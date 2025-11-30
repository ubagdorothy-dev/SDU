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
    <title>Pending Account Approvals - SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f0f2f5 ;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            max-width: 1000px;
        }

        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #1a237e;
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header-actions a {
            padding: 10px 20px;
            background-color: #1a237e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .header-actions a:hover {
            background-color: #141b63;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .pending-users-box {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .pending-users-box h2 {
            color: #1a237e;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            font-weight: 700;
        }

        .no-pending {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .no-pending i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .pending-item {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .pending-item:hover {
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .pending-info {
            flex: 1;
        }

        .pending-info h4 {
            color: #1a237e;
            margin: 0 0 8px;
            font-weight: 700;
        }

        .pending-info p {
            color: #6c757d;
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .role-badge.staff {
            background-color: #cfe2ff;
            color: #084298;
        }

        .role-badge.head {
            background-color: #cff4fc;
            color: #055160;
        }

        .pending-actions {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }

        .btn-approve, .btn-reject {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card p {
            color: #6c757d;
            margin: 0 0 10px;
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-card h3 {
            color: #667eea;
            font-size: 2rem;
            margin: 0;
            font-weight: 900;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .pending-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .pending-actions {
                width: 100%;
                margin-left: 0;
                margin-top: 15px;
            }

            .btn-approve, .btn-reject {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><i class="fas fa-clipboard-check"></i> Pending Approvals</h1>
            </div>
            <div class="header-actions">
                <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($approval_message): ?>
            <div class="alert alert-<?php echo $approval_type; ?>">
                <i class="fas fa-<?php echo ($approval_type === 'success') ? 'check-circle' : (($approval_type === 'warning') ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                <?php echo $approval_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <p>Pending Accounts</p>
                <h3><?php echo count($pending_users); ?></h3>
            </div>
            <div class="stat-card">
                <p>Total Users</p>
                <h3>
                    <?php
                    $total_sql = "SELECT COUNT(*) as count FROM users WHERE role NOT IN ('unit_director', 'unit director')";
                    $total_stmt = $pdo->prepare($total_sql);
                    $total_stmt->execute();
                    $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
                    echo $total_result['count'];
                    ?>
                </h3>
            </div>
        </div>

        <!-- Pending Users -->
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
                                <button type="submit" class="btn-approve" onclick="return confirm('Approve this account?');">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-reject" onclick="return confirm('Reject and remove this account?');">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
