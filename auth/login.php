<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

require_once '../includes/db.php';

// Αν ο χρήστης είναι ήδη συνδεδεμένος, δεν πρέπει να βλέπει τη login page.
if (isset($_SESSION['user_id'])) {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

$error = '';
$email = '';

$registered = isset($_GET['registered']) && $_GET['registered'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Λανθασμένα στοιχεία σύνδεσης.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :e');
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['username'] = $user['username'];

            $officialStmt = $pdo->prepare('
                SELECT id
                FROM officials
                WHERE user_id = :user_id
                LIMIT 1
            ');
            $officialStmt->execute([
                ':user_id' => $user['id'],
            ]);

            $official = $officialStmt->fetch();
            $_SESSION['official_id'] = $official ? $official['id'] : null;

            header('Location: ../searchModule/dashboard.php');
            exit;
        } else {
            $error = 'Λανθασμένα στοιχεία σύνδεσης.';
        }
    }
}

$pageTitle = 'Σύνδεση';
require_once '../includes/header.php';
?>

<script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                <div class="login-card">

                    <div class="auth-card-header">
                        <div class="auth-logo">⚖</div>
                        <h1>Σύνδεση</h1>
                        <p>Εισέλθετε στο σύστημα παρακολούθησης</p>
                    </div>

                    <div class="auth-card-body">

                        <?php if ($registered): ?>
                            <script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Επιτυχής Εγγραφή',
                                    text: 'Η εγγραφή ολοκληρώθηκε με επιτυχία. Μπορείτε τώρα να συνδεθείτε.',
                                    confirmButtonText: 'Εντάξει',
                                    confirmButtonColor: '#1a2f5a'
                                });
                            </script>
                        <?php endif; ?>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
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

                            <div class="mb-4">
                                <label for="password" class="form-label">Κωδικός Πρόσβασης</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="Εισάγετε τον κωδικό σας"
                                    autocomplete="current-password"
                                >
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Σύνδεση
                                </button>
                            </div>

                        </form>

                        <hr class="my-4" style="border-color: var(--border);">

                        <p class="text-center mb-0" style="font-size:0.88rem; color: var(--text-muted);">
                            Δεν έχετε λογαριασμό;
                            <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--navy);">Εγγραφείτε εδώ</a>
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
