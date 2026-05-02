<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Dashboard';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <div class="user-info-banner">
        <div class="avatar">👤</div>
        <div class="flex-grow-1">
            <p class="user-name mb-0"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p class="user-role-label mb-0">Συνδεδεμένος χρήστης</p>
        </div>
        <span class="role-badge"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
    </div>

    <p class="section-title">Γρήγορες Ενέργειες</p>

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../searchModule/list.php" class="module-card">
                <div class="card-icon">📋</div>
                <h3>Δηλώσεις</h3>
                <p>Αναζήτηση και προβολή δηλώσεων πόθεν έσχες αξιωματούχων</p>
            </a>
        </div>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'official'): ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../submitModule/profile.php" class="module-card">
                <div class="card-icon">👤</div>
                <h3>Προφίλ</h3>
                <p>Προβολή και τροποποίηση των προσωπικών σας στοιχείων</p>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../submitModule/my_submissions.php" class="module-card">
                <div class="card-icon">📝</div>
                <h3>Οι Δηλώσεις μου</h3>
                <p>Προβολή και υποβολή των δηλώσεων Πόθεν Έσχες σας</p>
            </a>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../adminModule/manage_submissions.php" class="module-card">
                <div class="card-icon">📁</div>
                <h3>Διαχείρηση Δηλώσεων</h3>
                <p>Προβολή και διαχείριση όλων των υποβολών δηλώσεων</p>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../adminModule/users.php" class="module-card">
                <div class="card-icon">👥</div>
                <h3>Διαχείριση Χρηστών</h3>
                <p>Προβολή και διαχείριση εγγεγραμμένων χρηστών</p>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../adminModule/settings.php" class="module-card">
                <div class="card-icon gold-icon">⚙</div>
                <h3>Ρυθμίσεις Συστήματος</h3>
                <p>Διαχείριση κομμάτων, θέσεων και παραμέτρων συστήματος</p>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../adminModule/reports.php" class="module-card">
                <div class="card-icon gold-icon">📊</div>
                <h3>Αναφορές</h3>
                <p>Στατιστικά και αναφορές υποβολών ανά έτος και κόμμα</p>
            </a>
        </div>
        <?php else: ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="../searchModule/statistics.php" class="module-card">
                <div class="card-icon gold-icon">📊</div>
                <h3>Στατιστικά</h3>
                <p>Δείτε συγκεντρωτικά στοιχεία δηλώσεων ανά κόμμα και έτος</p>
            </a>
        </div>
        <?php endif; ?>

    </div>

    <p class="section-title">Σχετικά με το Σύστημα</p>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="module-card" style="pointer-events:none;">
                <div class="card-icon" style="background: linear-gradient(135deg, #2e6b3e, #3d8e52);">🏛</div>
                <h3>Βουλή Αντιπροσώπων</h3>
                <p>Τα δεδομένα αντλούνται από τη Βουλή των Αντιπροσώπων της Κυπριακής Δημοκρατίας σύμφωνα με τον νόμο περί Πόθεν Έσχες.</p>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="module-card" style="pointer-events:none;">
                <div class="card-icon" style="background: linear-gradient(135deg, #5a3e1a, #8a6230);">🔒</div>
                <h3>Ασφάλεια &amp; Προστασία</h3>
                <p>Το σύστημα χρησιμοποιεί κρυπτογράφηση κωδικών και ασφαλείς συνδέσεις για την προστασία των δεδομένων.</p>
            </div>
        </div>
    </div>

</div>


<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>
