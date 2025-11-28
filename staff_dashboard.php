<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff'])) {
    header("Location: login.php");
    exit();
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
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            display: flex;
            background: white;
            background-attachment: fixed;
        }
        @media (min-width: 992px) {
            body.toggled .sidebar { width: 80px; }
            body.toggled .main-content { margin-left: 80px; }
            .sidebar .nav-link { transition: all 0.2s; white-space: nowrap; overflow: hidden; }
            body.toggled .sidebar .nav-link { text-align: center; padding: 12px 0; }
            body.toggled .sidebar .nav-link i { margin-right: 0; }
            body.toggled .sidebar .nav-link span { display: none; }
            body.toggled .sidebar h3 { display: none; }
        }
        .main-content {
            flex-grow: 1;
            padding: 2rem;
            margin-left: 250px; 
            transition: margin-left 0.3s ease-in-out;
        }
        .sidebar {
            width: 250px;
            background-color: #1a237e;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
            transition: width 0.3s ease-in-out;
        }
        .sidebar .nav-link {
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 5px 15px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.75rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #3f51b5;
        }
        .content-box { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.1); 
            padding: 2rem; 
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .content-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        /* Transparent sidebar toggle like admin */
        .sidebar .btn-toggle {
            background-color: transparent;
            border: none;
            color: #ffffff;
            padding: 6px 10px;
        }
        .sidebar .btn-toggle:focus { box-shadow: none; }
        .sidebar .btn-toggle:hover { background-color: transparent; }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-left: 0 !important;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card {
                padding: 1.5rem 1rem;
            }
            
            .card p {
                font-size: 2rem;
            }
            
            .content-box {
                padding: 1.5rem;
                border-radius: 16px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header p {
                font-size: 0.9rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .card {
                padding: 1rem;
            }
            
            .content-box {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 1.25rem;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .btn-group {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
        .card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 2rem 1.5rem;
            text-align: center;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        .card h3 {
            margin: 0 0 1rem;
            color: var(--card-color);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card p {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--card-color);
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-cards .card:nth-child(1) { 
            --card-color: #10b981;
            --card-color-light: #6ee7b7;
        }
        .stats-cards .card:nth-child(2) { 
            --card-color: #f59e0b;
            --card-color-light: #fbbf24;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .action-card {
            background: #1a237e;
            color: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .action-card:hover {
            transform: translateY(-5px);
        }
        .action-card a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .action-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .btn-group .btn {
            margin-right: 5px;
        }
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        .progress-bar {
            background-color: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .table thead th {
            background: #020381;
            color: white;
            font-weight: 600;
            padding: 1rem;
            border: none;
        }

         /* Training records action buttons */
         .table .btn-sm {
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        
        .table .btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .table .btn-sm i {
            font-size: 0.8rem;
        }
        
        .table td .d-flex {
            align-items: center;
        }

    
        /* Center modals both horizontally and vertically */
        .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100vh - 1rem);
        }
        
        .modal-content {
            width: 100%;
        }
    </style>
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


            <?php if ($result_upcoming && $result_upcoming->num_rows > 0): ?>
            <div class="content-box mt-4">
                <h2>Upcoming Trainings</h2>
                <div class="list-group">
                    <?php while ($upcoming = $result_upcoming->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($upcoming['title']); ?></h6>
                                <small class="text-muted">Scheduled for <?php echo date('M d, Y', strtotime($upcoming['completion_date'])); ?></small>
                            </div>
                            <span class="badge bg-warning rounded-pill">Upcoming</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-box mt-4">
                <h2>Recent Activity</h2>
            <?php if ($result_activities && $result_activities->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($activity = $result_activities->fetch_assoc()): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                    <small class="text-muted">
                                        <?php if ($activity['status'] === 'completed'): ?>
                                            Completed on <?php echo date('M d, Y', strtotime($activity['completion_date'])); ?>
                                        <?php else: ?>
                                            Added on <?php echo date('M d, Y', strtotime($activity['created_at'])); ?> - Scheduled for <?php echo date('M d, Y', strtotime($activity['completion_date'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $activity['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                    <?php echo ucfirst($activity['status']); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
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
        <?php if ($result_records && $result_records->num_rows > 0): ?>
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
                    <?php while ($row = $result_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['completion_date']); ?></td>
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
                                        data-date="<?php echo htmlspecialchars($row['completion_date']); ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="delete_training.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this training record?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
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
            // Build Add Training Modal dynamically
            var addModalEl = document.createElement('div');
            addModalEl.className = 'modal fade';
            addModalEl.id = 'addTrainingModal';
            addModalEl.tabIndex = -1;
            addModalEl.innerHTML = '\
        <div class="modal-dialog">\
          <div class="modal-content">\
            <div class="modal-header">\
              <h5 class="modal-title">Add Training</h5>\
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\
            </div>\
            <form id="addTrainingForm">\
              <div class="modal-body">\
                <div class="mb-3">\
                  <label class="form-label">Training Title</label>\
                  <input type="text" name="title" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Description</label>\
                  <textarea name="description" class="form-control" rows="3"></textarea>\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Date</label>\
                  <input type="date" name="completion_date" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Status</label>\
                  <select name="status" class="form-control" required>\
                    <option value="completed">Completed</option>\
                    <option value="upcoming">Upcoming</option>\
                  </select>\
                </div>\
                <div id="addTrainingFeedback"></div>\
              </div>\
              <div class="modal-footer">\
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>\
                <button type="submit" class="btn btn-primary">Save</button>\
              </div>\
            </form>\
          </div>\
        </div>';
            document.body.appendChild(addModalEl);

            // Build Edit Training Modal dynamically
            var editModalEl = document.createElement('div');
            editModalEl.className = 'modal fade';
            editModalEl.id = 'editTrainingModal';
            editModalEl.tabIndex = -1;
            editModalEl.innerHTML = '\
        <div class="modal-dialog">\
          <div class="modal-content">\
            <div class="modal-header">\
              <h5 class="modal-title">Edit Training</h5>\
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\
            </div>\
            <form id="editTrainingForm">\
              <div class="modal-body">\
                <input type="hidden" name="id" />\
                <div class="mb-3">\
                  <label class="form-label">Training Title</label>\
                  <input type="text" name="title" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Description</label>\
                  <textarea name="description" class="form-control" rows="3"></textarea>\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Date</label>\
                  <input type="date" name="completion_date" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Status</label>\
                  <select name="status" class="form-control" required>\
                    <option value="completed">Completed</option>\
                    <option value="upcoming">Upcoming</option>\
                  </select>\
                </div>\
                <div id="editTrainingFeedback"></div>\
              </div>\
              <div class="modal-footer">\
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>\
                <button type="submit" class="btn btn-primary">Update</button>\
              </div>\
            </form>\
          </div>\
        </div>';
            document.body.appendChild(editModalEl);

            var editTrainingModal = document.getElementById('editTrainingModal');
            if (editTrainingModal) {
                editTrainingModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    var form = document.getElementById('editTrainingForm');
                    form.elements['id'].value = button.getAttribute('data-training-id');
                    form.elements['title'].value = button.getAttribute('data-title');
                    form.elements['completion_date'].value = button.getAttribute('data-date');
                    // Populate description (was missing) so textarea shows existing description when editing
                    if (form.elements['description']) {
                        form.elements['description'].value = button.getAttribute('data-description') || '';
                    }
                    form.elements['status'].value = button.getAttribute('data-status');
                });
            }

            var addForm = document.getElementById('addTrainingForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(addForm);
                    fetch('training_api.php?action=create', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(data){
                            var fb = document.getElementById('addTrainingFeedback');
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training added!</div>';
                                setTimeout(function(){ window.location.reload(); }, 600);
                            } else {
                                fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                            }
                        })
                        .catch(function(){
                            var fb = document.getElementById('addTrainingFeedback');
                            fb.innerHTML = '<div class="alert alert-danger">Request failed</div>';
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
            }

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

    <?php include 'profile_modal.php'; ?>

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