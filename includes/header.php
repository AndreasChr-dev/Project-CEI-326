<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – Πόθεν Έσχες' : 'Πόθεν Έσχες Κυπριακής Δημοκρατίας'; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg pe-navbar">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../searchModule/dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../searchModule/list.php">
                            <i class="bi bi-list-ul me-1"></i>Δηλώσεις
                        </a>
                    </li>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'official'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../submitModule/my_submissions.php">
                                <i class="bi bi-file-text me-1"></i>Οι Δηλώσεις μου
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../submitModule/submit.php">
                                <i class="bi bi-plus-circle me-1"></i>Νέα Υποβολή
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../submitModule/profile.php">
                                <i class="bi bi-person-circle me-1"></i>Προφίλ
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item ms-lg-2">
                        <span class="user-info">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <span class="role-badge"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        </span>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Αποσύνδεση
                        </a>
                    </li>
                    
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../searchModule/list.php">
                            <i class="bi bi-list-ul me-1"></i>Δηλώσεις
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../searchModule/statistics.php">
                            <i class="bi bi-bar-chart me-1"></i>Στατιστικά
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Σύνδεση
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/register.php">
                            <i class="bi bi-person-plus me-1"></i>Εγγραφή
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 
