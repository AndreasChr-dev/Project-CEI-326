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

$errors  = [];
$success = false;

// Φόρτωση στοιχείων official
$stmt = $pdo->prepare('
    SELECT o.*, u.email
    FROM officials o
    JOIN users u ON u.id = o.user_id
    WHERE o.user_id = :uid
    LIMIT 1
');
$stmt->execute([':uid' => $_SESSION['user_id']]);
$official = $stmt->fetch();

if (!$official) {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

// Αρχικοποίηση πεδίων
$first_name = $official['first_name'];
$last_name  = $official['last_name'];
$phone      = $official['phone'] ?? '';

// ── ΑΛΛΑΓΗ ΣΤΟΙΧΕΙΩΝ ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');

    if ($first_name === '') {
        $errors[] = 'Το όνομα είναι υποχρεωτικό.';
    }

    if ($last_name === '') {
        $errors[] = 'Το επίθετο είναι υποχρεωτικό.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            UPDATE officials
            SET first_name = :first_name,
                last_name  = :last_name,
                phone      = :phone
            WHERE user_id  = :uid
        ');
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name'  => $last_name,
            ':phone'      => $phone !== '' ? $phone : null,
            ':uid'        => $_SESSION['user_id'],
        ]);

        $success = true;
    }
}

// ── ΑΛΛΑΓΗ ΚΩΔΙΚΟΥ ────────────────────────────────────────────────
$pwErrors  = [];
$pwSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '') {
        $pwErrors[] = 'Εισάγετε τον τρέχοντα κωδικό σας.';
    }

    if (strlen($newPassword) < 8) {
        $pwErrors[] = 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    if ($newPassword !== $confirmPassword) {
        $pwErrors[] = 'Οι νέοι κωδικοί δεν ταιριάζουν.';
    }

    if (empty($pwErrors)) {
        // Επαλήθευση τρέχοντος κωδικού
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :uid');
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $pwErrors[] = 'Ο τρέχων κωδικός είναι λανθασμένος.';
        }
    }

    if (empty($pwErrors)) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :uid');
        $stmt->execute([
            ':hash' => $newHash,
            ':uid'  => $_SESSION['user_id'],
        ]);

        $pwSuccess = true;
    }
}

$pageTitle = 'Το Προφίλ μου';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <!-- Header -->
    <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
         style="background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem 1.75rem; box-shadow:var(--shadow-sm);">
        <div>
            <h1><i class="bi bi-person-circle me-2" style="color:var(--gold);"></i>Το Προφίλ μου</h1>
            <p class="mb-0 results-count">Διαχείριση προσωπικών στοιχείων</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="../submitModule/my_submissions.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-file-text me-1"></i>Οι Δηλώσεις μου
            </a>
            <a href="../searchModule/dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── ΣΤΟΙΧΕΙΑ ΠΡΟΦΙΛ ─────────────────────────────────── -->
        <div class="col-12 col-lg-6">

            <p class="section-title">Προσωπικά Στοιχεία</p>

            <div class="list-card">
                <div class="list-card-header">
                    <h1 style="font-size:1rem;"><i class="bi bi-pencil me-2" style="color:var(--gold);"></i>Τροποποίηση Στοιχείων</h1>
                </div>
                <div style="padding:1.5rem 1.75rem;">

                    <?php if ($success): ?>
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Επιτυχία',
                                text: 'Τα στοιχεία σας ενημερώθηκαν.',
                                confirmButtonText: 'Εντάξει',
                                confirmButtonColor: '#1a2f5a'
                            });
                        </script>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4" role="alert">
                            <strong><i class="bi bi-exclamation-triangle me-2"></i>Σφάλμα:</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label">Email <span style="color:var(--text-muted); font-weight:400;">(δεν τροποποιείται)</span></label>
                            <input
                                type="email"
                                class="form-control"
                                value="<?php echo htmlspecialchars($official['email']); ?>"
                                disabled
                            >
                        </div>

                        <div class="mb-3">
                            <label for="first_name" class="form-label">Όνομα <span style="color:#c0392b">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="first_name"
                                name="first_name"
                                value="<?php echo htmlspecialchars($first_name); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Επίθετο <span style="color:#c0392b">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="last_name"
                                name="last_name"
                                value="<?php echo htmlspecialchars($last_name); ?>"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label">Τηλέφωνο</label>
                            <input
                                type="text"
                                class="form-control"
                                id="phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($phone); ?>"
                                placeholder="π.χ. 99123456"
                            >
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i>Αποθήκευση
                        </button>

                    </form>
                </div>
            </div>
        </div>

        <!-- ── ΑΛΛΑΓΗ ΚΩΔΙΚΟΥ ─────────────────────────────────── -->
        <div class="col-12 col-lg-6">

            <p class="section-title">Αλλαγή Κωδικού</p>

            <div class="list-card">
                <div class="list-card-header">
                    <h1 style="font-size:1rem;"><i class="bi bi-lock me-2" style="color:var(--gold);"></i>Νέος Κωδικός Πρόσβασης</h1>
                </div>
                <div style="padding:1.5rem 1.75rem;">

                    <?php if ($pwSuccess): ?>
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Επιτυχία',
                                text: 'Ο κωδικός σας άλλαξε επιτυχώς.',
                                confirmButtonText: 'Εντάξει',
                                confirmButtonColor: '#1a2f5a'
                            });
                        </script>
                    <?php endif; ?>

                    <?php if (!empty($pwErrors)): ?>
                        <div class="alert alert-danger mb-4" role="alert">
                            <strong><i class="bi bi-exclamation-triangle me-2"></i>Σφάλμα:</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <?php foreach ($pwErrors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Τρέχων Κωδικός <span style="color:#c0392b">*</span></label>
                            <input
                                type="password"
                                class="form-control"
                                id="current_password"
                                name="current_password"
                                placeholder="Εισάγετε τον τρέχοντα κωδικό"
                                autocomplete="current-password"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Νέος Κωδικός <span style="color:#c0392b">*</span></label>
                            <input
                                type="password"
                                class="form-control"
                                id="new_password"
                                name="new_password"
                                placeholder="Τουλάχιστον 8 χαρακτήρες"
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Επιβεβαίωση Νέου Κωδικού <span style="color:#c0392b">*</span></label>
                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Επαναλάβετε τον νέο κωδικό"
                                autocomplete="new-password"
                            >
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary px-4">
                            <i class="bi bi-shield-lock me-2"></i>Αλλαγή Κωδικού
                        </button>

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>
