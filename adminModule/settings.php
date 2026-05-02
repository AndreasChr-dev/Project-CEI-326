<?php
session_start();
require_once '../includes/db.php';

// Session guard — μόνο admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../searchModule/dashboard.php');
    exit;
}

$errors  = [];
$success = '';

// -- ΔΙΑΓΡΑΦΗ ΚΟΜΜΑΤΟΣ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_party'])) {
    $partyId = (int)($_POST['delete_party'] ?? 0);

    if ($partyId > 0) {
        $stmt = $pdo->prepare('DELETE FROM parties WHERE id = :id');
        $stmt->execute([':id' => $partyId]);
        $success = 'Το κόμμα διαγράφηκε επιτυχώς.';
    }
}

// -- ΔΙΑΓΡΑΦΗ ΘΕΣΗΣ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_position'])) {
    $positionId = (int)($_POST['delete_position'] ?? 0);

    if ($positionId > 0) {
        $stmt = $pdo->prepare('DELETE FROM positions WHERE id = :id');
        $stmt->execute([':id' => $positionId]);
        $success = 'Η θέση διαγράφηκε επιτυχώς.';
    }
}

// -- ΠΡΟΣΘΗΚΗ ΚΟΜΜΑΤΟΣ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_party'])) {
    $partyName  = trim($_POST['party_name']  ?? '');
    $partyShort = trim($_POST['party_short'] ?? '');

    if ($partyName === '') {
        $errors[] = 'Το όνομα κόμματος είναι υποχρεωτικό.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM parties WHERE name = :name');
        $stmt->execute([':name' => $partyName]);
        if ($stmt->fetch()) {
            $errors[] = 'Υπάρχει ήδη κόμμα με αυτό το όνομα.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO parties (name, short_name)
            VALUES (:name, :short)
        ');
        $stmt->execute([
            ':name'  => $partyName,
            ':short' => $partyShort !== '' ? $partyShort : null,
        ]);
        $success = 'Το κόμμα προστέθηκε επιτυχώς.';
    }
}

// -- ΠΡΟΣΘΗΚΗ ΘΕΣΗΣ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_position'])) {
    $positionTitle = trim($_POST['position_title'] ?? '');

    if ($positionTitle === '') {
        $errors[] = 'Ο τίτλος θέσης είναι υποχρεωτικός.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM positions WHERE title = :title');
        $stmt->execute([':title' => $positionTitle]);
        if ($stmt->fetch()) {
            $errors[] = 'Υπάρχει ήδη θέση με αυτό τον τίτλο.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO positions (title)
            VALUES (:title)
        ');
        $stmt->execute([':title' => $positionTitle]);
        $success = 'Η θέση προστέθηκε επιτυχώς.';
    }
}

// -- ΦΟΡΤΩΣΗ ΔΕΔΟΜΕΝΩΝ --
$stmt = $pdo->prepare('
    SELECT p.*, COUNT(o.id) AS official_count
    FROM parties p
    LEFT JOIN officials o ON o.party_id = p.id
    GROUP BY p.id
    ORDER BY p.name ASC
');
$stmt->execute([]);
$parties = $stmt->fetchAll();

$stmt = $pdo->prepare('
    SELECT pos.*, COUNT(o.id) AS official_count
    FROM positions pos
    LEFT JOIN officials o ON o.position_id = pos.id
    GROUP BY pos.id
    ORDER BY pos.title ASC
');
$stmt->execute([]);
$positions = $stmt->fetchAll();

$pageTitle = 'Ρυθμίσεις Συστήματος';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <!-- Header -->
    <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
         style="background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem 1.75rem; box-shadow:var(--shadow-sm);">
        <div>
            <h1><i class="bi bi-gear me-2" style="color:var(--gold);"></i>Ρυθμίσεις Συστήματος</h1>
            <p class="mb-0 results-count">Διαχείριση κομμάτων και θέσεων αξιωματούχων</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="../searchModule/dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
        </div>
    </div>

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

    <div class="row g-4">

        <!--  ΚΟΜΜΑΤΑ  -->
        <div class="col-12 col-lg-6">

            <p class="section-title">Κόμματα</p>

            <!-- Φόρμα προσθήκης -->
            <div class="list-card mb-3">
                <div class="list-card-header">
                    <h1 style="font-size:1rem;"><i class="bi bi-plus-circle me-2" style="color:var(--gold);"></i>Νέο Κόμμα</h1>
                </div>
                <div style="padding:1.25rem 1.75rem;">
                    <form action="settings.php" method="POST">
                        <div class="row g-2">
                            <div class="col-12 col-sm-7">
                                <label for="party_name" class="form-label">Πλήρες Όνομα <span style="color:#c0392b">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="party_name"
                                    name="party_name"
                                    placeholder="π.χ. Δημοκρατικός Συναγερμός"
                                >
                            </div>
                            <div class="col-12 col-sm-5">
                                <label for="party_short" class="form-label">Συντομογραφία</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="party_short"
                                    name="party_short"
                                    placeholder="π.χ. ΔΗΣΥ"
                                >
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="add_party" class="btn btn-primary px-4">
                                <i class="bi bi-plus me-1"></i>Προσθήκη
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Λίστα κομμάτων -->
            <div class="list-card">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th><i class="bi bi-diagram-3 me-1"></i>Κόμμα</th>
                                <th><i class="bi bi-people me-1"></i>Αξιωματούχοι</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parties as $party): ?>
                                <tr>
                                    <td>
                                        <span class="party-badge"><?php echo htmlspecialchars($party['short_name'] ?? '—'); ?></span>
                                        <span class="ms-2" style="font-size:0.82rem;">
                                            <?php echo htmlspecialchars($party['name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($party['official_count']); ?></td>
                                    <td>
                                        <?php if ((int)$party['official_count'] === 0): ?>
                                            <form action="settings.php" method="POST">
                                                <input type="hidden" name="delete_party" value="<?php echo (int)$party['id']; ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="Swal.fire({title:'Επιβεβαίωση',text:'Διαγραφή κόμματος «<?php echo htmlspecialchars($party['name'], ENT_QUOTES); ?>»;',icon:'warning',showCancelButton:true,confirmButtonText:'Εντάξει',cancelButtonText:'Ακύρωση',confirmButtonColor:'#1a2f5a'}).then(r=>{if(r.isConfirmed)this.closest('form').submit();})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.76rem;">Σε χρήση</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!--  ΘΕΣΕΙΣ  -->
        <div class="col-12 col-lg-6">

            <p class="section-title">Θέσεις Αξιωματούχων</p>

            <!-- Φόρμα προσθήκης -->
            <div class="list-card mb-3">
                <div class="list-card-header">
                    <h1 style="font-size:1rem;"><i class="bi bi-plus-circle me-2" style="color:var(--gold);"></i>Νέα Θέση</h1>
                </div>
                <div style="padding:1.25rem 1.75rem;">
                    <form action="settings.php" method="POST">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="position_title" class="form-label">Τίτλος Θέσης <span style="color:#c0392b">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="position_title"
                                    name="position_title"
                                    placeholder="π.χ. Βουλευτής"
                                >
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="add_position" class="btn btn-primary px-4">
                                <i class="bi bi-plus me-1"></i>Προσθήκη
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Λίστα θέσεων -->
            <div class="list-card">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th><i class="bi bi-briefcase me-1"></i>Θέση</th>
                                <th><i class="bi bi-people me-1"></i>Αξιωματούχοι</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($positions as $pos): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pos['title']); ?></td>
                                    <td><?php echo htmlspecialchars($pos['official_count']); ?></td>
                                    <td>
                                        <?php if ((int)$pos['official_count'] === 0): ?>
                                            <form action="settings.php" method="POST">
                                                <input type="hidden" name="delete_position" value="<?php echo (int)$pos['id']; ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="Swal.fire({title:'Επιβεβαίωση',text:'Διαγραφή θέσης «<?php echo htmlspecialchars($pos['title'], ENT_QUOTES); ?>»;',icon:'warning',showCancelButton:true,confirmButtonText:'Εντάξει',cancelButtonText:'Ακύρωση',confirmButtonColor:'#1a2f5a'}).then(r=>{if(r.isConfirmed)this.closest('form').submit();})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.76rem;">Σε χρήση</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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