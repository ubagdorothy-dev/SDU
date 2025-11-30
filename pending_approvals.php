<?php
session_start();
require_once 'db.php';

$pdo = connect_db();

// Check if user is logged in and is a unit director (accept common role variants)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$approval_message = '';
$approval_type = '';

// Handle approve/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $pending_user_id = intval($_POST['user_id']);
    
    try {
        if ($action === 'approve') {
            $sql = "UPDATE users SET is_approved = TRUE WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pending_user_id]);
            
            $_SESSION['approval_message'] = "✓ Account approved successfully!";
            $_SESSION['approval_type'] = "success";
            
        } elseif ($action === 'reject') {
            $sql = "DELETE FROM users WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pending_user_id]);
            
            $_SESSION['approval_message'] = "✗ Account rejected and removed from the system.";
            $_SESSION['approval_type'] = "warning";
        }
        
        header("Location: pending_approvals.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['approval_message'] = "Error processing request: " . $e->getMessage();
        $_SESSION['approval_type'] = "error";
    }
}

// Display message if set
if (isset($_SESSION['approval_message'])) {
    $approval_message = $_SESSION['approval_message'];
    $approval_type = $_SESSION['approval_type'];
    unset($_SESSION['approval_message']);
    unset($_SESSION['approval_type']);
}

// Fetch pending accounts (not yet approved)
$sql = "SELECT user_id, full_name, email, role, office_code, created_at 
    FROM users 
    WHERE is_approved = FALSE AND role NOT IN ('unit_director', 'unit director')
    ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending_users = [];
    $approval_message = "Error fetching pending accounts: " . $e->getMessage();
    $approval_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unit Director - Pending Approvals</title>
<!-- Bootstrap & Font Awesome Links -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_sidebar.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');

body { 
    font-family: 'Montserrat', sans-serif;
}

.stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.card { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 2rem 1.5rem; text-align: center; border: none; transition: all 0.3s ease; position: relative; overflow: hidden; }
.card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--card-color), var(--card-color-light)); }
.card h3 { margin: 0 0 1rem; color: var(--card-color); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
.card p { font-size: 2.5rem; font-weight: 900; margin: 0; color: var(--card-color); }
.card:nth-child(1) { --card-color: #6366f1; --card-color-light: #a5b4fc; }
.card:nth-child(2) { --card-color: #10b981; --card-color-light: #6ee7b7; }
.card:nth-child(3) { --card-color: #f59e0b; --card-color-light: #fbbf24; }

.content-box { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 2rem; border: 1px solid rgba(255, 255, 255, 0.2); }
.content-box h2 { color: #1e293b; border-bottom: 3px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; font-size: 1.5rem; }

.pending-users-box { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
.pending-users-box h2 { color: #1a237e; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #667eea; font-weight: 700; }
.pending-item { background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 8px; padding: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease; }
.pending-item:hover { box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2); }
.pending-info h4 { color: #1a237e; margin: 0 0 8px; font-weight: 700; }
.role-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-top: 8px; }
.role-badge.staff { background-color: #cfe2ff; color: #084298; }
.role-badge.head { background-color: #cff4fc; color: #055160; }

@media (max-width: 768px) { .main-content { padding: 1rem; } .stats-cards { grid-template-columns: 1fr; } }
</style>
</head>
<body id="body">
<input type="checkbox" id="sidebar-toggle-checkbox" style="display: none;">

<!-- Mobile Offcanvas Menu -->
<div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header text-white">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">SDU Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#inboxModal"><i class="fas fa-inbox me-2"></i> <span>Inbox</span> <span id="inboxCount" class="badge bg-danger ms-2" style="font-size:10px;">0</span></a></li>
            <li class="nav-item"><a class="nav-link" href="directory_reports.php"><i class="fas fa-users me-2"></i> <span>Directory & Reports</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span>Pending Approvals</span></a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
        </ul>
    </div>
</div>

<!-- Desktop Sidebar -->
<div class="sidebar-lg d-none d-lg-block">
    <div class="d-flex justify-content-between align-items-center px-3 mb-3">
        <div class="d-flex align-items-center">
            <img src="SDU_Logo.png" alt="SDU Logo" class="sidebar-logo">
            <h5 class="m-0 text-white"><span class="logo-text">SDU UNIT DIRECTOR</span></h5>
        </div>
        <label for="sidebar-toggle-checkbox" id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></label>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="directory_reports.php"><i class="fas fa-users me-2"></i> <span>Directory & Reports</span></a></li>
        <li class="nav-item"><a class="nav-link active" href="pending_approvals.php"><i class="fas fa-clipboard-check me-2"></i> <span>Pending Approvals</span></a></li>
        <li class="nav-item mt-auto"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content Area -->
<div class="main-content">
    <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
        <i class="fas fa-bars"></i> Menu
    </button>

    <div class="header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="fw-bold mb-2" style="color: #1e293b;"><i class="fas fa-clipboard-check me-2"></i> Pending Approvals</h1>
                <p class="mb-0" style="color: #6b7280;">Review and approve or reject pending account requests.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Statistics (kept similar to previous layout) -->
    <div class="stats-cards mb-4">
        <div class="card"><h3>Pending Accounts</h3><p><?php echo count($pending_users); ?></p></div>
        <div class="card"><h3>Total Users</h3><p>
            <?php
                $total_sql = "SELECT COUNT(*) as count FROM users WHERE role NOT IN ('unit_director', 'unit director')";
                $total_stmt = $pdo->prepare($total_sql);
                $total_stmt->execute();
                $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
                echo $total_result['count'];
            ?>
        </p></div>
        <div class="card"><h3>Pending Rate</h3><p>
            <?php
                $total = max(1, intval($total_result['count']));
                $pending = intval(count($pending_users));
                echo round(($pending / $total) * 100) . '%';
            ?>
        </p></div>
    </div>

    <div class="content-box">
        <!-- Alert Messages -->
        <?php if ($approval_message): ?>
            <div class="alert alert-<?php echo $approval_type; ?>">
                <i class="fas fa-<?php echo ($approval_type === 'success') ? 'check-circle' : (($approval_type === 'warning') ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                <?php echo $approval_message; ?>
            </div>
        <?php endif; ?>

        <!-- Pending Users List -->
        <div class="pending-users-box">
            <h2><i class="fas fa-hourglass-half"></i> Pending Accounts</h2>

            <?php if (empty($pending_users)): ?>
                <div class="no-pending">
                    <i class="fas fa-check-circle"></i>
                    <h4>All accounts approved!</h4>
                    <p>There are no pending accounts at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_users as $user): ?>
                    <div class="pending-item">
                        <div class="pending-info">
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Office:</strong> <?php echo htmlspecialchars($user['office_code'] ?? 'Not assigned'); ?></p>
                            <p><strong>Registered:</strong> <?php echo date('M d, Y @ h:i A', strtotime($user['created_at'] ?? 'now')); ?></p>
                            <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                <i class="fas fa-badge"></i> <?php echo strtoupper($user['role']); ?>
                            </span>
                        </div>
                        <div class="pending-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this account?');">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Reject and remove this account?');">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
