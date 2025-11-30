<?php
session_start();
require_once 'db.php';

$pdo = connect_db();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    header("Location: login.php");
    exit();
}

// View selection
$view = $_GET['view'] ?? 'overview';

// Flash/message helper
$message = '';
if (!empty($_SESSION['message'])) {
    $message = '<div class="alert alert-info">' . htmlspecialchars($_SESSION['message']) . '</div>';
    unset($_SESSION['message']);
}

// Load current user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT user_id, full_name, email, office_code FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $user['full_name'] ?? ($user['email'] ?? 'Office Head');
$office = $user['office_code'] ?? null;

// Head-specific metrics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$head_trainings_completed = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE user_id = ? AND status = 'upcoming'");
$stmt->execute([$user_id]);
$head_trainings_upcoming = (int)$stmt->fetchColumn();

if ($office) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'staff' AND office_code = ?");
    $stmt->execute([$office]);
    $total_staff_in_office = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE office_code = ? AND status = 'completed'");
    $stmt->execute([$office]);
    $completed_trainings_in_office = (int)$stmt->fetchColumn();
    
        // Minimal training status breakdown (for chart): completed, pending, overdue
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE office_code = ? AND status = 'completed'");
        $stmt->execute([$office]);
        $training_completed = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE office_code = ? AND status != 'completed' AND end_date >= CURDATE()");
        $stmt->execute([$office]);
        $training_pending = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM training_records WHERE office_code = ? AND status != 'completed' AND end_date < CURDATE()");
        $stmt->execute([$office]);
        $training_overdue = (int)$stmt->fetchColumn();
} else {
    $total_staff_in_office = 0;
    $completed_trainings_in_office = 0;
        $training_completed = 0;
        $training_pending = 0;
        $training_overdue = 0;
}

// Upcoming trainings for this head (for dashboard list)
$stmt = $pdo->prepare("SELECT id, title, start_date, end_date, nature, scope FROM training_records WHERE user_id = ? AND status = 'upcoming' ORDER BY end_date ASC LIMIT 10");
$stmt->execute([$user_id]);
$result_head_upcoming_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activity (head)
$stmt = $pdo->prepare("SELECT id, title, start_date, end_date, created_at, status, nature, scope FROM training_records WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$user_id]);
$result_head_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Training records (for training-records view)
$result_records = [];
if ($view === 'training-records') {
    $stmt = $pdo->prepare("SELECT id, title, description, start_date, end_date, status, venue, nature, scope FROM training_records WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $result_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Office directory rows (for office-directory view)
$office_staff_rows = [];
if ($view === 'office-directory' && !empty($office)) {
    $stmt = $pdo->prepare("SELECT u.user_id AS id, u.full_name AS username, u.email, s.position, s.program, s.job_function FROM users u LEFT JOIN staff_details s ON u.user_id = s.user_id WHERE u.role = 'staff' AND u.office_code = ? ORDER BY u.full_name");
    $stmt->execute([$office]);
    $office_staff_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Head Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            display: flex;
            background-color: #f0f2f5;
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
            color: #ffffff;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
            transition: width 0.3s ease-in-out;
        }
        .sidebar .nav-link { color: white; padding: 12px 20px; border-radius: 5px; margin: 5px 15px; transition: background-color 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #3f51b5; }
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
        .content-box h2 {
            margin-top: 0;
            color: #1e293b;
            border-bottom: 3px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 1.5rem;
        }
        /* Transparent sidebar toggle like admin */
        .sidebar .btn-toggle { background-color: transparent; border: none; color: #ffffff; padding: 6px 10px; }
        .sidebar .btn-toggle:focus { box-shadow: none; }
        .sidebar .btn-toggle:hover { background-color: transparent; }
        @media (min-width: 992px) {
            body.toggled .sidebar { width: 80px; }
            body.toggled .main-content { margin-left: 80px; }
            .sidebar .nav-link { transition: all 0.2s; }
            body.toggled .sidebar .nav-link { text-align: center; padding: 12px 0; }
            body.toggled .sidebar .nav-link i { margin-right: 0; }
            body.toggled .sidebar .nav-link span { display: none; }
            /* Hide header logo text when collapsed and tighten logo spacing */
            body.toggled .sidebar .logo-text { display: none; }
            body.toggled .sidebar h3 { display: none; }
            body.toggled .sidebar .sidebar-logo { margin-right: 0; }
        }
        .header h1 { 
            font-size: 2rem; 
            font-weight: 800; 
            margin-bottom: .25rem; 
            color: #1e293b;
        }
        .header p { 
            color: #6b7280; 
            font-size: .95rem; 
            margin: 0; 
        }

        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
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
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .card p {
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0;
            color: var(--card-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card:nth-child(1) { 
            --card-color: #10b981;
            --card-color-light: #6ee7b7;
        }
        .card:nth-child(2) { 
            --card-color: #f59e0b;
            --card-color-light: #fbbf24;
        }
        .card:nth-child(3) { 
            --card-color: #6366f1;
            --card-color-light: #a5b4fc;
        }
        .card:nth-child(4) { 
            --card-color: #8b5cf6;
            --card-color-light: #c4b5fd;
        }

        /* Buttons consistency */
        .btn-primary,
        .btn-info,
        .btn-success { border-radius: 10px; padding: .6rem 1rem; font-weight: 600; }
        
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
        
        /* Training records table improvements */
        .table {
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background: #020381;
            color: white;
            font-weight: 600;
            padding: 1rem;
            border: none;
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table-striped tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .table-striped tbody tr:hover {
            background-color: #f1f5f9;
            transition: background-color 0.2s ease;
        }
        .table .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Center modals */
        .modal-dialog { display: flex; align-items: center; min-height: calc(100vh - 1rem); }
        .modal-content { border-radius: 14px; }
        .modal-header .btn-close { margin: -0.25rem -0.25rem -0.25rem auto; }

        /* Responsive design */
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
        }
        .sidebar-logo { height: 30px; width: auto; margin-right: 8px; }
        /* Make text and icon changes instantaneous so labels "pop" when opening */
        .sidebar .nav-link span,
        .sidebar .logo-text,
        .sidebar .nav-link i {
            transition: none !important;
            -webkit-transition: none !important;
        }

        /* Disable width/margin/padding transitions so expand/collapse is instant */
        .sidebar,
        .main-content,
        .sidebar .nav-link,
        .sidebar .nav-link i,
        .sidebar .nav-link span {
            transition: none !important;
            -webkit-transition: none !important;
        }

        /* Also override media-specific toggled transitions */
        @media (min-width: 992px) {
            body.toggled .sidebar { transition: none !important; }
            body.toggled .main-content { transition: none !important; }
        }
    </style>
    </head>
<body id="body">
<input type="checkbox" id="sidebar-toggle-checkbox" style="display:none;">

    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
             <img src="SDU_Logo.png" alt="SDU Logo" class="sidebar-logo">
            <h5 class="m-0 text-white"><span class="logo-text"><?= $_SESSION['role'] === 'head' ? 'SDU OFFICE HEAD' : 'SDU STAFF' ?></span></h5>

            <label for="sidebar-toggle-checkbox" id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></label>
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
                <a class="nav-link <?= $view === 'office-directory' ? 'active' : '' ?>" href="?view=office-directory">
                    <i class="fas fa-users me-2"></i> <span>Office Staff Directory</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="initProfileModal('view')">
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
            <div class="header mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h1 class="mb-1">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                        <p class="mb-0">Your office: <strong><?php echo htmlspecialchars($office ?: 'Not set'); ?></strong></p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="initProfileModal('view')">
                            <i class="fas fa-user-circle me-2"></i> Profile
                        </button>
                        <button class="btn btn-primary position-relative" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;"></span>
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#headBroadcastModal">
                            <i class="fas fa-paper-plane me-2"></i> Notify My Staff
                        </button>
                    </div>
                </div>
            </div>
            <div class="stats-cards">
                <div class="card">
                    <h3>My Completed Trainings</h3>
                    <p><?php echo $head_trainings_completed; ?></p>
                </div>
                <div class="card">
                    <h3>My Upcoming Trainings</h3>
                    <p><?php echo $head_trainings_upcoming; ?></p>
                </div>
                <div class="card">
                    <h3>Staff in Your Office</h3>
                    <p><?php echo $total_staff_in_office; ?></p>
                </div>
                <div class="card">
                    <h3>Completed Trainings (Office)</h3>
                    <p><?php echo $completed_trainings_in_office; ?></p>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="content-box h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="mb-1">Total Attendance</h2>
                                <p class="text-muted small mb-0">Completed trainings in your office</p>
                            </div>
                        </div>
                        <canvas id="headAttendanceChart" height="160"></canvas>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="content-box h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="mb-1">Training Status Breakdown</h2>
                                <p class="text-muted small mb-0">Completed / Pending / Overdue</p>
                            </div>
                            <div id="trainingStatusSummary" class="text-end small text-muted" style="min-width:120px">
                                <!-- Filled by JS -->
                            </div>
                        </div>
                        <canvas id="trainingStatusChart" height="160"></canvas>
                    </div>
                </div>
            </div>
            <?php if (!empty($result_head_upcoming_list)): ?>
            <div class="content-box mt-4">
                <h2>My Upcoming Trainings</h2>
                <div class="list-group">
                    <?php foreach ($result_head_upcoming_list as $upcoming): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($upcoming['title']); ?></h6>
                                <small class="text-muted">Scheduled for <?php echo date('M d, Y', strtotime($upcoming['start_date'])); ?> — <?php echo date('M d, Y', strtotime($upcoming['end_date'])); ?></small>
                                <?php if (!empty($upcoming['nature']) || !empty($upcoming['scope'])): ?>
                                    <small class="d-block mt-1">
                                        <?php if (!empty($upcoming['nature'])): ?>
                                            <span class="badge bg-info me-1"><?php echo htmlspecialchars($upcoming['nature']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($upcoming['scope'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($upcoming['scope']); ?></span>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-warning rounded-pill">Upcoming</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-box mt-4">
                <h2>My Recent Activity</h2>
            <?php if (!empty($result_head_activities)): ?>
                    <div class="list-group">
                        <?php foreach ($result_head_activities as $activity): ?>
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
                                    <?php if (!empty($activity['nature']) || !empty($activity['scope'])): ?>
                                        <small class="d-block mt-1">
                                            <?php if (!empty($activity['nature'])): ?>
                                                <span class="badge bg-info me-1"><?php echo htmlspecialchars($activity['nature']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($activity['scope'])): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($activity['scope']); ?></span>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
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
                <?php if (!empty($result_records)): ?>
                    <table class="table table-striped mt-4">
                        <thead>
                            <tr>
                                <th scope="col">Training Title</th>
                                <th scope="col">Description</th>
                                <th scope="col">Dates</th>
                                <th scope="col">Nature</th>
                                <th scope="col">Scope</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result_records as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_date']); ?> — <?php echo htmlspecialchars($row['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nature'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['scope'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php if ($row['status'] === 'upcoming'): ?>
                                                <a href="update_training_status.php?id=<?php echo $row['id']; ?>&status=completed" 
                                                   class="btn btn-success btn-sm" 
                                                   onclick="return confirm('Mark this training as completed?')"
                                                   title="Mark as completed">
                                                    <i class="fas fa-check me-1"></i> Complete
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTrainingModal"
                                                data-training-id="<?php echo $row['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                                data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                data-start-date="<?php echo htmlspecialchars($row['start_date'] ?? ''); ?>"
                                                data-end-date="<?php echo htmlspecialchars($row['end_date'] ?? ''); ?>"
                                                data-venue="<?php echo htmlspecialchars($row['venue'] ?? ''); ?>"
                                                data-nature="<?php echo htmlspecialchars($row['nature'] ?? ''); ?>"
                                                data-scope="<?php echo htmlspecialchars($row['scope'] ?? ''); ?>"
                                                data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                                title="Edit training">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <a href="delete_training.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this training record?')"
                                               title="Delete training">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info mt-4" role="alert">
                        You have not added any trainings yet. <a href="#" data-bs-toggle="modal" data-bs-target="#addTrainingModal">Add your first training</a>.
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($view === 'office-directory'): ?>
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Office Staff Directory</h2>
                </div>
                <?php // $office_staff_rows prepared earlier using PDO; use that for listing ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Program</th>
                                <th>Job Function</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($office_staff_rows)): ?>
                            <?php foreach ($office_staff_rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-view-trainings data-user-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-eye"></i> View Trainings
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No staff found or office not set.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const sidebarCheckbox = document.getElementById('sidebar-toggle-checkbox');
            const toggleLabel = document.getElementById('sidebar-toggle');
            if (sidebarCheckbox) {
                // initialize checkbox from current body state
                sidebarCheckbox.checked = (document.body.classList.contains('toggled'));
                sidebarCheckbox.addEventListener('change', function(){
                    if (sidebarCheckbox.checked) document.body.classList.add('toggled');
                    else document.body.classList.remove('toggled');
                });
                if (toggleLabel) toggleLabel.style.cursor = 'pointer';
            }

            if (document.getElementById('headAttendanceChart')) {
                initHeadCharts();
            }
            initHeadBroadcastForm();
            initStaffTrainingButtons();
            initAddTrainingForm();
        });

        let headAttendanceChart;

        function initHeadCharts() {
            fetch('dashboard_metrics_api.php?scope=head', { credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(resp => {
                        console.log('dashboard_metrics_api response', resp);
                        if (!resp.success) throw new Error('Failed to load metrics');
                        renderHeadAttendance(resp.data.attendance);
                        renderHeadParticipation(resp.data.participation);
                        // prefer structured trainingStatus data when available
                        if (resp.data && resp.data.trainingStatus) {
                            renderTrainingStatus(resp.data.trainingStatus);
                        } else if (resp.data && resp.data.staffPerOffice) {
                            renderStaffPerOffice(resp.data.staffPerOffice);
                        } else {
                            // show placeholder in trainingStatusChart when no structured data
                            const t = document.getElementById('trainingStatusChart');
                            if (t) {
                                const c = t.getContext('2d');
                                c.font = '14px Inter';
                                c.fillStyle = '#cbd5f5';
                                c.textAlign = 'center';
                                c.fillText('No training status data available', t.width / 2, t.height / 2);
                            }
                        }
                    })
                    .catch((err) => {
                        console.error('Failed to load dashboard metrics', err);
                        ['headAttendanceChart','headParticipationChart','staffPerOfficeChart','trainingStatusChart'].forEach(id => {
                            const canvas = document.getElementById(id);
                            if (!canvas) return;
                            const ctx = canvas.getContext('2d');
                            ctx.font = '14px Inter';
                            ctx.fillStyle = '#cbd5f5';
                            ctx.textAlign = 'center';
                            ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
                        });
                    });
        }

        function renderHeadAttendance(dataset) {
            const ctx = document.getElementById('headAttendanceChart');
            if (!ctx) return;
            if (headAttendanceChart) headAttendanceChart.destroy();
            headAttendanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataset.labels,
                    datasets: [{
                        label: 'Completed trainings',
                        data: dataset.values,
                        borderColor: '#38bdf8',
                        backgroundColor: 'rgba(56,189,248,0.15)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }

        // Render training status breakdown (horizontal bar)
        let trainingStatusChart;
        function renderTrainingStatus(data) {
            const ctx = document.getElementById('trainingStatusChart');
            if (!ctx) return;
            const values = [data.completed || 0, data.pending || 0, data.overdue || 0];
            const labels = ['Completed', 'Pending', 'Overdue'];
            if (trainingStatusChart) trainingStatusChart.destroy();
            trainingStatusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Count',
                        data: values,
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });

            // Update summary text
            const total = (values[0] + values[1] + values[2]) || 0;
            const overduePct = total ? Math.round((values[2] / total) * 1000) / 10 : 0;
            const summary = document.getElementById('trainingStatusSummary');
            if (summary) {
                summary.innerHTML = '<div><strong>' + (values[0] || 0) + '</strong> completed</div>' +
                                    '<div><strong>' + (values[1] || 0) + '</strong> pending</div>' +
                                    '<div><strong>' + overduePct + '%</strong> overdue</div>';
            }
        }

        // Inline server-side data injection for training status (minimal, avoids extra API)
        const trainingStatusData = {
            completed: <?php echo (int)($training_completed ?? 0); ?>,
            pending: <?php echo (int)($training_pending ?? 0); ?>,
            overdue: <?php echo (int)($training_overdue ?? 0); ?>
        };

        // Render injected data so chart is visible even without an external API
        if (document.getElementById('trainingStatusChart')) {
            try { renderTrainingStatus(trainingStatusData); } catch (e) { console.error('Render training status failed', e); }
        }

        function initHeadBroadcastForm() {
            const form = document.getElementById('headBroadcastForm');
            if (!form) return;
            const feedback = document.getElementById('headBroadcastFeedback');
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                feedback.innerHTML = '';
                const fd = new FormData(form);
                fetch('head_broadcast.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(resp => {
                        if (resp.success) {
                            feedback.innerHTML = '<div class="alert alert-success">Notification sent to your staff.</div>';
                            form.reset();
                            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('headBroadcastModal')).hide(), 1000);
                        } else {
                            feedback.innerHTML = '<div class="alert alert-danger">' + (resp.error || 'Unable to send notification') + '</div>';
                        }
                    })
                    .catch(() => {
                        feedback.innerHTML = '<div class="alert alert-danger">Request failed. Please try again.</div>';
                    })
                    .finally(() => { submitBtn.disabled = false; });
            });
        }

        function initStaffTrainingButtons() {
            const staffTrainingsModal = document.getElementById('staffTrainingsModal');
            if (!staffTrainingsModal) return;
            document.querySelectorAll('[data-view-trainings]').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const uid = this.getAttribute('data-user-id');
                    const body = document.getElementById('staffTrainingsContent');
                    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
                    const modal = new bootstrap.Modal(staffTrainingsModal);
                    modal.show();
                    fetch('view_staff_trainings.php?id=' + encodeURIComponent(uid), { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { body.innerHTML = html; })
                        .catch(() => { body.innerHTML = '<div class="alert alert-danger">Failed to load trainings</div>'; });
                });
            });
        }

        function initAddTrainingForm() {
            const addForm = document.getElementById('addTrainingForm');
            if (!addForm) return;
            
            addForm.addEventListener('submit', function(e){
                e.preventDefault();
                const fd = new FormData(addForm);
                fetch('training_api.php?action=create', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(data => {
                        const fb = document.getElementById('addTrainingFeedback');
                        if (data.success) {
                            fb.innerHTML = '<div class="alert alert-success">Training added!</div>';
                            setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('addTrainingModal')).hide(); window.location.reload(); }, 800);
                        } else {
                            fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                        }
                    })
                    .catch(() => { document.getElementById('addTrainingFeedback').innerHTML = '<div class="alert alert-danger">Request failed</div>'; });
            });

            // Initialize edit training modal
            const editModal = document.getElementById('editTrainingModal');
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    const form = document.getElementById('editTrainingForm');
                    form.elements['id'].value = button.getAttribute('data-training-id');
                    form.elements['title'].value = button.getAttribute('data-title');
                    form.elements['description'].value = button.getAttribute('data-description') || '';
                    // Handle start_date / end_date fields
                    if (form.elements['start_date']) form.elements['start_date'].value = button.getAttribute('data-start-date') || '';
                    if (form.elements['end_date']) form.elements['end_date'].value = button.getAttribute('data-end-date') || '';
                    if (form.elements['venue']) form.elements['venue'].value = button.getAttribute('data-venue') || '';
                    if (form.elements['nature']) form.elements['nature'].value = button.getAttribute('data-nature') || '';
                    if (form.elements['scope']) form.elements['scope'].value = button.getAttribute('data-scope') || '';
                    if (form.elements['status']) form.elements['status'].value = button.getAttribute('data-status') || 'upcoming';
                });
            }

            // Initialize edit training form submission
            const editForm = document.getElementById('editTrainingForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    const fd = new FormData(editForm);
                    fetch('training_api.php?action=update', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(data => {
                            const fb = document.getElementById('editTrainingFeedback');
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training updated!</div>';
                                setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('editTrainingModal')).hide(); window.location.reload(); }, 800);
                            } else {
                                fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                            }
                        })
                        .catch(() => { document.getElementById('editTrainingFeedback').innerHTML = '<div class="alert alert-danger">Request failed</div>'; });
                });
            }
        }
    </script>

    <!-- Staff Trainings Modal -->
    <div class="modal fade" id="staffTrainingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Staff Trainings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="staffTrainingsContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Notify Staff Modal -->
    <div class="modal fade" id="headBroadcastModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="headBroadcastForm">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Notify Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">This message will be delivered to all staff members assigned to your office.</p>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="Share updates, reminders, or acknowledgements"></textarea>
                        </div>
                        <div id="headBroadcastFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning text-dark">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Training Modal -->
    <div class="modal fade" id="addTrainingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Training</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTrainingForm">
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
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" />
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nature</label>
                                <select name="nature" class="form-control">
                                    <option value="">Select Nature</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Permanent/Regular">Permanent/Regular</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Scope</label>
                                <select name="scope" class="form-control">
                                    <option value="">Select Scope</option>
                                    <option value="Local">Local</option>
                                    <option value="Regional">Regional</option>
                                    <option value="National">National</option>
                                    <option value="International">International</option>
                                </select>
                            </div>
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

    <!-- Edit Training Modal -->
    <div class="modal fade" id="editTrainingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Training</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTrainingForm">
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
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" />
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nature</label>
                                <select name="nature" class="form-control">
                                    <option value="">Select Nature</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Permanent/Regular">Permanent/Regular</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Scope</label>
                                <select name="scope" class="form-control">
                                    <option value="">Select Scope</option>
                                    <option value="Local">Local</option>
                                    <option value="Regional">Regional</option>
                                    <option value="National">National</option>
                                    <option value="International">International</option>
                                </select>
                            </div>
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
    $profileModal = __DIR__ . DIRECTORY_SEPARATOR . 'profile_modal.php';
    if (is_file($profileModal)) {
        include $profileModal;
    }
    ?>

    <script>
        // Initialize profile modal
        if (typeof initProfileModal === 'function') {
            initProfileModal('view');
        }

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

        // Update unread count on office head dashboard
        async function updateUnreadCount() {
            const res = await fetch('get_unread_count.php');
            const j = await res.json();
            const badge = document.getElementById('notificationBadge');
            if (j.count > 0) {
                if (badge) {
                    badge.textContent = j.count;
                    badge.style.display = 'block';
                }
            } else {
                if (badge) badge.style.display = 'none';
            }
        }
        updateUnreadCount();
        setInterval(updateUnreadCount, 5000);
    </script>
</body>
</html>


