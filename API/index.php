<?php
require_once '../includes/db.php';

// Επικεφαλίδες για JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Μόνο GET requests επιτρέπονται
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use GET.']);
    exit;
}

// Router — βάσει ?endpoint=
$endpoint = trim($_GET['endpoint'] ?? '');

switch ($endpoint) {

    // -- GET /api/?endpoint=officials --
    case 'officials':
        $stmt = $pdo->query('
            SELECT
                o.id,
                o.first_name,
                o.last_name,
                o.district,
                p.name       AS party_name,
                p.short_name AS party_short,
                pos.title    AS position
            FROM officials o
            LEFT JOIN parties   p   ON p.id   = o.party_id
            LEFT JOIN positions pos ON pos.id = o.position_id
            ORDER BY o.last_name ASC
        ');
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
        break;

    // ── GET /api/?endpoint=declarations ───────────────────────────
    case 'declarations':
        $year       = trim($_GET['year']        ?? '');
        $party      = trim($_GET['party']       ?? '');
        $officialId = trim($_GET['official_id'] ?? '');

        $sql = '
            SELECT
                d.id,
                d.declaration_year,
                d.status,
                d.submitted_at,
                o.first_name,
                o.last_name,
                o.district,
                p.name       AS party_name,
                p.short_name AS party_short,
                pos.title    AS position
            FROM declarations d
            JOIN officials o         ON o.id   = d.official_id
            LEFT JOIN parties p      ON p.id   = o.party_id
            LEFT JOIN positions pos  ON pos.id = o.position_id
            WHERE d.status = \'submitted\'
        ';

        $params = [];

        if ($year !== '') {
            $sql .= ' AND d.declaration_year = :year ';
            $params[':year'] = $year;
        }

        if ($party !== '') {
            $sql .= ' AND (p.name LIKE :party OR p.short_name LIKE :party) ';
            $params[':party'] = '%' . $party . '%';
        }

        if ($officialId !== '') {
            $sql .= ' AND o.id = :official_id ';
            $params[':official_id'] = (int)$officialId;
        }

        $sql .= ' ORDER BY d.declaration_year DESC, o.last_name ASC ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
        break;

    // ── GET /api/?endpoint=declaration&id=X ───────────────────────
    case 'declaration':
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Απαιτείται έγκυρο id δήλωσης.']);
            exit;
        }

        // Βασικά στοιχεία δήλωσης
        $stmt = $pdo->prepare('
            SELECT
                d.id,
                d.declaration_year,
                d.status,
                d.submitted_at,
                o.first_name,
                o.last_name,
                o.district,
                p.name       AS party_name,
                p.short_name AS party_short,
                pos.title    AS position
            FROM declarations d
            JOIN officials o        ON o.id   = d.official_id
            LEFT JOIN parties p     ON p.id   = o.party_id
            LEFT JOIN positions pos ON pos.id = o.position_id
            WHERE d.id = :id AND d.status = \'submitted\'
        ');
        $stmt->execute([':id' => $id]);
        $declaration = $stmt->fetch();

        if (!$declaration) {
            http_response_code(404);
            echo json_encode(['error' => 'Δήλωση δεν βρέθηκε.']);
            exit;
        }

        // Assets της δήλωσης
        $stmt = $pdo->prepare('
            SELECT
                id,
                category,
                description,
                amount,
                currency,
                notes
            FROM declaration_assets
            WHERE declaration_id = :id
            ORDER BY created_at ASC
        ');
        $stmt->execute([':id' => $id]);
        $declaration['assets'] = $stmt->fetchAll();

        echo json_encode($declaration, JSON_UNESCAPED_UNICODE);
        break;

    // ── GET /api/?endpoint=statistics ─────────────────────────────
    case 'statistics':
        $result = [];

        // Ανά κόμμα
        $stmt = $pdo->query('
            SELECT
                COALESCE(p.name, \'Ανεξάρτητος\')  AS party_name,
                COALESCE(p.short_name, \'—\')       AS party_short,
                COUNT(DISTINCT o.id)               AS total_officials,
                COUNT(DISTINCT d.id)               AS total_declarations,
                COALESCE(SUM(CASE WHEN da.category != \'Χρέη\' THEN da.amount ELSE 0 END), 0) AS total_assets,
                COALESCE(SUM(CASE WHEN da.category  = \'Χρέη\' THEN da.amount ELSE 0 END), 0) AS total_debt
            FROM officials o
            LEFT JOIN parties p      ON p.id  = o.party_id
            LEFT JOIN declarations d ON d.official_id = o.id AND d.status = \'submitted\'
            LEFT JOIN declaration_assets da ON da.declaration_id = d.id
            GROUP BY p.id, p.name, p.short_name
            ORDER BY total_assets DESC
        ');
        $result['by_party'] = $stmt->fetchAll();

        // Ανά έτος
        $stmt = $pdo->query('
            SELECT
                d.declaration_year                                                              AS year,
                COUNT(DISTINCT d.id)                                                            AS total_declarations,
                COALESCE(SUM(CASE WHEN da.category != \'Χρέη\' THEN da.amount ELSE 0 END), 0) AS total_assets,
                COALESCE(SUM(CASE WHEN da.category  = \'Χρέη\' THEN da.amount ELSE 0 END), 0) AS total_debt
            FROM declarations d
            LEFT JOIN declaration_assets da ON da.declaration_id = d.id
            WHERE d.status = \'submitted\'
            GROUP BY d.declaration_year
            ORDER BY d.declaration_year DESC
        ');
        $result['by_year'] = $stmt->fetchAll();

        // Ανά κατηγορία
        $stmt = $pdo->query('
            SELECT
                da.category,
                COUNT(da.id)                AS total_entries,
                COALESCE(SUM(da.amount), 0) AS total_amount,
                COALESCE(AVG(da.amount), 0) AS avg_amount
            FROM declaration_assets da
            JOIN declarations d ON d.id = da.declaration_id
            WHERE d.status = \'submitted\'
            GROUP BY da.category
            ORDER BY total_amount DESC
        ');
        $result['by_category'] = $stmt->fetchAll();

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    // -- Default: λίστα endpoints --
    default:
        echo json_encode([
            'name'    => 'Πόθεν Έσχες API',
            'version' => '1.0',
            'endpoints' => [
                'officials'   => 'api/?endpoint=officials — Λίστα αξιωματούχων',
                'declarations'=> 'api/?endpoint=declarations&year=2025&party=ΔΗΣΥ — Λίστα δηλώσεων',
                'declaration' => 'api/?endpoint=declaration&id=1 — Μία δήλωση με assets',
                'statistics'  => 'api/?endpoint=statistics — Στατιστικά ανά κόμμα/έτος/κατηγορία',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        break;
}
