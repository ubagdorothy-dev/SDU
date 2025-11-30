<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff'])) {
    header("Location: login.php");
    exit();
}
// Backend: connect and prepare training-related data
$pdo = connect_db();

// View selector
$view = $_GET['view'] ?? 'overview';

$staff_user_id = $_SESSION['user_id'];
$staff_username = $_SESSION['full_name'] ?? ($_SESSION['email'] ?? 'Staff');

// Auto-complete trainings where end_date has passed
try {
    $toCompleteStmt = $pdo->prepare("SELECT id, title, office_code FROM training_records WHERE end_date < CURDATE() AND status != 'completed'");
    $toCompleteStmt->execute();
    $toComplete = $toCompleteStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($toComplete)) {
        $upd = $pdo->prepare("UPDATE training_records SET status = 'completed' WHERE id = ?");
        $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        foreach ($toComplete as $t) {
            $upd->execute([$t['id']]);
            $title = $t['title'];
            $msg = "Training marked completed: " . $title;
            // notify unit directors
            $nds = $pdo->query("SELECT user_id FROM users WHERE role IN ('unit_director','unit director')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($nds as $ud) { $insn->execute([$ud, 'Training Completed', $msg]); }
            // notify office heads in same office
            if ($t['office_code']) {
                $nh = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head' AND office_code = ?");
                $nh->execute([$t['office_code']]);
                $heads = $nh->fetchAll(PDO::FETCH_COLUMN);
                foreach ($heads as $h) { $insn->execute([$h, 'Training Completed', $msg]); }
            }
        }
    }
} catch (Exception $e) {
    // ignore; non-fatal
}

// Counts
$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM training_records WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$staff_user_id]);
$trainings_completed = $stmt->fetchColumn() ?: 0;

$stmt2 = $pdo->prepare("SELECT COUNT(*) as c FROM training_records WHERE user_id = ? AND status != 'completed'");
$stmt2->execute([$staff_user_id]);
$trainings_upcoming = $stmt2->fetchColumn() ?: 0;

// Upcoming trainings (for overview list)
$upcoming_stmt = $pdo->prepare("SELECT id, title, start_date, end_date, status FROM training_records WHERE user_id = ? AND status != 'completed' ORDER BY start_date ASC LIMIT 10");
$upcoming_stmt->execute([$staff_user_id]);
// fetch into array for PDO (avoid mysqli-style num_rows/fetch_assoc)
$upcoming_rows = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activities (last 10)
$act_stmt = $pdo->prepare("SELECT id, title, status, created_at, start_date, end_date FROM training_records WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$act_stmt->execute([$staff_user_id]);
$activity_rows = $act_stmt->fetchAll(PDO::FETCH_ASSOC);

// Full records for training-records view
$rec_stmt = $pdo->prepare("SELECT * FROM training_records WHERE user_id = ? ORDER BY start_date DESC");
$rec_stmt->execute([$staff_user_id]);
$record_rows = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Debug helper: when visiting ?debug=1 show session & recent training_records
$show_debug = false;
$debug_rows = [];
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $show_debug = true;
    try {
        $dbgStmt = $pdo->prepare("SELECT * FROM training_records ORDER BY created_at DESC LIMIT 50");
        $dbgStmt->execute();
        $debug_rows = $dbgStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug_rows = [['error' => $e->getMessage()]];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="staff.css">
</head>
<body id="body">

    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h3 class="m-0"><?= $_SESSION['role'] === 'head' ? 'Office Head Dashboard' : 'Staff Dashboard' ?></h3>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'training-records' ? 'active' : '' ?>" href="?view=training-records">
                    <i class="fas fa-book-open me-2"></i> <span>Training Records</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="edit_profile_api.php" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="initProfileModal('view')">
                    <i class="fas fa-user-circle me-2"></i> <span>Profile</span>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <?php if ($show_debug): ?>
            <div class="alert alert-secondary">
                <strong>DEBUG</strong> - Session user_id: <?php echo htmlspecialchars($staff_user_id); ?> | Showing last <?php echo count($debug_rows); ?> training_records
                <div style="max-height:300px; overflow:auto; margin-top:8px;">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>id</th><th>user_id</th><th>title</th><th>status</th><th>start_date</th><th>end_date</th><th>created_at</th></tr></thead>
                        <tbody>
                        <?php foreach ($debug_rows as $dr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dr['id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dr['user_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($dr['title'] ?? '',0,60)); ?></td>
                                <td><?php echo htmlspecialchars($dr['status'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dr['start_date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dr['end_date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dr['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 mb-0"><em>Remove ?debug=1 from the URL to hide this panel.</em></p>
            </div>
        <?php endif; ?>
        <?php if ($view === 'overview'): ?>
            <div class="header mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h1 class="text-dark fw-bold mb-2">Welcome, <?php echo htmlspecialchars($staff_username); ?>!</h1>
                        <p class="mb-0" style="color: #6b7280;">Manage your training records and track your progress.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="initProfileModal('view')">
                            <i class="fas fa-user-circle me-2"></i> Profile
                        </button>
                        <button class="btn btn-primary position-relative" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;"></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="stats-cards">
                <div class="card">
                    <h3>Trainings Completed</h3>
                    <p><?php echo $trainings_completed; ?></p>
                </div>
                <div class="card">
                    <h3>Upcoming Trainings</h3>
                    <p><?php echo $trainings_upcoming; ?></p>
                </div>
            </div>

    
            <div class="quick-actions">
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#addTrainingModal" style="cursor:pointer;">
                    <i class="fas fa-plus-circle"></i>
                    <h4>Add Training</h4>
                    <p>Record a new training</p>
                </div>
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="initProfileModal('view')" style="cursor:pointer;">
                    <i class="fas fa-user"></i>
                    <h4>View Profile</h4>
                    <p>Manage your information</p>
                </div>
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#trainingRecordsModal" style="cursor:pointer;">
                    <i class="fas fa-book-open"></i>
                    <h4>Training Records</h4>
                    <p>Review your records instantly</p>
                </div>
            </div>


            <?php if (!empty($upcoming_rows)): ?>
            <div class="content-box mt-4">
                <h2>Upcoming Trainings</h2>
                <div class="list-group">
                    <?php foreach ($upcoming_rows as $upcoming): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($upcoming['title']); ?></h6>
                                <small class="text-muted">Scheduled for <?php echo date('M d, Y', strtotime($upcoming['start_date'])); ?> — <?php echo date('M d, Y', strtotime($upcoming['end_date'])); ?></small>
                            </div>
                            <span class="badge bg-warning rounded-pill">Upcoming</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-box mt-4">
                <h2>Recent Activity</h2>
                <?php if (!empty($activity_rows)): ?>
                    <div class="list-group">
                            <?php foreach ($activity_rows as $activity): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                    <small class="text-muted">
                                        <?php if ($activity['status'] === 'completed'): ?>
                                            Completed on <?php echo date('M d, Y', strtotime($activity['end_date'])); ?>
                                        <?php else: ?>
                                            Added on <?php echo date('M d, Y', strtotime($activity['created_at'])); ?> - Scheduled for <?php echo date('M d, Y', strtotime($activity['start_date'])); ?> — <?php echo date('M d, Y', strtotime($activity['end_date'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $activity['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                    <?php echo ucfirst($activity['status']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Training Activities Yet</h5>
                        <p class="text-muted">Start by adding your first training record!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
                            <i class="fas fa-plus-circle me-1"></i> Add Your First Training
                        </button>
                    </div>
                <?php endif; ?>
            </div>
      <?php elseif ($view === 'training-records'): ?>
    <div class="content-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>My Training Records</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
                <i class="fas fa-plus-circle me-1"></i> Add Training
            </button>
        </div>
        <?php echo $message; ?>
        <?php if (!empty($record_rows)): ?>
            <table class="table table-striped mt-4">
                <thead>
                    <tr>
                        <th scope="col">Training Title</th>
                        <th scope="col">Description</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($record_rows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['start_date']); ?> — <?php echo htmlspecialchars($row['end_date']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($row['status'] === 'upcoming'): ?>
                                        <a href="update_training_status.php?id=<?php echo $row['id']; ?>&status=completed" 
                                           class="btn btn-success btn-sm" 
                                           onclick="return confirm('Mark this training as completed?')">
                                            <i class="fas fa-check"></i> Mark Completed
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTrainingModal"
                                        data-training-id="<?php echo $row['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                        data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                        data-start-date="<?php echo htmlspecialchars($row['start_date']); ?>"
                                        data-end-date="<?php echo htmlspecialchars($row['end_date']); ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                        data-employment="<?php echo htmlspecialchars($row['employment_status'] ?? ''); ?>"
                                        data-degree="<?php echo htmlspecialchars($row['degree_attained'] ?? ''); ?>"
                                        data-degree-other="<?php echo htmlspecialchars($row['degree_other'] ?? ''); ?>"
                                        data-venue="<?php echo htmlspecialchars($row['venue'] ?? ''); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php if ($row['status'] === 'completed' && empty($row['proof_uploaded'])): ?>
                                        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadProofModal" data-training-id="<?php echo $row['id']; ?>"> <i class="fas fa-upload"></i> Upload Proof</button>
                                    <?php endif; ?>
                                    <a href="delete_training.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this training record?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info mt-4" role="alert">
                You have not completed any trainings yet.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var btn = document.getElementById('sidebar-toggle');
            if (btn) {
                btn.addEventListener('click', function(){
                    var b = document.getElementById('body') || document.body;
                    b.classList.toggle('toggled');
                });
            }
            // Static modals are rendered in the HTML (below) to avoid JS string quoting issues

            var editTrainingModal = document.getElementById('editTrainingModal');
            if (editTrainingModal) {
                editTrainingModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    var form = document.getElementById('editTrainingForm');
                    form.elements['id'].value = button.getAttribute('data-training-id');
                    form.elements['title'].value = button.getAttribute('data-title');
                    // handle start_date / end_date fields
                    if (form.elements['start_date']) form.elements['start_date'].value = button.getAttribute('data-start-date') || '';
                    if (form.elements['end_date']) form.elements['end_date'].value = button.getAttribute('data-end-date') || '';
                    // Populate description
                    if (form.elements['description']) {
                        form.elements['description'].value = button.getAttribute('data-description') || '';
                    }
                    if (form.elements['status']) form.elements['status'].value = button.getAttribute('data-status') || 'upcoming';
                    if (form.elements['employment_status']) form.elements['employment_status'].value = button.getAttribute('data-employment') || '';
                    if (form.elements['degree_attained']) form.elements['degree_attained'].value = button.getAttribute('data-degree') || '';
                    if (form.elements['degree_other']) form.elements['degree_other'].value = button.getAttribute('data-degree-other') || '';
                    if (form.elements['venue']) form.elements['venue'].value = button.getAttribute('data-venue') || '';
                });
            }

            var addForm = document.getElementById('addTrainingForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(addForm);
                    fetch('training_api.php?action=create', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json().catch(function(){ return { success:false, error: 'Invalid JSON response from server', rawStatus: r.status }; }); })
                        .then(function(data){
                            var fb = document.getElementById('addTrainingFeedback');
                            console.log('training_api#create response:', data);
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training added!</div>';
                                setTimeout(function(){ window.location.reload(); }, 600);
                            } else {
                                var msg = data.error || 'Failed';
                                if (data.raw) msg += ' (raw:' + JSON.stringify(data.raw) + ')';
                                fb.innerHTML = '<div class="alert alert-danger">' + msg + '</div>';
                            }
                        })
                        .catch(function(err){
                            var fb = document.getElementById('addTrainingFeedback');
                            console.error('Fetch error on create:', err);
                            fb.innerHTML = '<div class="alert alert-danger">Request failed: ' + (err && err.message ? err.message : 'network error') + '</div>';
                        });
                });
            }

            var editForm = document.getElementById('editTrainingForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(editForm);
                    fetch('training_api.php?action=update', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(data){
                            var fb = document.getElementById('editTrainingFeedback');
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training updated!</div>';
                                setTimeout(function(){ window.location.reload(); }, 600);
                            } else {
                                fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                            }
                        })
                        .catch(function(){
                            var fb = document.getElementById('editTrainingFeedback');
                            fb.innerHTML = '<div class="alert alert-danger">Request failed</div>';
                        });
                });

                        
                        // Attach handlers to the static upload proof modal and form
                        var uploadProofModal = document.getElementById('uploadProofModal');
                        if (uploadProofModal) {
                            uploadProofModal.addEventListener('show.bs.modal', function (event) {
                                var button = event.relatedTarget;
                                if (!button) return;
                                var form = document.getElementById('uploadProofForm');
                                if (form && form.elements['training_id']) form.elements['training_id'].value = button.getAttribute('data-training-id');
                            });
                        }

                        var uploadForm = document.getElementById('uploadProofForm');
                        if (uploadForm) {
                            uploadForm.addEventListener('submit', function(e){
                                e.preventDefault();
                                var fd = new FormData(uploadForm);
                                fetch('training_api.php?action=upload_proof', { method: 'POST', body: fd, credentials: 'same-origin' })
                                    .then(function(r){ return r.json(); })
                                    .then(function(data){
                                        var fb = document.getElementById('uploadProofFeedback');
                                        if (data.success) {
                                            fb.innerHTML = '<div class="alert alert-success">Proof uploaded and sent for review.</div>';
                                            setTimeout(function(){ window.location.reload(); }, 900);
                                        } else {
                                            fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Upload failed') + '</div>';
                                        }
                                    }).catch(function(){
                                        var fb = document.getElementById('uploadProofFeedback');
                                        fb.innerHTML = '<div class="alert alert-danger">Request failed</div>';
                                    });
                            });
                        }

                        // Training records modal loader
            // Training records modal loader
            const trainingRecordsModal = document.getElementById('trainingRecordsModal');
            if (trainingRecordsModal) {
                trainingRecordsModal.addEventListener('show.bs.modal', function () {
                    const container = document.getElementById('trainingRecordsContent');
                    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
                    fetch('training_records_api.php', { credentials: 'same-origin' })
                        .then(response => response.text())
                        .then(html => {
                            container.innerHTML = html;
                        })
                        .catch(() => {
                            container.innerHTML = '<div class="alert alert-danger">Unable to load training records.</div>';
                        });
                });
            }

        });
        // Update unread count on staff dashboard
        async function updateUnreadCount() {
            const res = await fetch('get_unread_count.php');
            const j = await res.json();
            const badge = document.getElementById('unreadBadge');
            const topBadge = document.getElementById('notificationBadge');
            if (j.count > 0) {
                if (badge) {
                    badge.textContent = j.count;
                    badge.style.display = 'inline-block';
                }
                if (topBadge) {
                    topBadge.textContent = j.count;
                    topBadge.style.display = 'block';
                }
            } else {
                if (badge) badge.style.display = 'none';
                if (topBadge) topBadge.style.display = 'none';
            }
        }
        updateUnreadCount();
        setInterval(updateUnreadCount, 5000);
    </script>

    <!-- Training Records Modal -->
    <div class="modal fade" id="trainingRecordsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-book-reader me-2"></i>Your Training Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="trainingRecordsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <small class="text-muted">Need more space? <a href="?view=training-records">Open the full page</a></small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Notifications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notificationsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    // Include profile modal if present; otherwise render a simple fallback modal
    if (file_exists(__DIR__ . '/profile_modal.php')) {
        include __DIR__ . '/profile_modal.php';
    } else {
        ?>
        <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? ''); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></p>
                        <p class="text-muted">Profile editing is not available because the profile modal file is missing.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <!-- STATIC: Add Training Modal -->
    <div class="modal fade" id="addTrainingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Training</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTrainingForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Training Title</label>
                            <input type="text" name="title" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" required />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Employment Status</label>
                            <select name="employment_status" class="form-control">
                                <option value="Probationary">Probationary</option>
                                <option value="Permanent/Regular">Permanent/Regular</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Degree Attained</label>
                            <select name="degree_attained" class="form-control" id="add_degree_select">
                                <option value="Bachelors">Bachelors</option>
                                <option value="Masters">Master's</option>
                                <option value="Doctorate">Doctorate / PhD</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="degree_other" class="form-control mt-2" placeholder="If Others, specify" style="display:none;" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="completed">Completed</option>
                                <option value="upcoming" selected>Upcoming</option>
                            </select>
                        </div>
                        <div id="addTrainingFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- STATIC: Edit Training Modal -->
    <div class="modal fade" id="editTrainingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Training</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTrainingForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" />
                        <div class="mb-3">
                            <label class="form-label">Training Title</label>
                            <input type="text" name="title" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required />
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" required />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Employment Status</label>
                            <select name="employment_status" class="form-control">
                                <option value="Probationary">Probationary</option>
                                <option value="Permanent/Regular">Permanent/Regular</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Degree Attained</label>
                            <select name="degree_attained" class="form-control">
                                <option value="Bachelors">Bachelors</option>
                                <option value="Masters">Master's</option>
                                <option value="Doctorate">Doctorate / PhD</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <input type="text" name="degree_other" class="form-control mt-2" placeholder="If Others, specify" style="display:none;" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="completed">Completed</option>
                                <option value="upcoming">Upcoming</option>
                            </select>
                        </div>
                        <div id="editTrainingFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- STATIC: Upload Proof Modal -->
    <div class="modal fade" id="uploadProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Proof of Completion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="uploadProofForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="training_id" />
                        <div class="mb-3">
                            <label class="form-label">Select file (photo or certificate)</label>
                            <input type="file" name="proof" class="form-control" accept="image/*,.pdf" required />
                        </div>
                        <div id="uploadProofFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Load notifications modal
        const notificationsModal = document.getElementById('notificationsModal');
        if (notificationsModal) {
            notificationsModal.addEventListener('show.bs.modal', function () {
                const container = document.getElementById('notificationsContent');
                container.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
                fetch('notifications_api.php', { credentials: 'same-origin' })
                    .then(response => response.text())
                    .then(html => {
                        // Insert HTML
                        container.innerHTML = html;
                        // Execute any scripts inside the loaded HTML (innerHTML doesn't run scripts)
                        try {
                            const scripts = container.querySelectorAll('script');
                            scripts.forEach(s => {
                                const newScript = document.createElement('script');
                                if (s.src) {
                                    newScript.src = s.src;
                                    newScript.async = false;
                                } else {
                                    newScript.textContent = s.textContent;
                                }
                                document.body.appendChild(newScript);
                                document.body.removeChild(newScript);
                            });
                        } catch (e) {
                            console.error('Error executing notification scripts:', e);
                        }
                        // Also initialize handlers directly on the inserted content to be reliable
                        try {
                            initInsertedNotificationHandlers(container);
                        } catch (e) {
                            console.error('Failed to init inserted notification handlers:', e);
                        }
                    })
                    .catch(() => {
                        container.innerHTML = '<div class="alert alert-danger">Unable to load notifications.</div>';
                    });
            });
        }

        // Initialize profile modal
        if (typeof initProfileModal === 'function') {
            initProfileModal('view');
        }
        
        // Called after notifications content is injected to attach event handlers
        function initInsertedNotificationHandlers(container) {
            if (!container) return;
            // per-item mark buttons
            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.mark-read-btn');
                if (btn) {
                    const id = btn.getAttribute('data-id');
                    if (!id) return;
                    btn.disabled = true;
                    fetch('mark_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids: [id] }),
                        credentials: 'same-origin'
                    })
                    .then(r => r.json())
                    .then(data => {
                        console.log('inserted mark_read response', data);
                        if (data && data.success) {
                            const item = container.querySelector(`[data-id="${id}"]`);
                            if (item) {
                                item.classList.remove('unread');
                                const actionBtn = item.querySelector('.mark-read-btn');
                                if (actionBtn) actionBtn.remove();
                            }
                            // update unread counts
                            if (typeof updateUnreadCount === 'function') updateUnreadCount();
                        } else {
                            console.error('Failed to mark read', data);
                            btn.disabled = false;
                        }
                    })
                    .catch(err => { console.error(err); btn.disabled = false; });
                }

                const allBtn = e.target.closest('#markAllReadBtn');
                if (allBtn) {
                    allBtn.disabled = true;
                    const unreadEls = Array.from(container.querySelectorAll('.list-group-item.unread'));
                    const ids = unreadEls.map(el => el.getAttribute('data-id')).filter(Boolean);
                    if (ids.length === 0) { allBtn.disabled = false; return; }
                    fetch('mark_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids }),
                        credentials: 'same-origin'
                    })
                    .then(r => r.json())
                    .then(data => {
                        console.log('inserted mark_all response', data);
                        if (data && data.success) {
                            unreadEls.forEach(item => {
                                item.classList.remove('unread');
                                const actionBtn = item.querySelector('.mark-read-btn');
                                if (actionBtn) actionBtn.remove();
                            });
                            if (typeof updateUnreadCount === 'function') updateUnreadCount();
                        } else {
                            console.error('Failed to mark all read', data);
                        }
                        allBtn.disabled = false;
                    })
                    .catch(err => { console.error(err); allBtn.disabled = false; });
                }
                // Delete all button inside inserted container
                const delBtn = e.target.closest('#deleteAllBtn');
                if (delBtn) {
                    delBtn.disabled = true;
                    // select all items inside the inserted container (read + unread)
                    const allEls = Array.from(container.querySelectorAll('.list-group-item'));
                    const ids = allEls.map(el => el.getAttribute('data-id')).filter(Boolean);
                    if (ids.length === 0) { delBtn.disabled = false; return; }
                    fetch('delete_notifications.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids }),
                        credentials: 'same-origin'
                    })
                    .then(r => r.json())
                    .then(data => {
                        console.log('inserted delete_all response', data);
                        if (data && data.success) {
                            allEls.forEach(item => item.remove());
                            if (typeof updateUnreadCount === 'function') updateUnreadCount();
                        } else {
                            console.error('Failed to delete all', data);
                        }
                        delBtn.disabled = false;
                    })
                    .catch(err => { console.error(err); delBtn.disabled = false; });
                }
            });
        }
    </script>

</body>
</html>