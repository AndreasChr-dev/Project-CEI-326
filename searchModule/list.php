<?php
session_start();
require_once '../includes/db.php';

$keyword = trim($_GET['keyword'] ?? '');
$year = trim($_GET['year'] ?? '');
$category = trim($_GET['category'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$sql = '
    SELECT
        da.id,
        d.declaration_year AS submission_year,
        da.category,
        da.notes AS note,
        da.created_at,
        u.username,
        u.email,
        o.first_name,
        o.last_name,
        p.short_name AS party_short_name,
        p.name AS party_name,
        pos.title AS position_title
    FROM declaration_assets da
    JOIN declarations d ON da.declaration_id = d.id
    JOIN officials o ON d.official_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN parties p ON o.party_id = p.id
    LEFT JOIN positions pos ON o.position_id = pos.id
    WHERE d.status = \'submitted\'
';

$params = [];

if ($keyword !== '') {
    $sql .= '
        AND (
            u.username LIKE :keyword
            OR u.email LIKE :keyword
            OR o.first_name LIKE :keyword
            OR o.last_name LIKE :keyword
            OR CONCAT(o.first_name, \' \', o.last_name) LIKE :keyword
            OR p.name LIKE :keyword
            OR p.short_name LIKE :keyword
            OR pos.title LIKE :keyword
        )
    ';
    $params[':keyword'] = '%' . $keyword . '%';
}

if ($year !== '') {
    $sql .= ' AND d.declaration_year = :year ';
    $params[':year'] = $year;
}

if ($category !== '') {
    $sql .= ' AND da.category = :category ';
    $params[':category'] = $category;
}

if ($fromDate !== '') {
    $sql .= ' AND DATE(da.created_at) >= :from_date ';
    $params[':from_date'] = $fromDate;
}

if ($toDate !== '') {
    $sql .= ' AND DATE(da.created_at) <= :to_date ';
    $params[':to_date'] = $toDate;
}

$sql .= ' ORDER BY d.declaration_year DESC, da.created_at DESC ';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$categoryStmt = $pdo->prepare('
    SELECT DISTINCT category
    FROM declaration_assets
    ORDER BY category ASC
');
$categoryStmt->execute([]);
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Λίστα Υποβολών';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <div class="list-card">

        <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1>Υποβολές Πόθεν Έσχες</h1>
                <p class="mb-0 results-count">
                    <?php if ($keyword !== '' || $year !== '' || $category !== '' || $fromDate !== '' || $toDate !== ''): ?>
                        <?php echo count($submissions); ?> αποτέλεσμα(-τα) με τα επιλεγμένα κριτήρια
                    <?php else: ?>
                        Σύνολο <?php echo count($submissions); ?> υποβολών
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../searchModule/dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-house me-1"></i>Dashboard
                    </a>
                    <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Αποσύνδεση
                    </a>
                <?php else: ?>
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-house me-1"></i>Αρχική
                    </a>
                    <a href="../auth/login.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Σύνδεση
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="search-strip">
            <form action="list.php" method="GET">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="keyword" class="form-label">Αναζήτηση</label>

                        <div style="position: relative; width: 100%;">
                            <i class="bi bi-search"
                            style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#6b7a99; z-index:5; pointer-events:none;"></i>

                            <input
                                type="text"
                                class="form-control"
                                id="keyword"
                                name="keyword"
                                value="<?php echo htmlspecialchars($keyword); ?>"
                                placeholder="Αναζήτηση με username ή email..."
                                style="height:52px; padding-left:46px; border-radius:14px;"
                            >
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
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

                    <div class="col-12 col-md-3">
                        <label for="category" class="form-label">Κατηγορία</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Όλες</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="from_date" class="form-label">Από ημερομηνία</label>
                        <input
                            type="date"
                            class="form-control"
                            id="from_date"
                            name="from_date"
                            value="<?php echo htmlspecialchars($fromDate); ?>"
                        >
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="to_date" class="form-label">Έως ημερομηνία</label>
                        <input
                            type="date"
                            class="form-control"
                            id="to_date"
                            name="to_date"
                            value="<?php echo htmlspecialchars($toDate); ?>"
                        >
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-search me-1"></i>Εφαρμογή
                    </button>

                    <?php if ($keyword !== '' || $year !== '' || $category !== '' || $fromDate !== '' || $toDate !== ''): ?>
                        <a href="list.php" class="text-decoration-none" style="font-size:0.9rem; color:var(--text-muted);">
                            <i class="bi bi-x-circle me-1"></i>Εκκαθάριση φίλτρων
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="p-4">
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Δεν βρέθηκαν υποβολές με τα συγκεκριμένα κριτήρια.
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person me-1"></i>Username</th>
                            <th><i class="bi bi-envelope me-1"></i>Email</th>
                            <th><i class="bi bi-tag me-1"></i>Κατηγορία</th>
                            <th><i class="bi bi-calendar me-1"></i>Έτος</th>
                            <th><i class="bi bi-card-text me-1"></i>Σημείωση</th>
                            <th><i class="bi bi-clock me-1"></i>Ημερομηνία Δημιουργίας</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($submission['username'] ?? ''); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($submission['email'] ?? ''); ?></td>
                                <td>
                                    <span class="party-badge"><?php echo htmlspecialchars($submission['category']); ?></span>
                                </td>
                                <td>
                                    <span class="year-badge"><?php echo htmlspecialchars($submission['submission_year']); ?></span>
                                </td>
                                <td>
                                    <?php echo nl2br(htmlspecialchars($submission['note'] ?? '')); ?>
                                </td>
                                <td><?php echo htmlspecialchars($submission['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>


<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>
