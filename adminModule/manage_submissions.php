<?php
session_start();
require_once '../includes/db.php';

// Session guard — μόνο admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

$success = '';
$errors  = [];

// -- ΔΙΑΓΡΑΦΗ ASSET --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset'])) {
    $assetId = (int)($_POST['delete_asset'] ?? 0);

    if ($assetId > 0) {
        $stmt = $pdo->prepare('DELETE FROM declaration_assets WHERE id = :id');
        $stmt->execute([':id' => $assetId]);
        $success = 'Η καταχώρηση διαγράφηκε επιτυχώς.';
    }
}

// -- ΔΙΑΓΡΑΦΗ DECLARATION --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_declaration'])) {
    $declarationId = (int)($_POST['delete_declaration'] ?? 0);

    if ($declarationId > 0) {
        $stmt = $pdo->prepare('DELETE FROM declarations WHERE id = :id');
        $stmt->execute([':id' => $declarationId]);
        $success = 'Η δήλωση διαγράφηκε επιτυχώς.';
    }
}

// -- ΦΙΛΤΡΑ --
$keyword = trim($_GET['keyword'] ?? '');
$year    = trim($_GET['year']    ?? '');

// -- QUERY --
$sql = '
    SELECT
        d.id            AS declaration_id,
        d.declaration_year,
        d.status,
        d.submitted_at,
        d.created_at,
        o.first_name,
        o.last_name,
        o.district,
        u.email,
        u.username,
        p.short_name    AS party_short,
        pos.title       AS position_title,
        COUNT(da.id)    AS asset_count,
        COALESCE(SUM(CASE WHEN da.category != \'Χρέη\' THEN da.amount ELSE 0 END), 0) AS total_assets,
        COALESCE(SUM(CASE WHEN da.category  = \'Χρέη\' THEN da.amount ELSE 0 END), 0) AS total_debt
    FROM declarations d
    JOIN officials o         ON o.id         = d.official_id
    LEFT JOIN users u        ON u.id         = o.user_id
    LEFT JOIN parties p      ON p.id         = o.party_id
    LEFT JOIN positions pos  ON pos.id       = o.position_id
    LEFT JOIN declaration_assets da ON da.declaration_id = d.id
    WHERE d.status = :submitted_status
';

$params = [
    ':submitted_status' => 'submitted',
];

if ($keyword !== '') {
    $sql .= '
        AND (
            o.first_name LIKE :keyword
            OR o.last_name LIKE :keyword
            OR CONCAT(o.first_name, \' \', o.last_name) LIKE :keyword
            OR u.email LIKE :keyword
            OR p.name LIKE :keyword
            OR p.short_name LIKE :keyword
        )
    ';
    $params[':keyword'] = '%' . $keyword . '%';
}

if ($year !== '') {
    $sql .= ' AND d.declaration_year = :year ';
    $params[':year'] = $year;
}

$sql .= '
    GROUP BY d.id, d.declaration_year, d.status, d.submitted_at,
             d.created_at, o.first_name, o.last_name, o.district,
             u.email, u.username, p.short_name, pos.title
    ORDER BY d.declaration_year DESC, o.last_name ASC
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$declarations = $stmt->fetchAll();

$pageTitle = 'Διαχείριση Υποβολών';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <!-- Header -->
    <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
         style="background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem 1.75rem; box-shadow:var(--shadow-sm);">
        <div>
            <h1><i class="bi bi-folder me-2" style="color:var(--gold);"></i>Διαχείριση Υποβολών</h1>
            <p class="mb-0 results-count">
                <?php if ($keyword !== '' || $year !== ''): ?>
                    <?php echo count($declarations); ?> αποτέλεσμα(-τα) με τα επιλεγμένα κριτήρια
                <?php else: ?>
                    Σύνολο <?php echo count($declarations); ?> υποβλημένων δηλώσεων
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="../searchModule/dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Επιτυχία',
                text: <?php echo json_encode($success); ?>,
                confirmButtonText: 'Εντάξει',
                confirmButtonColor: '#1a2f5a'
            });
        </script>
    <?php endif; ?>

    <!-- Φίλτρα -->
    <div class="list-card mb-4">
        <div class="search-strip">
            <form action="manage_submissions.php" method="GET">
                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <label for="keyword" class="form-label">Αναζήτηση</label>
                        <div style="position:relative;">
                            <i class="bi bi-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#6b7a99; z-index:5; pointer-events:none;"></i>
                            <input
                                type="text"
                                class="form-control"
                                id="keyword"
                                name="keyword"
                                value="<?php echo htmlspecialchars($keyword); ?>"
                                placeholder="Όνομα, email, κόμμα..."
                                style="height:52px; padding-left:46px; border-radius:14px;"
                            >
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="year" class="form-label">Έτος</label>
                        <input
                            type="text"
                            class="form-control"
                            id="year"
                            name="year"
                            value="<?php echo htmlspecialchars($year); ?>"
                            placeholder="π.χ. 2025"
                        >
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="status" class="form-label">Κατάσταση</label>
                        <input
                            type="text"
                            class="form-control"
                            id="status"
                            value="Υποβλήθηκε"
                            readonly
                            style="background:var(--bg); cursor:not-allowed;"
                        >
                    </div>
                </div>
                <div class="d-flex gap-3 mt-3 align-items-center">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-search me-1"></i>Εφαρμογή
                    </button>
                    <?php if ($keyword !== '' || $year !== ''): ?>
                        <a href="manage_submissions.php" class="text-decoration-none" style="font-size:0.9rem; color:var(--text-muted);">
                            <i class="bi bi-x-circle me-1"></i>Εκκαθάριση φίλτρων
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Αποτελέσματα -->
    <?php if (empty($declarations)): ?>
        <div class="list-card">
            <div class="p-4">
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Δεν βρέθηκαν υποβλημένες δηλώσεις με τα επιλεγμένα κριτήρια.
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php foreach ($declarations as $decl): ?>
            <div class="list-card mb-3">

                <!-- Δήλωση header -->
                <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="year-badge"><?php echo htmlspecialchars($decl['declaration_year']); ?></span>
                            <span class="status-submitted"><i class="bi bi-check-circle me-1"></i>Υποβλήθηκε</span>
                            <?php if ($decl['party_short']): ?>
                                <span class="party-badge"><?php echo htmlspecialchars($decl['party_short']); ?></span>
                            <?php endif; ?>
                        </div>
                        <strong style="font-size:1rem;">
                            <?php echo htmlspecialchars($decl['first_name'] . ' ' . $decl['last_name']); ?>
                        </strong>
                        <span style="font-size:0.82rem; color:var(--text-muted); margin-left:8px;">
                            <?php echo htmlspecialchars($decl['position_title'] ?? ''); ?>
                            <?php if ($decl['district']): ?>
                                · <?php echo htmlspecialchars($decl['district']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <!-- Διαγραφή ολόκληρης δήλωσης -->
                        <form action="manage_submissions.php<?php echo $keyword || $year ? '?' . http_build_query(['keyword' => $keyword, 'year' => $year]) : ''; ?>" method="POST">
                            <input type="hidden" name="delete_declaration" value="<?php echo (int)$decl['declaration_id']; ?>">
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                onclick="Swal.fire({title:'Επιβεβαίωση',text:'Διαγραφή ολόκληρης της δήλωσης <?php echo htmlspecialchars($decl['declaration_year']); ?> του Χρήστη <?php echo htmlspecialchars($decl['first_name'] . ' ' . $decl['last_name'], ENT_QUOTES); ?>;',icon:'warning',showCancelButton:true,confirmButtonText:'Εντάξει',cancelButtonText:'Ακύρωση',confirmButtonColor:'#1a2f5a'}).then(r=>{if(r.isConfirmed)this.closest('form').submit();})">
                                <i class="bi bi-trash me-1"></i>Διαγραφή Δήλωσης
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Σύνοψη ποσών -->
                <div class="d-flex flex-wrap gap-4 px-4 py-3" style="border-bottom: 1px solid var(--border); background: var(--bg);">
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Περιουσία</div>
                        <div class="amount-cell"><?php echo number_format((float)$decl['total_assets'], 2, ',', '.'); ?> €</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Χρέη</div>
                        <div class="amount-cell debts"><?php echo number_format((float)$decl['total_debt'], 2, ',', '.'); ?> €</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Καταχωρήσεις</div>
                        <div style="font-weight:700; color:var(--navy);"><?php echo htmlspecialchars($decl['asset_count']); ?></div>
                    </div>
                    <?php if ($decl['submitted_at']): ?>
                        <div>
                            <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Ημ. Υποβολής</div>
                            <div style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars(date('d/m/Y', strtotime($decl['submitted_at']))); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Assets -->
                <?php
                $assetStmt = $pdo->prepare('
                    SELECT * FROM declaration_assets
                    WHERE declaration_id = :did
                    ORDER BY created_at ASC
                ');
                $assetStmt->execute([':did' => $decl['declaration_id']]);
                $assets = $assetStmt->fetchAll();
                ?>

                <?php if (!empty($assets)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle" style="margin:0;">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-tag me-1"></i>Κατηγορία</th>
                                    <th><i class="bi bi-card-text me-1"></i>Περιγραφή</th>
                                    <th><i class="bi bi-cash me-1"></i>Ποσό</th>
                                    <th><i class="bi bi-chat-text me-1"></i>Σημειώσεις</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assets as $asset): ?>
                                    <tr>
                                        <td>
                                            <span class="party-badge"><?php echo htmlspecialchars($asset['category']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($asset['description']); ?></td>
                                        <td class="amount-cell <?php echo $asset['category'] === 'Χρέη' ? 'debts' : ''; ?>">
                                            <?php echo number_format((float)$asset['amount'], 2, ',', '.'); ?> €
                                        </td>
                                        <td style="font-size:0.82rem; color:var(--text-muted);">
                                            <?php echo nl2br(htmlspecialchars($asset['notes'] ?? '')); ?>
                                        </td>
                                        <td>
                                            <form action="manage_submissions.php<?php echo $keyword || $year ? '?' . http_build_query(['keyword' => $keyword, 'year' => $year]) : ''; ?>" method="POST">
                                                <input type="hidden" name="delete_asset" value="<?php echo (int)$asset['id']; ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="Swal.fire({title:'Επιβεβαίωση',text:'Διαγραφή αυτής της καταχώρησης;',icon:'warning',showCancelButton:true,confirmButtonText:'Εντάξει',cancelButtonText:'Ακύρωση',confirmButtonColor:'#1a2f5a'}).then(r=>{if(r.isConfirmed)this.closest('form').submit();})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>