<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'official') {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

$errors  = [];
$success = false;
$isDraft = false;

$submission_year = trim($_GET['year'] ?? '');
$category        = '';
$amount          = '';
$note            = '';

$allowed_categories = ['Εισόδημα', 'Καταθέσεις', 'Ακίνητα', 'Χρέη', 'Οχήματα', 'Μετοχές', 'Περιουσία'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_year = trim($_POST['submission_year'] ?? '');
    $category        = trim($_POST['category']        ?? '');
    $amount          = trim($_POST['amount']          ?? '');
    $note            = trim($_POST['note']            ?? '');
    $isDraft         = isset($_POST['save_draft']);
    $status          = $isDraft ? 'draft' : 'submitted';

    if ($submission_year === '') {
        $errors[] = 'Το έτος υποβολής είναι υποχρεωτικό.';
    } elseif (!preg_match('/^\d{4}$/', $submission_year) || (int)$submission_year < 2000 || (int)$submission_year > (int)date('Y')) {
        $errors[] = 'Μη έγκυρο έτος υποβολής.';
    }

    if ($category === '') {
        $errors[] = 'Η κατηγορία είναι υποχρεωτική.';
    } elseif (!in_array($category, $allowed_categories, true)) {
        $errors[] = 'Μη έγκυρη κατηγορία.';
    }

    if ($amount === '' || !is_numeric($amount) || (float)$amount < 0) {
        $errors[] = 'Εισάγετε έγκυρο ποσό.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            SELECT id FROM officials
            WHERE user_id = :uid
            LIMIT 1
        ');
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $official = $stmt->fetch();

        if (!$official) {
            $errors[] = 'Δεν υπάρχει προφίλ αξιωματούχου συνδεδεμένο με τον λογαριασμό σας.';
        }
    }

    if (empty($errors)) {
        // Ψάχνει μόνο για δήλωση με το ίδιο status
        $stmt = $pdo->prepare('
            SELECT id FROM declarations
            WHERE official_id = :official_id
              AND declaration_year = :year
              AND status = :status
            LIMIT 1
        ');
        $stmt->execute([
            ':official_id' => $official['id'],
            ':year'        => $submission_year,
            ':status'      => $status,
        ]);

        $declaration = $stmt->fetch();

        if ($declaration) {
            $declarationId = (int)$declaration['id'];
        } else {
            $submittedAt = $isDraft ? null : date('Y-m-d H:i:s');

            $stmt = $pdo->prepare('
                INSERT INTO declarations
                    (official_id, declaration_year, status, submitted_at)
                VALUES
                    (:official_id, :year, :status, :submitted_at)
            ');
            $stmt->execute([
                ':official_id'  => $official['id'],
                ':year'         => $submission_year,
                ':status'       => $status,
                ':submitted_at' => $submittedAt,
            ]);

            $declarationId = (int)$pdo->lastInsertId();
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO declaration_assets
                (declaration_id, category, description, amount, currency, notes)
            VALUES
                (:declaration_id, :category, :description, :amount, \'EUR\', :notes)
        ');
        $stmt->execute([
            ':declaration_id' => $declarationId,
            ':category'       => $category,
            ':description'    => $category . ' για το έτος ' . $submission_year,
            ':amount'         => (float)$amount,
            ':notes'          => $note !== '' ? $note : null,
        ]);

        $success = true;

        $submission_year = trim($_GET['year'] ?? '');
        $category        = '';
        $amount          = '';
        $note            = '';
    }
}

$pageTitle = 'Νέα Υποβολή';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <div class="list-card">

        <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1><i class="bi bi-plus-circle me-2" style="color:var(--gold);"></i>Νέα Υποβολή Δήλωσης</h1>
                <p class="mb-0 results-count">Συμπληρώστε τα στοιχεία της δήλωσης Πόθεν Έσχες</p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="../submitModule/my_submissions.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list-ul me-1"></i>Λίστα Υποβολών
                </a>
                <a href="../searchModule/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house me-1"></i>Dashboard
                </a>
            </div>
        </div>

        <div class="auth-card-body" style="padding: 2rem;">

            <?php if ($success): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Επιτυχής Υποβολή',
                        text: '<?php echo $isDraft ? "Η δήλωσή σας αποθηκεύτηκε ως πρόχειρο." : "Η δήλωσή σας καταχωρήθηκε με επιτυχία."; ?>',
                        confirmButtonText: 'Εντάξει',
                        confirmButtonColor: '#1a2f5a'
                    });
                </script>
                <div class="alert alert-success mb-4" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $isDraft ? 'Η δήλωση αποθηκεύτηκε ως πρόχειρο.' : 'Η δήλωση υποβλήθηκε με επιτυχία!'; ?>
                    Μπορείτε να δείτε όλες τις υποβολές στη
                    <a href="../submitModule/my_submissions.php" class="alert-link">λίστα δηλώσεών σας</a>.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Παρακαλώ διορθώστε τα παρακάτω:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="submit.php<?php echo isset($_GET['year']) ? '?year=' . htmlspecialchars($_GET['year']) : ''; ?>" method="POST">

                <div class="row g-3">

                    <div class="col-12 col-md-6">
                        <label for="submission_year" class="form-label">Έτος Υποβολής <span style="color:#c0392b">*</span></label>
                        <?php if (isset($_GET['year']) && $_GET['year'] !== ''): ?>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo htmlspecialchars($submission_year); ?>"
                                disabled
                            >
                            <input type="hidden" name="submission_year" value="<?php echo htmlspecialchars($submission_year); ?>">
                        <?php else: ?>
                            <input
                                type="number"
                                class="form-control"
                                id="submission_year"
                                name="submission_year"
                                value="<?php echo htmlspecialchars($submission_year); ?>"
                                placeholder="π.χ. <?php echo date('Y'); ?>"
                                min="2000"
                                max="<?php echo (int)date('Y'); ?>"
                            >
                            <div class="form-text">Εισάγετε το έτος στο οποίο αναφέρεται η δήλωση.</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="category" class="form-label">Κατηγορία <span style="color:#c0392b">*</span></label>
                        <select class="form-select" id="category" name="category">
                            <option value="">— Επιλέξτε κατηγορία —</option>
                            <?php foreach ($allowed_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="amount" class="form-label">Ποσό (€) <span style="color:#c0392b">*</span></label>
                        <input
                            type="number"
                            class="form-control"
                            id="amount"
                            name="amount"
                            value="<?php echo htmlspecialchars($amount); ?>"
                            placeholder="π.χ. 15000.00"
                            min="0"
                            step="0.01"
                        >
                        <div class="form-text">Εισάγετε το ποσό σε Ευρώ (€).</div>
                    </div>

                    <div class="col-12">
                        <label for="note" class="form-label">Σημειώσεις <span style="color:var(--text-muted); font-weight:400;">(προαιρετικό)</span></label>
                        <textarea
                            class="form-control"
                            id="note"
                            name="note"
                            rows="4"
                            placeholder="Προσθέστε τυχόν σχόλια ή διευκρινίσεις για τη δήλωση..."
                        ><?php echo htmlspecialchars($note); ?></textarea>
                    </div>

                </div>

                <div class="d-grid d-md-flex gap-2 mt-4">
                    <button type="submit" name="save_submit" class="btn btn-primary px-5">
                        <i class="bi bi-send me-2"></i>Υποβολή Δήλωσης
                    </button>
                    <button type="submit" name="save_draft" class="btn btn-outline-secondary px-5">
                        <i class="bi bi-floppy me-2"></i>Αποθήκευση Πρόχειρου
                    </button>
                    <a href="../submitModule/my_submissions.php" class="btn btn-outline-danger px-4">
                        <i class="bi bi-x me-1"></i>Ακύρωση
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html> 
