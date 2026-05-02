<?php
session_start();
require_once '../includes/db.php';

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

$stmt = $pdo->prepare('SELECT id, name, short_name FROM parties ORDER BY name ASC');
$stmt->execute([]);
$parties = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, title FROM positions ORDER BY title ASC');
$stmt->execute([]);
$positions = $stmt->fetchAll();

// -- ΔΙΑΓΡΑΦΗ ΧΡΗΣΤΗ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = (int)($_POST['delete_user'] ?? 0);

    if ($userId === (int)$_SESSION['user_id']) {
        $errors[] = 'Δεν μπορείτε να διαγράψετε τον δικό σας λογαριασμό.';
    } elseif ($userId > 0) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $success = 'Ο χρήστης διαγράφηκε επιτυχώς.';
    }
}

// -- ΕΝΗΜΕΡΩΣΗ OFFICIAL --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_official'])) {
    $userId     = (int)($_POST['user_id']     ?? 0);
    $partyId    = (isset($_POST['party_id'])    && $_POST['party_id']    !== '') ? (int)$_POST['party_id']    : null;
    $positionId = (isset($_POST['position_id']) && $_POST['position_id'] !== '') ? (int)$_POST['position_id'] : null;
    $district   = trim($_POST['district'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM officials WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare('
            UPDATE officials
            SET party_id    = :party_id,
                position_id = :position_id,
                district    = :district
            WHERE user_id   = :uid
        ');
        $stmt->execute([
            ':party_id'    => $partyId,
            ':position_id' => $positionId,
            ':district'    => $district !== '' ? $district : null,
            ':uid'         => $userId,
        ]);
        $success = 'Τα στοιχεία του αξιωματούχου ενημερώθηκαν επιτυχώς.';
    }
}

// -- ΠΡΟΣΘΗΚΗ ΧΡΗΣΤΗ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username   = trim($_POST['new_username']   ?? '');
    $email      = trim($_POST['new_email']      ?? '');
    $password   = $_POST['new_password']        ?? '';
    $role       = trim($_POST['new_role']       ?? 'official');
    $firstName  = trim($_POST['new_first_name'] ?? '');
    $lastName   = trim($_POST['new_last_name']  ?? '');
    $phone      = trim($_POST['new_phone']      ?? '');
    $district   = trim($_POST['new_district']   ?? '');
    $partyId    = (isset($_POST['new_party_id'])    && $_POST['new_party_id']    !== '') ? (int)$_POST['new_party_id']    : null;
    $positionId = (isset($_POST['new_position_id']) && $_POST['new_position_id'] !== '') ? (int)$_POST['new_position_id'] : null;

    $allowedRoles = ['admin', 'official'];

    if ($username === '') $errors[] = 'Το username είναι υποχρεωτικό.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Μη έγκυρη διεύθυνση email.';
    if (strlen($password) < 8) $errors[] = 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    if (!in_array($role, $allowedRoles, true)) $errors[] = 'Μη έγκυρος ρόλος.';
    if ($role === 'official' && ($firstName === '' || $lastName === '')) {
        $errors[] = 'Για official απαιτείται όνομα και επίθετο.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        if ($stmt->fetch()) $errors[] = 'Το username χρησιμοποιείται ήδη.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->execute([':e' => $email]);
        if ($stmt->fetch()) $errors[] = 'Το email χρησιμοποιείται ήδη.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :p, :r)');
        $stmt->execute([':u' => $username, ':e' => $email, ':p' => $passwordHash, ':r' => $role]);
        $newUserId = (int)$pdo->lastInsertId();

        if ($role === 'official') {
            $stmt = $pdo->prepare('
                INSERT INTO officials (user_id, first_name, last_name, phone, district, party_id, position_id)
                VALUES (:uid, :fn, :ln, :phone, :district, :party_id, :position_id)
            ');
            $stmt->execute([
                ':uid'         => $newUserId,
                ':fn'          => $firstName,
                ':ln'          => $lastName,
                ':phone'       => $phone !== '' ? $phone : null,
                ':district'    => $district !== '' ? $district : null,
                ':party_id'    => $partyId,
                ':position_id' => $positionId,
            ]);
        }

        $success = 'Ο χρήστης προστέθηκε επιτυχώς.';
    }
}

// -- ΦΟΡΤΩΣΗ ΧΡΗΣΤΩΝ --
$stmt = $pdo->prepare('
    SELECT
        u.id, u.username, u.email, u.role, u.created_at,
        o.id         AS official_id,
        o.first_name, o.last_name, o.district,
        o.party_id, o.position_id,
        p.short_name AS party_short,
        pos.title    AS position_title
    FROM users u
    LEFT JOIN officials o   ON o.user_id = u.id
    LEFT JOIN parties p     ON p.id      = o.party_id
    LEFT JOIN positions pos ON pos.id    = o.position_id
    ORDER BY u.created_at DESC
');
$stmt->execute([]);
$users = $stmt->fetchAll();

$pageTitle = 'Διαχείριση Χρηστών';
require_once '../includes/header.php';
?>

<div class="container py-4">

    <div class="list-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
         style="background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem 1.75rem; box-shadow:var(--shadow-sm);">
        <div>
            <h1><i class="bi bi-people me-2" style="color:var(--gold);"></i>Διαχείριση Χρηστών</h1>
            <p class="mb-0 results-count">Σύνολο <?php echo count($users); ?> εγγεγραμμένων χρηστών</p>
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

    <!-- ΠΡΟΣΘΗΚΗ ΧΡΗΣΤΗ  -->
    <p class="section-title">Προσθήκη Νέου Χρήστη</p>
    <div class="list-card mb-4">
        <div class="list-card-header">
            <h1 style="font-size:1rem;"><i class="bi bi-person-plus me-2" style="color:var(--gold);"></i>Νέος Χρήστης</h1>
        </div>
        <div style="padding:1.5rem 1.75rem;">
            <form action="users.php" method="POST">
                <p class="section-title" style="font-size:0.68rem;">Στοιχεία Λογαριασμού</p>
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-3">
                        <label for="new_username" class="form-label">Username <span style="color:#c0392b">*</span></label>
                        <input type="text" class="form-control" id="new_username" name="new_username" placeholder="π.χ. user123">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="new_email" class="form-label">Email <span style="color:#c0392b">*</span></label>
                        <input type="email" class="form-control" id="new_email" name="new_email" placeholder="user@example.com">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="new_password" class="form-label">Κωδικός <span style="color:#c0392b">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Τουλάχιστον 8 χαρακτήρες">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="new_role" class="form-label">Ρόλος <span style="color:#c0392b">*</span></label>
                        <select class="form-select" id="new_role" name="new_role" onchange="toggleOfficialFields(this.value)">
                            <option value="official">official</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                </div>

                <div id="official_fields" style="display:block;">
                    <p class="section-title" style="font-size:0.68rem; margin-top:1rem;">Στοιχεία Αξιωματούχου</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label for="new_first_name" class="form-label">Όνομα <span style="color:#c0392b">*</span></label>
                            <input type="text" class="form-control" id="new_first_name" name="new_first_name" placeholder="π.χ. Γιάννης">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="new_last_name" class="form-label">Επίθετο <span style="color:#c0392b">*</span></label>
                            <input type="text" class="form-control" id="new_last_name" name="new_last_name" placeholder="π.χ. Παπαδόπουλος">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="new_phone" class="form-label">Τηλέφωνο</label>
                            <input type="text" class="form-control" id="new_phone" name="new_phone" placeholder="π.χ. 99123456">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="new_district" class="form-label">Επαρχία</label>
                            <input type="text" class="form-control" id="new_district" name="new_district" placeholder="π.χ. Λευκωσία">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="new_party_id" class="form-label">Κόμμα</label>
                            <select class="form-select" id="new_party_id" name="new_party_id">
                                <option value="">— Επιλέξτε —</option>
                                <?php foreach ($parties as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>">
                                        <?php echo htmlspecialchars($p['short_name'] . ' – ' . $p['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="new_position_id" class="form-label">Θέση</label>
                            <select class="form-select" id="new_position_id" name="new_position_id">
                                <option value="">— Επιλέξτε —</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo (int)$pos['id']; ?>">
                                        <?php echo htmlspecialchars($pos['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" name="add_user" class="btn btn-primary px-4">
                        <i class="bi bi-plus me-1"></i>Προσθήκη
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ΛΙΣΤΑ ΧΡΗΣΤΩΝ -->
    <p class="section-title">Εγγεγραμμένοι Χρήστες</p>
    <div class="list-card">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash me-1"></i>ID</th>
                        <th><i class="bi bi-person me-1"></i>Username</th>
                        <th><i class="bi bi-envelope me-1"></i>Email</th>
                        <th><i class="bi bi-shield me-1"></i>Ρόλος</th>
                        <th><i class="bi bi-person-badge me-1"></i>Αξιωματούχος</th>
                        <th><i class="bi bi-diagram-3 me-1"></i>Κόμμα</th>
                        <th><i class="bi bi-briefcase me-1"></i>Θέση</th>
                        <th><i class="bi bi-geo-alt me-1"></i>Επαρχία</th>
                        <th><i class="bi bi-calendar me-1"></i>Εγγραφή</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php
                                $roleClass = match($user['role']) {
                                    'admin'    => 'bg-danger text-white',
                                    'official' => 'bg-warning text-dark',
                                    default    => 'bg-secondary text-white',
                                };
                                ?>
                                <span class="badge <?php echo $roleClass; ?> rounded-pill px-3">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['first_name'] && $user['role'] === 'official'): ?>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['party_short'] && $user['role'] === 'official'): ?>
                                    <span class="party-badge"><?php echo htmlspecialchars($user['party_short']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['position_title'] && $user['role'] === 'official'): ?>
                                    <?php echo htmlspecialchars($user['position_title']); ?>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['district'] && $user['role'] === 'official'): ?>
                                    <?php echo htmlspecialchars($user['district']); ?>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.8rem; color:var(--text-muted);">
                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($user['created_at']))); ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($user['role'] === 'official'): ?>
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            title="Επεξεργασία"
                                            onclick="openEditModal(
                                                <?php echo (int)$user['id']; ?>,
                                                '<?php echo htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES); ?>',
                                                <?php echo $user['party_id']    ? (int)$user['party_id']    : 'null'; ?>,
                                                <?php echo $user['position_id'] ? (int)$user['position_id'] : 'null'; ?>,
                                                '<?php echo htmlspecialchars($user['district'] ?? '', ENT_QUOTES); ?>'
                                            )">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            title="Διαθέσιμο μόνο για officials"
                                            disabled>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                        <form action="users.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_user" value="<?php echo (int)$user['id']; ?>">
                                            <button type="button" class="btn btn-outline-danger btn-sm" title="Διαγραφή"
                                                onclick="Swal.fire({title:'Επιβεβαίωση',text:'Διαγραφή χρήστη «<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>»;',icon:'warning',showCancelButton:true,confirmButtonText:'Εντάξει',cancelButtonText:'Ακύρωση',confirmButtonColor:'#1a2f5a'}).then(r=>{if(r.isConfirmed)this.closest('form').submit();})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!--  EDIT MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: var(--radius); border: 1px solid var(--border);">

            <div class="modal-header" style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); border-bottom: 3px solid var(--gold);">
                <h5 class="modal-title" style="color:#fff; font-family:'Playfair Display',serif;">
                    <i class="bi bi-pencil me-2"></i>Επεξεργασία Αξιωματούχου
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="users.php" method="POST">
                <input type="hidden" name="user_id" id="modal_user_id">

                <div class="modal-body" style="padding: 1.75rem;">

                    <p class="section-title" style="font-size:0.68rem;">Αξιωματούχος</p>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Όνομα <span style="color:var(--text-muted); font-weight:400;">(δεν τροποποιείται)</span></label>
                            <input type="text" class="form-control" id="modal_first_name" disabled>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Επίθετο <span style="color:var(--text-muted); font-weight:400;">(δεν τροποποιείται)</span></label>
                            <input type="text" class="form-control" id="modal_last_name" disabled>
                        </div>
                    </div>

                    <p class="section-title" style="font-size:0.68rem;">Στοιχεία Θέσης</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="modal_party_id" class="form-label">Κόμμα</label>
                            <select class="form-select" id="modal_party_id" name="party_id">
                                <option value="">— Επιλέξτε —</option>
                                <?php foreach ($parties as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>">
                                        <?php echo htmlspecialchars($p['short_name'] . ' – ' . $p['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="modal_position_id" class="form-label">Θέση</label>
                            <select class="form-select" id="modal_position_id" name="position_id">
                                <option value="">— Επιλέξτε —</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo (int)$pos['id']; ?>">
                                        <?php echo htmlspecialchars($pos['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="modal_district" class="form-label">Επαρχία</label>
                            <input type="text" class="form-control" id="modal_district" name="district" placeholder="π.χ. Λευκωσία">
                        </div>
                    </div>

                </div>

                <div class="modal-footer" style="border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Ακύρωση
                    </button>
                    <button type="submit" name="update_official" class="btn btn-primary px-4">
                        <i class="bi bi-save me-2"></i>Αποθήκευση
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function toggleOfficialFields(role) {
    const fields = document.getElementById('official_fields');
    if (fields) fields.style.display = role === 'official' ? 'block' : 'none';
}

function openEditModal(userId, firstName, lastName, partyId, positionId, district) {
    document.getElementById('modal_user_id').value     = userId;
    document.getElementById('modal_first_name').value  = firstName  || '';
    document.getElementById('modal_last_name').value   = lastName   || '';
    document.getElementById('modal_party_id').value    = partyId    || '';
    document.getElementById('modal_position_id').value = positionId || '';
    document.getElementById('modal_district').value    = district   || '';

    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}
</script>

<footer class="pe-footer mt-5">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Εφαρμογή Παρακολούθησης Πόθεν Έσχες – Κυπριακή Δημοκρατία</p>
</footer>

</body>
</html>