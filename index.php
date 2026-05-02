<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: searchModule/dashboard.php');
    exit;
}

$pageTitle = 'Αρχική';
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Αρχική – Πόθεν Έσχες</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg pe-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="brand-emblem">⚖</span>
            <span>
                Πόθεν Έσχες
                <span class="brand-sub">Κυπριακή Δημοκρατία</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="searchModule/list.php">
                        <i class="bi bi-list-ul me-1"></i>Δηλώσεις
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="auth/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Σύνδεση
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="auth/register.php">
                        <i class="bi bi-person-plus me-1"></i>Εγγραφή
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">

    <div class="list-card">

        <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1><i class="bi bi-bank me-2" style="color:var(--gold);"></i>Εφαρμογή Παρακολούθησης Πόθεν Έσχες</h1>
                <p class="mb-0 results-count">Δημόσια αναζήτηση και προβολή δηλώσεων αξιωματούχων</p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="auth/login.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Σύνδεση
                </a>
                <a href="auth/register.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Εγγραφή
                </a>
            </div>
        </div>

        <div class="auth-card-body" style="padding: 2rem;">

            <p class="section-title">Δημόσιες Λειτουργίες</p>

            <div class="row g-3 mb-4">

                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="searchModule/list.php" class="module-card">
                        <div class="card-icon">📋</div>
                        <h3>Δηλώσεις</h3>
                        <p>Αναζήτηση και προβολή δημοσιευμένων δηλώσεων Πόθεν Έσχες αξιωματούχων</p>
                    </a>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="searchModule/statistics.php" class="module-card">
                        <div class="card-icon gold-icon">📊</div>
                        <h3>Στατιστικά</h3>
                        <p>Συγκεντρωτικά στοιχεία δηλώσεων ανά κόμμα, έτος και κατηγορία</p>
                    </a>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="auth/register.php" class="module-card">
                        <div class="card-icon">👤</div>
                        <h3>Εγγραφή</h3>
                        <p>Δημιουργία απλού λογαριασμού χρήστη για πρόσβαση στο σύστημα</p>
                    </a>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="auth/login.php" class="module-card">
                        <div class="card-icon gold-icon">🔐</div>
                        <h3>Σύνδεση</h3>
                        <p>Σύνδεση για εγγεγραμμένους χρήστες, αξιωματούχους και διαχειριστές</p>
                    </a>
                </div>

            </div>

            <p class="section-title">Σχετικά με το Σύστημα</p>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="module-card" style="pointer-events:none;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #2e6b3e, #3d8e52);">🏛</div>
                        <h3>Δημόσια Πληροφόρηση</h3>
                        <p>Το σύστημα επιτρέπει στο κοινό να αναζητά στοιχεία δηλώσεων Πόθεν Έσχες με κατάλληλα φίλτρα.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="module-card" style="pointer-events:none;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #5a3e1a, #8a6230);">🔒</div>
                        <h3>Ελεγχόμενη Υποβολή</h3>
                        <p>Η υποβολή νέας δήλωσης επιτρέπεται μόνο σε λογαριασμούς αξιωματούχων.</p>
                    </div>
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