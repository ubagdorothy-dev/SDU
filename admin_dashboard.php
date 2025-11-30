<?php
session_start();
include("db.php"); // Include the database connection

// Initialize PDO connection (db.php provides connect_db())
$conn = connect_db();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    header("Location: login.php");
    exit();
}

// Get current user's full name from the session for the welcome message
// NOTE: Ensure your login script sets $_SESSION['full_name']
$current_user_name = 'SDU Director'; // Default fallback name
if (isset($_SESSION['full_name'])) {
    $current_user_name = htmlspecialchars($_SESSION['full_name']);
} else {
    // If not set, try to fetch it from DB using user_id
    try {
        $stmt_name = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt_name->execute([$_SESSION['user_id']]);
        if ($row = $stmt_name->fetch(PDO::FETCH_ASSOC)) {
            $current_user_name = htmlspecialchars($row['full_name']);
            $_SESSION['full_name'] = $current_user_name; // Store for future requests
        }
    } catch (PDOException $e) {
        error_log("Error fetching user name: " . $e->getMessage());
    }
}

// Fetch Dashboard Statistics
try {
    // 1. Total Staff
    $sql_staff = "SELECT COUNT(user_id) FROM users WHERE role = 'staff' AND is_approved = 1";
    $stmt_staff = $conn->query($sql_staff);
    $total_staff = $stmt_staff->fetchColumn();

    // 2. Total Heads
    $sql_heads = "SELECT COUNT(user_id) FROM users WHERE role = 'head' AND is_approved = 1";
    $stmt_heads = $conn->query($sql_heads);
    $total_heads = $stmt_heads->fetchColumn();

    // 3. Training Completion Percentage
    // Calculate actual training completion percentage
    $sql_total_trainings = "SELECT COUNT(*) FROM training_records WHERE user_id IN (SELECT user_id FROM users WHERE role IN ('staff', 'head'))";
    $stmt_total = $conn->query($sql_total_trainings);
    $total_trainings = $stmt_total->fetchColumn();
        
    $sql_completed_trainings = "SELECT COUNT(*) FROM training_records WHERE status = 'completed' AND user_id IN (SELECT user_id FROM users WHERE role IN ('staff', 'head'))";
    $stmt_completed = $conn->query($sql_completed_trainings);
    $completed_trainings = $stmt_completed->fetchColumn();
        
    $training_completion_percentage = ($total_trainings > 0) ? round(($completed_trainings / $total_trainings) * 100) : 0;
        
    // 4. Upcoming Trainings
    $sql_upcoming = "SELECT COUNT(*) FROM training_records WHERE status = 'upcoming' AND start_date >= CURDATE()";
    $stmt_upcoming = $conn->query($sql_upcoming);
    $upcoming_trainings = $stmt_upcoming->fetchColumn();
        
    // 5. Offices with staff
    $sql_offices = "SELECT COUNT(DISTINCT office_code) FROM users WHERE office_code IS NOT NULL AND role IN ('staff', 'head')";
    $stmt_offices = $conn->query($sql_offices);
    $active_offices = $stmt_offices->fetchColumn();
        
    // 6. Chart data - Most attended training
    $sql_most_attended = "SELECT title, COUNT(id) as attendance_count FROM training_records WHERE status = 'completed' GROUP BY title ORDER BY attendance_count DESC LIMIT 1";
    $stmt_most_attended = $conn->query($sql_most_attended);
    $most_attended = $stmt_most_attended->fetch(PDO::FETCH_ASSOC);
    $most_attended_title = $most_attended ? $most_attended['title'] : 'No data available';
        
    // 7. Chart data - Least attended training
    $sql_least_attended = "SELECT title, COUNT(id) as attendance_count FROM training_records WHERE status = 'completed' GROUP BY title ORDER BY attendance_count ASC LIMIT 1";
    $stmt_least_attended = $conn->query($sql_least_attended);
    $least_attended = $stmt_least_attended->fetch(PDO::FETCH_ASSOC);
    $least_attended_title = $least_attended ? $least_attended['title'] : 'No data available';
        
    // 8. Chart data - Trainings this month
    $sql_this_month = "SELECT COUNT(*) as count FROM training_records WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $stmt_this_month = $conn->query($sql_this_month);
    $this_month_result = $stmt_this_month->fetch(PDO::FETCH_ASSOC);
    $this_month_count = $this_month_result ? $this_month_result['count'] : 0;
        
    // 9. Chart data - Attendance data (Last 6 months)
    $attendance_labels = [];
    $attendance_data = [];
        
    // Generate last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('M', strtotime("-{$i} months"));
        $attendance_labels[] = $month;
            
        // Get real data
        $sql_monthly = "SELECT COUNT(*) as count FROM training_records WHERE status = 'completed' AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL ? MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL ? MONTH))";
        $stmt_monthly = $conn->prepare($sql_monthly);
        $stmt_monthly->execute([$i, $i]);
        $result = $stmt_monthly->fetch(PDO::FETCH_ASSOC);
        $attendance_data[] = $result ? (int)$result['count'] : 0;
    }
        
    // 10. Chart data - Training completion trends
    $completion_labels = [];
    $completion_data = [];
        
    $sql_completion = "SELECT title, COUNT(id) as completion_count FROM training_records WHERE status = 'completed' GROUP BY title ORDER BY completion_count DESC LIMIT 6";
    $stmt_completion = $conn->query($sql_completion);
    $trainings = $stmt_completion->fetchAll(PDO::FETCH_ASSOC);
        
    foreach ($trainings as $training) {
        $completion_labels[] = strlen($training['title']) > 15 ? substr($training['title'], 0, 15) . '...' : $training['title'];
        $completion_data[] = (int)$training['completion_count'];
    }
        
    // If no trainings, provide default data
    if (empty($completion_labels)) {
        $completion_labels = ['No data'];
        $completion_data = [0];
    }
    
    // 11. Recent training completions
    $sql_recent_trainings = "SELECT tr.title, tr.created_at as completion_date, u.full_name, COALESCE(u.office_code, 'Unassigned') as office_code FROM training_records tr JOIN users u ON tr.user_id = u.user_id WHERE tr.status = 'completed' ORDER BY tr.created_at DESC LIMIT 10";
    $stmt_recent = $conn->query($sql_recent_trainings);
    $recent_trainings = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($recent_trainings as &$training) {
        $training['completion_date'] = date('M j, Y', strtotime($training['completion_date']));
    }
    
    // 12. Office statistics
    $sql_offices_list = "SELECT code, name FROM offices";
    $stmt_offices_list = $conn->query($sql_offices_list);
    $offices = $stmt_offices_list->fetchAll(PDO::FETCH_ASSOC);
    
    $office_stats = [];
    foreach ($offices as $office) {
        // Get total staff in office
        $sql_staff_count = "SELECT COUNT(*) as count FROM users WHERE COALESCE(office_code, '') = COALESCE(?, '') AND role IN ('staff', 'head')";
        $stmt_staff_count = $conn->prepare($sql_staff_count);
        $stmt_staff_count->execute([$office['code']]);
        $total_staff_count = $stmt_staff_count->fetchColumn();
        
        // Get completed trainings for office
        $sql_completed_count = "SELECT COUNT(*) as count FROM training_records tr JOIN users u ON tr.user_id = u.user_id WHERE COALESCE(u.office_code, '') = COALESCE(?, '') AND tr.status = 'completed'";
        $stmt_completed_count = $conn->prepare($sql_completed_count);
        $stmt_completed_count->execute([$office['code']]);
        $completed_trainings_count = $stmt_completed_count->fetchColumn();
        
        $office_stats[] = [
            'office_name' => $office['name'],
            'office_code' => $office['code'],
            'total_staff' => $total_staff_count,
            'completed_trainings' => $completed_trainings_count
        ];
    }
    
    // 13. Pending approvals count
    $sql_pending = "SELECT COUNT(user_id) FROM users WHERE is_approved = 0 AND role IN ('staff', 'head')";
    $stmt_pending = $conn->query($sql_pending);
    $pending_approvals = $stmt_pending->fetchColumn();

} catch (PDOException $e) {
    $total_staff = 0;
    $total_heads = 0;
    $training_completion_percentage = 0;
    $upcoming_trainings = 0;
    $active_offices = 0;
    $most_attended_title = 'Error';
    $least_attended_title = 'Error';
    $this_month_count = 0;
    $attendance_labels = ['Error'];
    $attendance_data = [0];
    $completion_labels = ['Error'];
    $completion_data = [0];
    $recent_trainings = [];
    $office_stats = [];
    $pending_approvals = 0;
    error_log("Database error on dashboard stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unit Director - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_sidebar.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');

body { 
    font-family: 'Montserrat', sans-serif;
    display: flex; 
    background-color: #f0f2f5;
}
.main-content { flex-grow: 1; padding: 2rem; transition: margin-left 0.3s ease-in-out; }
.sidebar-lg { transition: width 0.3s ease-in-out; }

@media (min-width: 992px) {
    .sidebar-lg { 
        width: 250px; 
        background-color: #1a237e; 
        color: white; 
        height: 100vh; 
        position: fixed; 
        padding-top: 2rem; 
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .sidebar-lg .d-flex.justify-content-between { 
        padding: 0.75rem 1rem; 
    }
    .main-content { margin-left: 250px; }
}

/* Sidebar collapse toggle */
#sidebar-toggle-checkbox:checked ~ .sidebar-lg { width: 80px; padding-top: 1rem; }
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .nav-link span,
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .logo-text,
#sidebar-toggle-checkbox:checked ~ .sidebar-lg h5 { display: none; }
#sidebar-toggle-checkbox:checked ~ .main-content { margin-left: 80px; }
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .d-flex.justify-content-between { padding-left: 0.25rem !important; padding-right: 0.25rem !important; margin-bottom: 1rem !important; }

/* Disable sidebar/main transitions so expand/collapse is instant (match other dashboards) */
.sidebar-lg,
.main-content,
.sidebar-lg .nav-link,
.sidebar-logo,
.logo-text {
    transition: none !important;
    -webkit-transition: none !important;
}
@media (min-width: 992px) {
    body.toggled .sidebar-lg { transition: none !important; }
    body.toggled .main-content { transition: none !important; }
}


/* Consistent sidebar styling */
.sidebar-lg .d-flex h5 { 
    font-weight: 700; 
    margin-right: 0 !important; 
    font-size: 1rem;
}
.sidebar-lg .nav-link { 
    color: #ffffff !important; 
    padding: 12px 20px; 
    border-radius: 5px; 
    margin: 5px 15px; 
    transition: background-color 0.2s; 
    white-space: nowrap; 
    overflow: hidden; 
    display: flex;
    align-items: center;
}
.sidebar-lg .nav-link:hover, 
.sidebar-lg .nav-link.active { 
    background-color: #3f51b5; 
    color: #ffffff !important; 
}
.sidebar-lg .nav-link i {
    min-width: 20px;
    text-align: center;
    margin-right: 15px;
}
#sidebar-toggle-checkbox:checked ~ .sidebar-lg .nav-link i {
    margin-right: 0;
}
.sidebar-lg .btn-toggle { background-color: transparent; border: none; color: #ffffff; padding: 6px 10px; cursor: pointer; }
.sidebar-lg .btn-toggle:focus { box-shadow: none; }

.stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.card { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 2rem 1.5rem; text-align: center; border: none; transition: all 0.3s ease; position: relative; overflow: hidden; }
.card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--card-color), var(--card-color-light)); }
.card h3 { margin: 0 0 1rem; color: var(--card-color); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
.card p { font-size: 2.5rem; font-weight: 900; margin: 0; color: var(--card-color); }
.card:nth-child(1) { --card-color: #6366f1; --card-color-light: #a5b4fc; }
.card:nth-child(2) { --card-color: #10b981; --card-color-light: #6ee7b7; }
.card:nth-child(3) { --card-color: #f59e0b; --card-color-light: #fbbf24; }
.card:nth-child(4) { --card-color: #8b5cf6; --card-color-light: #c4b5fd; }
.card:nth-child(5) { --card-color: #06b6d4; --card-color-light: #67e8f9; }

.content-box { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 2rem; border: 1px solid rgba(255, 255, 255, 0.2); }
.content-box h2 { color: #1e293b; border-bottom: 3px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; font-size: 1.5rem; }



.modal-body .form-label, .modal-body .form-control-plaintext { color: #1e293b !important; }
.modal-dialog { display: flex; align-items: center; min-height: calc(100vh - 1rem); }
.sidebar-logo { height: 30px; width: auto; margin-right: 8px; }

@media (max-width: 991.98px) { .main-content { margin-left: 0 !important; } }
@media (max-width: 768px) { .main-content { padding: 1rem; } .stats-cards { grid-template-columns: 1fr; } }
/* Chart styling */
.chart-card { padding: 1rem; }
.chart-wrapper { overflow: hidden; max-height: 260px; }
.chart-canvas { width: 100%; height: auto; display: block; }
@media (min-width: 1200px) { .chart-wrapper { max-height: 320px; } }
@media (max-width: 767px) { .chart-wrapper { max-height: 200px; } }

/* Progress bar styling */
.progress { 
    border-radius: 10px; 
    background-color: #e9ecef; 
}
.progress-bar { 
    border-radius: 10px; 
    background-color: #6366f1; 
}

/* Offcanvas sidebar for mobile */
.offcanvas {
    background-color: #1a237e;
}
.offcanvas .nav-link {
    color: #ffffff !important;
    padding: 12px 20px;
    border-radius: 5px;
    margin: 5px 0;
    transition: background-color 0.2s;
}
.offcanvas .nav-link:hover,
.offcanvas .nav-link.active {
    background-color: #3f51b5;
    color: #ffffff !important;
}
.offcanvas .nav-link i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}
</style>
</head>
<body id="body">
<input type="checkbox" id="sidebar-toggle-checkbox" style="display: none;">

<!-- Offcanvas Mobile -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar">
  <div class="offcanvas-header text-white">
    <h5 class="offcanvas-title">SDU Menu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i>Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="directory_reports.php"><i class="fas fa-users me-2"></i>Directory & Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i>Pending Approvals <span class="badge bg-danger"><?php echo $pending_approvals; ?></span></a></li>
      <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>
</div>

<!-- Desktop Sidebar -->
<div class="sidebar-lg d-none d-lg-block">
  <div class="d-flex justify-content-between align-items-center px-3 mb-3">
    <div class="d-flex align-items-center">
      <img src="SDU_Logo.png" class="sidebar-logo" alt="SDU">
      <h5 class="m-0 text-white">SDU UNIT DIRECTOR</h5>
    </div>
    <label for="sidebar-toggle-checkbox" class="btn btn-toggle" style="color:#fff;border:none;background:transparent"><i class="fas fa-bars"></i></label>
  </div>
  <ul class="nav flex-column">
    <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php" ><i class="fas fa-chart-line me-2"></i><span> Dashboard</span></a></li>
    <li class="nav-item"><a class="nav-link" href="directory_reports.php" ><i class="fas fa-users me-2"></i><span> Directory & Reports</span></a></li>
    <li class="nav-item"><a class="nav-link" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span> Pending Approvals <span class="badge bg-danger"><?php echo $pending_approvals; ?></span></span></a></li>
    <li class="nav-item mt-auto"><a class="nav-link" href="logout.php" ><i class="fas fa-sign-out-alt me-2"></i><span> Logout</span></a></li>
  </ul>
</div>

<div class="main-content">
    <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
        <i class="fas fa-bars"></i> Menu
    </button>

    <div class="header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="fw-bold mb-2" style="color: #1e293b;">Welcome <?php echo $current_user_name; ?>! </h1>
                <p class="mb-0" style="color: #6b7280;">Here's what's happening with your organization today.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary position-relative" data-bs-toggle="modal" data-bs-target="#inboxModal">
                    <i class="fas fa-bell me-2"></i> Inbox
                    <span id="inboxCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#broadcastModal">
                    <i class="fas fa-bullhorn me-2"></i> Send Notification
                </button>
            </div>
        </div>
    </div>
    
    <div class="stats-cards">
        <div class="card"><h3>Total Staff</h3><p><?php echo $total_staff; ?></p></div>
        <div class="card"><h3>Total Heads</h3><p><?php echo $total_heads; ?></p></div>
        <div class="card"><h3>Trainings Completed</h3><p><?php echo $training_completion_percentage; ?>%</p></div>
        <div class="card"><h3>Upcoming Trainings</h3><p><?php echo $upcoming_trainings; ?></p></div>
        <div class="card"><h3>Active Offices</h3><p><?php echo $active_offices; ?></p></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="content-box chart-card">
                <h2>Total Attendance</h2>
                <p class="text-muted">Completed trainings across the last 6 months</p>
                <div class="chart-wrapper"><canvas id="areaChart" class="chart-canvas" height="220"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="content-box chart-card">
                <h2>Training Statistics</h2>
                <p class="text-muted">Overview of training activity and completion trends</p>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="card" style="padding:1rem; border-radius:12px;">
                            <h3 style="font-size:0.8rem;">Most Attended</h3>
                            <p style="font-size:1.3rem; font-weight:800; color:#6366f1; margin:0;">...</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card" style="padding:1rem; border-radius:12px;">
                            <h3 style="font-size:0.8rem;">Least Attended</h3>
                            <p style="font-size:1.3rem; font-weight:800; color:#10b981; margin:0;">...</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card" style="padding:1rem; border-radius:12px;">
                            <h3 style="font-size:0.8rem;">This Month</h3>
                            <p style="font-size:1.3rem; font-weight:800; color:#f59e0b; margin:0;">...</p>
                        </div>
                    </div>
                </div>

                <div class="chart-wrapper"><canvas id="horizontalBarChart" class="chart-canvas" height="220"></canvas></div>
            </div>
        </div>
    </div>
    
    <!-- Recent Training Completions -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="content-box">
                <h2>Recent Training Completions</h2>
                <p class="text-muted">Latest training completions by staff members</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Training Title</th>
                                <th>Completion Date</th>
                                <th>Office</th>
                            </tr>
                        </thead>
                        <tbody id="recentTrainings">
                            <?php if (empty($recent_trainings)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No recent training completions</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_trainings as $training): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($training['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($training['title']); ?></td>
                                        <td><?php echo htmlspecialchars($training['completion_date']); ?></td>
                                        <td><?php echo htmlspecialchars($training['office_code']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Office-wise Training Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="content-box">
                <h2>Office-wise Training Statistics</h2>
                <p class="text-muted">Training completion rates by office</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Office</th>
                                <th>Total Staff</th>
                                <th>Completed Trainings</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody id="officeStats">
                            <?php if (empty($office_stats)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No office statistics available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($office_stats as $office): ?>
                                    <?php 
                                        $completionRate = $office['total_staff'] > 0 ? round(($office['completed_trainings'] / $office['total_staff']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($office['office_name']); ?></td>
                                        <td><?php echo htmlspecialchars($office['total_staff']); ?></td>
                                        <td><?php echo htmlspecialchars($office['completed_trainings']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $completionRate; ?>%" aria-valuenow="<?php echo $completionRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span><?php echo $completionRate; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    

<div class="modal fade" id="broadcastModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="broadcastForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Send Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Audience</label>
                            <select class="form-select" name="audience" required>
                                <option value="all">All Users</option>
                                <option value="staff">Staff Only</option>
                                <option value="heads">Office Heads Only</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Subject (optional)</label>
                            <input type="text" class="form-control" name="subject" placeholder="Optional subject line">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="5" placeholder="Share updates, reminders, or announcements" required></textarea>
                    </div>
                    <div id="broadcastFeedback" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inbox Modal -->
<div class="modal fade" id="inboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-inbox me-2"></i>Notifications Inbox</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <button id="markAllReadBtn" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-check-double me-1"></i>Mark All Read
                        </button>
                        <button id="deleteAllBtn" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash-alt me-1"></i>Delete All
                        </button>
                    </div>
                    <div>
                        <span id="notificationCount" class="text-muted small">Loading notifications...</span>
                    </div>
                </div>
                <div id="notificationsList">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Loading notifications...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // --- Update Training Statistics Cards ---
    const cards = document.querySelectorAll('.row.g-2.mb-3 .card p');
    if (cards.length >= 3) {
        cards[0].textContent = '<?php echo addslashes($most_attended_title); ?>';
        cards[1].textContent = '<?php echo addslashes($least_attended_title); ?>';
        cards[2].textContent = '<?php echo $this_month_count; ?> Trainings';
    }

    // --- Area Chart (Total Attendance) ---
    const areaCtx = document.getElementById('areaChart').getContext('2d');
    const areaGradient = areaCtx.createLinearGradient(0, 0, 0, 220);
    areaGradient.addColorStop(0, 'rgba(99,102,241,0.16)');
    areaGradient.addColorStop(1, 'rgba(99,102,241,0.02)');

    new Chart(areaCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($attendance_labels); ?>,
            datasets: [{
                label: 'Completed trainings',
                data: <?php echo json_encode($attendance_data); ?>,
                borderColor: '#1e3a8a',
                backgroundColor: areaGradient,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#1e3a8a',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });

    // --- Horizontal Bar Chart (Training completion trends) ---
    const hbarCtx = document.getElementById('horizontalBarChart').getContext('2d');
    new Chart(hbarCtx, {
        type: 'bar',
        data: {
            indexAxis: 'y',
            labels: <?php echo json_encode($completion_labels); ?>,
            datasets: [
                {
                    label: 'Trainings Completed',
                    data: <?php echo json_encode($completion_data); ?>,
                    backgroundColor: ['#7841f7ff','#7d4af5ff','#7d4decff','#7642f0ff','#8b5cf6','#7d4ceeff'],
                    borderRadius: 6
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } },
                y: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
    
    // Load recent training completions
    // loadRecentTrainings();
    
    // Load office statistics
    // loadOfficeStats();


    // --- Broadcast Modal Form Submission Logic ---
    const broadcastForm = document.getElementById('broadcastForm');
    const broadcastFeedback = document.getElementById('broadcastFeedback');

    if (broadcastForm) {
        broadcastForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendButton = this.querySelector('button[type="submit"]');
            const originalButtonText = sendButton.innerHTML;

            // Disable button and show loading state
            sendButton.disabled = true;
            sendButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
            broadcastFeedback.innerHTML = ''; // Clear previous feedback

            fetch('send_notification.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    broadcastFeedback.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    broadcastForm.reset(); // Clear form on success
                    // Optionally close the modal after a brief moment
                    setTimeout(() => {
                        const modalEl = document.getElementById('broadcastModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }, 2000);
                } else {
                    broadcastFeedback.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error sending notification:', error);
                broadcastFeedback.innerHTML = `<div class="alert alert-danger">An unexpected error occurred. Please try again.</div>`;
            })
            .finally(() => {
                // Re-enable button
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonText;
            });
        });
    }

    // Load count initially and when modal opened
    fetchInboxCount();
    var inboxModal = document.getElementById('inboxModal');
    if (inboxModal) inboxModal.addEventListener('show.bs.modal', loadInboxList);
});

// Fetch inbox count
function fetchInboxCount() {
    fetch('admin_api.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const unreadCount = data.notifications.filter(n => !n.is_read).length;
                const inboxCountElement = document.getElementById('inboxCount');
                if (inboxCountElement) {
                    if (unreadCount > 0) {
                        inboxCountElement.textContent = unreadCount;
                        inboxCountElement.style.display = 'inline';
                    } else {
                        inboxCountElement.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => console.error('Error fetching inbox count:', error));
}

// Load inbox list
function loadInboxList() {
    fetch('admin_api.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderNotifications(data.notifications);
            } else {
                document.getElementById('notificationsList').innerHTML = 
                    '<div class="alert alert-danger">Failed to load notifications</div>';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            document.getElementById('notificationsList').innerHTML = 
                '<div class="alert alert-danger">Error loading notifications</div>';
        });
}

// Render notifications
function renderNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    const countElement = document.getElementById('notificationCount');
    
    if (countElement) {
        countElement.textContent = `${notifications.length} notifications`;
    }
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                <h5>No notifications</h5>
                <p class="text-muted">You don't have any notifications at the moment.</p>
            </div>`;
        return;
    }
    
    let html = '<div class="list-group">';
    
    notifications.forEach(notification => {
        const isUnreadClass = !notification.is_read ? 'list-group-item-warning' : '';
        const unreadIndicator = !notification.is_read ? '<span class="badge bg-warning me-2">NEW</span>' : '';
        
        html += `
            <div class="list-group-item ${isUnreadClass}" data-notification-id="${notification.id}">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${unreadIndicator}${notification.title}</h6>
                    <small class="text-muted">${notification.time_ago}</small>
                </div>
                <p class="mb-1">${notification.message}</p>
                <div class="mt-2">
                    ${!notification.is_read ? 
                        `<button class="btn btn-sm btn-outline-primary mark-read-btn" data-id="${notification.id}">
                            <i class="fas fa-check me-1"></i>Mark Read
                        </button>` : ''}
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${notification.id}">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Add event listeners for mark read buttons
    document.querySelectorAll('.mark-read-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            markAsRead([id]);
        });
    });
    
    // Add event listeners for delete buttons
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            deleteNotifications([id]);
        });
    });
}

// Mark notifications as read
function markAsRead(ids) {
    fetch('admin_api.php?action=mark_notifications_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications
            loadInboxList();
            // Update inbox count
            fetchInboxCount();
        } else {
            alert('Failed to mark notifications as read');
        }
    })
    .catch(error => {
        console.error('Error marking as read:', error);
        alert('Error marking notifications as read');
    });
}

// Delete notifications
function deleteNotifications(ids) {
    if (!confirm('Are you sure you want to delete these notifications?')) {
        return;
    }
    
    fetch('admin_api.php?action=delete_notifications', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications
            loadInboxList();
            // Update inbox count
            fetchInboxCount();
        } else {
            alert('Failed to delete notifications');
        }
    })
    .catch(error => {
        console.error('Error deleting notifications:', error);
        alert('Error deleting notifications');
    });
}

// Mark all as read
document.getElementById('markAllReadBtn').addEventListener('click', function() {
    fetch('admin_api.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Filter unread notifications
                const unreadIds = data.notifications
                    .filter(n => !n.is_read)
                    .map(n => n.id);
                
                if (unreadIds.length > 0) {
                    markAsRead(unreadIds);
                } else {
                    alert('No unread notifications to mark as read');
                }
            }
        });
});

// Delete all notifications
document.getElementById('deleteAllBtn').addEventListener('click', function() {
    fetch('admin_api.php?action=get_notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.notifications.length > 0) {
                    const allIds = data.notifications.map(n => n.id);
                    deleteNotifications(allIds);
                } else {
                    alert('No notifications to delete');
                }
            }
        });
});

// Refresh inbox count periodically
setInterval(fetchInboxCount, 30000); // Every 30 seconds

// Load count initially and when modal opened
fetchInboxCount();
var inboxModal = document.getElementById('inboxModal');
if (inboxModal) inboxModal.addEventListener('show.bs.modal', loadInboxList);
});
</script>
</body>
</html>