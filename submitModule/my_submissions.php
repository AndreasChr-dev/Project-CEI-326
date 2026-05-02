<?php
session_start();
require_once '../includes/db.php';

// Session guard — μόνο official
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'official') {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

// Βρίσκουμε τον official
$stmt = $pdo->prepare('
    SELECT id FROM officials WHERE user_id = :uid LIMIT 1
');
$stmt->execute([':uid' => $_SESSION['user_id']]);
$official = $stmt->fetch();

if (!$official) {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

$officialId = (int)$official['id'];
$success    = '';

// -- Φίλτρα --
$filterYear   = trim($_GET['year']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// -- SUBMIT DRAFT (MERGE) --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_draft'])) {
    $draftId = (int)($_POST['submit_draft'] ?? 0);

    if ($draftId > 0) {
        $stmt = $pdo->prepare('
            SELECT id, declaration_year FROM declarations
            WHERE id = :id AND official_id = :official_id AND status = :status
            LIMIT 1
        ');
        $stmt->execute([
            ':id'          => $draftId,
            ':official_id' => $officialId,
            ':status'      => 'draft',
        ]);
        $draft = $stmt->fetch();

        if ($draft) {
            $stmt = $pdo->prepare('
                SELECT id FROM declarations
                WHERE official_id = :official_id
                  AND declaration_year = :year
                  AND status = :status
                  AND id != :draft_id
                LIMIT 1
            ');
            $stmt->execute([
                ':official_id' => $officialId,
                ':year'        => $draft['declaration_year'],
                ':status'      => 'submitted',
                ':draft_id'    => $draftId,
            ]);
            $submitted = $stmt->fetch();

            if ($submitted) {
                $stmt = $pdo->prepare('
                    UPDATE declaration_assets
                    SET declaration_id = :submitted_id
                    WHERE declaration_id = :draft_id
                ');
                $stmt->execute([
                    ':submitted_id' => $submitted['id'],
                    ':draft_id'     => $draftId,
                ]);

                $stmt = $pdo->prepare('DELETE FROM declarations WHERE id = :id');
                $stmt->execute([':id' => $draftId]);

                $success = 'Το πρόχειρο υποβλήθηκε και συγχωνεύτηκε επιτυχώς.';
            } else {
                $stmt = $pdo->prepare('
                    UPDATE declarations
                    SET status = :status, submitted_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':status' => 'submitted',
                    ':id'     => $draftId,
                ]);

                $success = 'Η δήλωση υποβλήθηκε επιτυχώς.';
            }
        }
    }
}

// -- Φόρτωση διαθέσιμων ετών για το φίλτρο --
$yearStmt = $pdo->prepare('
    SELECT DISTINCT declaration_year
    FROM declarations
    WHERE official_id = :official_id
    ORDER BY declaration_year DESC
');
$yearStmt->execute([':official_id' => $officialId]);
$availableYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// -- Φόρτωση δηλώσεων με φίλτρα --
$sql = '
    SELECT
        d.id,
        d.declaration_year,
        d.status,
        d.submitted_at,
        d.created_at,
        COUNT(da.id) AS asset_count,
        COALESCE(SUM(CASE WHEN da.category != :cat_debt1 THEN da.amount ELSE 0 END), 0) AS total_assets,
        COALESCE(SUM(CASE WHEN da.category  = :cat_debt2 THEN da.amount ELSE 0 END), 0) AS total_debt
    FROM declarations d
    LEFT JOIN declaration_assets da ON da.declaration_id = d.id
    WHERE d.official_id = :official_id
';

$params = [
    ':official_id' => $officialId,
    ':cat_debt1'   => 'Χρέη',
    ':cat_debt2'   => 'Χρέη',
];

if ($filterYear !== '') {
    $sql .= ' AND d.declaration_year = :year ';
    $params[':year'] = $filterYear;
}

if ($filterStatus !== '') {
    $sql .= ' AND d.status = :status ';
    $params[':status'] = $filterStatus;
}

$sql .= '
    GROUP BY d.id, d.declaration_year, d.status, d.submitted_at, d.created_at
    ORDER BY d.declaration_year DESC, d.status ASC
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$declarations = $stmt->fetchAll();

$pageTitle = 'Οι Δηλώσεις μου';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <!-- Header -->
    <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
         style="background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem 1.75rem; box-shadow:var(--shadow-sm);">
        <div>
            <h1><i class="bi bi-file-text me-2" style="color:var(--gold);"></i>Οι Δηλώσεις μου</h1>
            <p class="mb-0 results-count">
                <?php if ($filterYear !== '' || $filterStatus !== ''): ?>
                    <?php echo count($declarations); ?> αποτέλεσμα(-τα) με τα επιλεγμένα κριτήρια
                <?php else: ?>
                    Σύνολο <?php echo count($declarations); ?> δηλώσεων
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="../submitModule/submit.php"
               class="btn btn-primary btn-sm d-inline-flex align-items-center justify-content-center"
               style="min-height:42px; padding:0.55rem 1rem; font-size:0.95rem; font-weight:700; line-height:1;">
                <i class="bi bi-plus-circle me-1"></i>Νέα Υποβολή
            </a>

            <a href="../submitModule/profile.php"
               class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center justify-content-center"
               style="min-height:42px; padding:0.55rem 1rem; font-size:0.95rem; font-weight:700; line-height:1;">
                <i class="bi bi-person-circle me-1"></i>Προφίλ
            </a>

            <a href="../searchModule/dashboard.php"
               class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center justify-content-center"
               style="min-height:42px; padding:0.55rem 1rem; font-size:0.95rem; font-weight:700; line-height:1;">
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
            <form action="my_submissions.php" method="GET">
                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <label for="filter_year" class="form-label">Έτος</label>
                        <select class="form-select" id="filter_year" name="year">
                            <option value="">Όλα τα έτη</option>
                            <?php foreach ($availableYears as $yr): ?>
                                <option value="<?php echo htmlspecialchars($yr); ?>" <?php echo $filterYear === (string)$yr ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($yr); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="filter_status" class="form-label">Κατάσταση</label>
                        <select class="form-select" id="filter_status" name="status">
                            <option value="">Όλες</option>
                            <option value="submitted" <?php echo $filterStatus === 'submitted' ? 'selected' : ''; ?>>Υποβλήθηκε</option>
                            <option value="draft"     <?php echo $filterStatus === 'draft'     ? 'selected' : ''; ?>>Πρόχειρο</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Εφαρμογή
                        </button>
                    </div>
                </div>
                <?php if ($filterYear !== '' || $filterStatus !== ''): ?>
                    <div class="mt-2">
                        <a href="my_submissions.php" class="text-decoration-none" style="font-size:0.9rem; color:var(--text-muted);">
                            <i class="bi bi-x-circle me-1"></i>Εκκαθάριση φίλτρων
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (empty($declarations)): ?>
        <div class="list-card">
            <div class="p-4">
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Δεν βρέθηκαν δηλώσεις με τα επιλεγμένα κριτήρια.
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php foreach ($declarations as $decl): ?>
            <div class="list-card mb-3">

                <!-- Δήλωση header -->
                <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="year-badge" style="font-size:1rem; padding: 4px 16px;">
                            <?php echo htmlspecialchars($decl['declaration_year']); ?>
                        </span>
                        <?php if ($decl['status'] === 'submitted'): ?>
                            <span class="status-submitted">
                                <i class="bi bi-check-circle me-1"></i>Υποβλήθηκε
                            </span>
                        <?php else: ?>
                            <span class="status-draft">
                                <i class="bi bi-pencil me-1"></i>Πρόχειρο
                            </span>
                        <?php endif; ?>
                        <span style="font-size:0.8rem; color:var(--text-muted);">
                            <?php echo htmlspecialchars($decl['asset_count']); ?> καταχωρήσεις
                        </span>
                    </div>
                    <div class="d-flex gap-3 flex-wrap" style="font-size:0.82rem; color:var(--text-muted);">
                        <?php if ($decl['submitted_at']): ?>
                            <span><i class="bi bi-clock me-1"></i>Υποβλήθηκε: <?php echo htmlspecialchars(date('d/m/Y', strtotime($decl['submitted_at']))); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Σύνοψη ποσών -->
                <div class="d-flex flex-wrap gap-4 px-4 py-3" style="border-bottom: 1px solid var(--border); background: var(--bg);">
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Συνολική Περιουσία</div>
                        <div class="amount-cell" style="font-size:1.05rem;">
                            <?php echo number_format((float)$decl['total_assets'], 2, ',', '.'); ?> €
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Συνολικά Χρέη</div>
                        <div class="amount-cell debts" style="font-size:1.05rem;">
                            <?php echo number_format((float)$decl['total_debt'], 2, ',', '.'); ?> €
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); font-weight:700;">Καθαρή Θέση</div>
                        <div class="amount-cell" style="font-size:1.05rem; color: <?php echo ($decl['total_assets'] - $decl['total_debt']) >= 0 ? 'var(--navy)' : '#8b3030'; ?>;">
                            <?php echo number_format((float)($decl['total_assets'] - $decl['total_debt']), 2, ',', '.'); ?> €
                        </div>
                    </div>
                </div>

                <!-- Assets του declaration -->
                <?php
                $assetStmt = $pdo->prepare('
                    SELECT * FROM declaration_assets
                    WHERE declaration_id = :did
                    ORDER BY category ASC, created_at ASC
                ');
                $assetStmt->execute([':did' => $decl['id']]);
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Footer κάρτας -->
                <div class="px-4 py-3 d-flex align-items-center gap-2 flex-wrap">
                    <a href="../submitModule/submit.php?year=<?php echo htmlspecialchars($decl['declaration_year']); ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-plus me-1"></i>Προσθήκη Στοιχείου
                    </a>

                    <?php if ($decl['status'] === 'draft'): ?>
                        <form action="my_submissions.php<?php echo $filterYear || $filterStatus ? '?' . http_build_query(['year' => $filterYear, 'status' => $filterStatus]) : ''; ?>"
                              method="POST" style="margin:0;">
                            <input type="hidden" name="submit_draft" value="<?php echo (int)$decl['id']; ?>">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="Swal.fire({title:'Επιβεβαίωση',text:'Υποβολή και συγχώνευση του πρόχειρου με τη δήλωση <?php echo htmlspecialchars($decl['declaration_year']); ?>;',icon:'question',showCancelButton:true,confirmButtonText:'Εντάξει',cancelButtonText:'Ακύρωση',confirmButtonColor:'#1a2f5a'}).then(r=>{if(r.isConfirmed)this.closest('form').submit();})">
                                <i class="bi bi-send me-1"></i>Υποβολή & Συγχώνευση
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>
