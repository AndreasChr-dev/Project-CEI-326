<?php
require_once '../includes/db.php';

$errors   = [];
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '') {
        $errors[] = 'Το όνομα χρήστη είναι υποχρεωτικό.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Μη έγκυρη διεύθυνση email.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Οι κωδικοί δεν ταιριάζουν.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);

        if ($stmt->fetch()) {
            $errors[] = 'Το όνομα χρήστη χρησιμοποιείται ήδη.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->execute([':e' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Αυτό το email χρησιμοποιείται ήδη.';
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role)
             VALUES (:u, :e, :p, :r)'
        );

        $stmt->execute([
            ':u' => $username,
            ':e' => $email,
            ':p' => $passwordHash,
            ':r' => 'user',
        ]);

        header('Location: login.php?registered=1');
        exit;
    }
}

$pageTitle = 'Εγγραφή';
require_once '../includes/header.php';
?>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                <div class="register-card">

                    <div class="auth-card-header">
                        <div class="auth-logo">👤</div>
                        <h1>Δημιουργία Λογαριασμού</h1>
                        <p>Εγγραφείτε για πρόσβαση στο σύστημα</p>
                    </div>

                    <div class="auth-card-body">

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

                        <form action="register.php" method="POST">

                            <div class="mb-3">
                                <label for="username" class="form-label">Όνομα Χρήστη <span style="color:#c0392b">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="username"
                                    name="username"
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    placeholder="π.χ. user123"
                                    autocomplete="username"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span style="color:#c0392b">*</span></label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    placeholder="user@example.com"
                                    autocomplete="email"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Κωδικός Πρόσβασης <span style="color:#c0392b">*</span></label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="Τουλάχιστον 8 χαρακτήρες"
                                    autocomplete="new-password"
                                >
                                <div class="form-text">Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.</div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm" class="form-label">Επιβεβαίωση Κωδικού <span style="color:#c0392b">*</span></label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="confirm"
                                    name="confirm"
                                    placeholder="Επαναλάβετε τον κωδικό"
                                    autocomplete="new-password"
                                >
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Εγγραφή
                                </button>
                            </div>

                        </form>

                        <hr class="my-4" style="border-color: var(--border);">

                        <p class="text-center mb-0" style="font-size:0.88rem; color: var(--text-muted);">
                            Έχετε ήδη λογαριασμό;
                            <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--navy);">Συνδεθείτε εδώ</a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<footer class="pe-footer">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>
