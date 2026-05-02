<?php
session_start();
require_once '../includes/db.php';

// Στατιστικά ανά κόμμα
$stmt = $pdo->prepare('
    SELECT
        COALESCE(p.name, :independent_name)      AS party_name,
        COALESCE(p.short_name, :independent_short) AS party_short,
        COUNT(DISTINCT o.id)                     AS total_officials,
        COUNT(DISTINCT d.id)                     AS total_declarations,
        COALESCE(SUM(CASE WHEN da.category != :cat_debt1 THEN da.amount ELSE 0 END), 0) AS total_assets,
        COALESCE(SUM(CASE WHEN da.category  = :cat_debt2 THEN da.amount ELSE 0 END), 0) AS total_debt,
        COALESCE(SUM(CASE WHEN da.category  = :cat_deposits1 THEN da.amount ELSE 0 END), 0) AS total_deposits
    FROM officials o
    LEFT JOIN parties p      ON p.id  = o.party_id
    LEFT JOIN declarations d ON d.official_id = o.id AND d.status = :status
    LEFT JOIN declaration_assets da ON da.declaration_id = d.id
    GROUP BY p.id, p.name, p.short_name
    ORDER BY total_assets DESC
');
$stmt->execute([
    ':independent_name'  => 'Ανεξάρτητος',
    ':independent_short' => '—',
    ':cat_debt1'         => 'Χρέη',
    ':cat_debt2'         => 'Χρέη',
    ':cat_deposits1'     => 'Καταθέσεις',
    ':status'            => 'submitted',
]);
$byParty = $stmt->fetchAll();

// Στατιστικά ανά έτος
$stmt = $pdo->prepare('
    SELECT
        d.declaration_year                                                               AS year,
        COUNT(DISTINCT d.id)                                                             AS total_declarations,
        COUNT(DISTINCT d.official_id)                                                    AS total_officials,
        COALESCE(SUM(CASE WHEN da.category != :cat_debt1 THEN da.amount ELSE 0 END), 0) AS total_assets,
        COALESCE(SUM(CASE WHEN da.category  = :cat_debt2 THEN da.amount ELSE 0 END), 0) AS total_debt,
        COALESCE(SUM(CASE WHEN da.category  = :cat_deposits1 THEN da.amount ELSE 0 END), 0) AS total_deposits
    FROM declarations d
    LEFT JOIN declaration_assets da ON da.declaration_id = d.id
    WHERE d.status = :status
    GROUP BY d.declaration_year
    ORDER BY d.declaration_year DESC
');
$stmt->execute([
    ':cat_debt1'     => 'Χρέη',
    ':cat_debt2'     => 'Χρέη',
    ':cat_deposits1' => 'Καταθέσεις',
    ':status'        => 'submitted',
]);
$byYear = $stmt->fetchAll();

// Στατιστικά ανά κατηγορία
$stmt = $pdo->prepare('
    SELECT
        da.category,
        COUNT(da.id)                AS total_entries,
        COALESCE(SUM(da.amount), 0) AS total_amount,
        COALESCE(AVG(da.amount), 0) AS avg_amount
    FROM declaration_assets da
    JOIN declarations d ON d.id = da.declaration_id
    WHERE d.status = :status
    GROUP BY da.category
    ORDER BY total_amount DESC
');
$stmt->execute([':status' => 'submitted']);
$byCategory = $stmt->fetchAll();

// Στατιστικά ανά επαρχία
$stmt = $pdo->prepare('
    SELECT
        COALESCE(o.district, :unknown_district)  AS district,
        COUNT(DISTINCT o.id)                     AS total_officials,
        COALESCE(SUM(CASE WHEN da.category != :cat_debt1 THEN da.amount ELSE 0 END), 0) AS total_assets
    FROM officials o
    LEFT JOIN declarations d        ON d.official_id = o.id AND d.status = :status
    LEFT JOIN declaration_assets da ON da.declaration_id = d.id
    GROUP BY o.district
    ORDER BY total_officials DESC
');
$stmt->execute([
    ':unknown_district' => 'Άγνωστη',
    ':cat_debt1'        => 'Χρέη',
    ':status'           => 'submitted',
]);
$byDistrict = $stmt->fetchAll();

// Συνολικά μεγέθη
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT id) AS total FROM officials');
$stmt->execute([]);
$totalOfficials = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(DISTINCT id) AS total FROM declarations WHERE status = :status');
$stmt->execute([':status' => 'submitted']);
$totalDeclarations = $stmt->fetch()['total'];

$stmt = $pdo->prepare('
    SELECT
        COALESCE(SUM(CASE WHEN da.category != :cat_debt1 THEN da.amount ELSE 0 END), 0) AS total_assets,
        COALESCE(SUM(CASE WHEN da.category  = :cat_debt2 THEN da.amount ELSE 0 END), 0) AS total_debt,
        COALESCE(SUM(CASE WHEN da.category  = :cat_deposits1 THEN da.amount ELSE 0 END), 0) AS total_deposits
    FROM declaration_assets da
    JOIN declarations d ON d.id = da.declaration_id
    WHERE d.status = :status
');
$stmt->execute([
    ':cat_debt1'     => 'Χρέη',
    ':cat_debt2'     => 'Χρέη',
    ':cat_deposits1' => 'Καταθέσεις',
    ':status'        => 'submitted',
]);
$totals = $stmt->fetch();

$pageTitle = 'Στατιστικά';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <!-- Header -->
    <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
         style="background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem 1.75rem; box-shadow:var(--shadow-sm);">
        <div>
            <h1><i class="bi bi-bar-chart me-2" style="color:var(--gold);"></i>Στατιστικά Πόθεν Έσχες</h1>
            <p class="mb-0 results-count">Συγκεντρωτικά στοιχεία δηλώσεων αξιωματούχων</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="../searchModule/list.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-search me-1"></i>Δηλώσεις
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../searchModule/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house me-1"></i>Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Κάρτες συνόλων -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="module-card" style="pointer-events:none;">
                <div class="card-icon">👤</div>
                <h3><?php echo htmlspecialchars($totalOfficials); ?></h3>
                <p>Αξιωματούχοι</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="module-card" style="pointer-events:none;">
                <div class="card-icon">📋</div>
                <h3><?php echo htmlspecialchars($totalDeclarations); ?></h3>
                <p>Δηλώσεις</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="module-card" style="pointer-events:none;">
                <div class="card-icon gold-icon">💶</div>
                <h3><?php echo number_format((float)$totals['total_assets'], 0, ',', '.'); ?> €</h3>
                <p>Συνολική Περιουσία</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="module-card" style="pointer-events:none;">
                <div class="card-icon gold-icon">🏦</div>
                <h3><?php echo number_format((float)$totals['total_deposits'], 0, ',', '.'); ?> €</h3>
                <p>Συνολικές Καταθέσεις</p>
            </div>
        </div>
    </div>

    <!-- Ανά Κόμμα -->
    <p class="section-title">Ανά Κόμμα</p>
    <div class="list-card mb-4">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th><i class="bi bi-diagram-3 me-1"></i>Κόμμα</th>
                        <th><i class="bi bi-people me-1"></i>Αξιωματούχοι</th>
                        <th><i class="bi bi-file-text me-1"></i>Δηλώσεις</th>
                        <th><i class="bi bi-graph-up me-1"></i>Συνολική Περιουσία</th>
                        <th><i class="bi bi-bank me-1"></i>Καταθέσεις</th>
                        <th><i class="bi bi-graph-down me-1"></i>Χρέη</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byParty as $row): ?>
                        <tr>
                            <td>
                                <span class="party-badge"><?php echo htmlspecialchars($row['party_short']); ?></span>
                                <span class="ms-2" style="font-size:0.82rem; color:var(--text-muted);">
                                    <?php echo htmlspecialchars($row['party_name']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['total_officials']); ?></td>
                            <td><?php echo htmlspecialchars($row['total_declarations']); ?></td>
                            <td class="amount-cell"><?php echo number_format((float)$row['total_assets'], 2, ',', '.'); ?> €</td>
                            <td class="amount-cell"><?php echo number_format((float)$row['total_deposits'], 2, ',', '.'); ?> €</td>
                            <td class="amount-cell debts"><?php echo number_format((float)$row['total_debt'], 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ανά Έτος -->
    <p class="section-title">Ανά Έτος</p>
    <div class="list-card mb-4">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th><i class="bi bi-calendar me-1"></i>Έτος</th>
                        <th><i class="bi bi-file-text me-1"></i>Δηλώσεις</th>
                        <th><i class="bi bi-people me-1"></i>Αξιωματούχοι</th>
                        <th><i class="bi bi-graph-up me-1"></i>Συνολική Περιουσία</th>
                        <th><i class="bi bi-bank me-1"></i>Καταθέσεις</th>
                        <th><i class="bi bi-graph-down me-1"></i>Χρέη</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byYear as $row): ?>
                        <tr>
                            <td><span class="year-badge"><?php echo htmlspecialchars($row['year']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['total_declarations']); ?></td>
                            <td><?php echo htmlspecialchars($row['total_officials']); ?></td>
                            <td class="amount-cell"><?php echo number_format((float)$row['total_assets'], 2, ',', '.'); ?> €</td>
                            <td class="amount-cell"><?php echo number_format((float)$row['total_deposits'], 2, ',', '.'); ?> €</td>
                            <td class="amount-cell debts"><?php echo number_format((float)$row['total_debt'], 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ανά Κατηγορία -->
    <p class="section-title">Ανά Κατηγορία Περιουσιακού Στοιχείου</p>
    <div class="list-card mb-4">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th><i class="bi bi-tag me-1"></i>Κατηγορία</th>
                        <th><i class="bi bi-hash me-1"></i>Καταχωρήσεις</th>
                        <th><i class="bi bi-cash-stack me-1"></i>Συνολικό Ποσό</th>
                        <th><i class="bi bi-calculator me-1"></i>Μέσος Όρος</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byCategory as $row): ?>
                        <tr>
                            <td>
                                <span class="party-badge"><?php echo htmlspecialchars($row['category']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['total_entries']); ?></td>
                            <td class="amount-cell <?php echo $row['category'] === 'Χρέη' ? 'debts' : ''; ?>">
                                <?php echo number_format((float)$row['total_amount'], 2, ',', '.'); ?> €
                            </td>
                            <td class="amount-cell <?php echo $row['category'] === 'Χρέη' ? 'debts' : ''; ?>">
                                <?php echo number_format((float)$row['avg_amount'], 2, ',', '.'); ?> €
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ανά Επαρχία -->
    <p class="section-title">Ανά Επαρχία</p>
    <div class="list-card mb-4">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th><i class="bi bi-geo-alt me-1"></i>Επαρχία</th>
                        <th><i class="bi bi-people me-1"></i>Αξιωματούχοι</th>
                        <th><i class="bi bi-graph-up me-1"></i>Συνολική Περιουσία</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byDistrict as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['district']); ?></td>
                            <td><?php echo htmlspecialchars($row['total_officials']); ?></td>
                            <td class="amount-cell"><?php echo number_format((float)$row['total_assets'], 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>
