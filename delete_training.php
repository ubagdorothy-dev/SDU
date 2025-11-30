<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pdo = connect_db();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';
$training_id = intval($_GET['id'] ?? 0);

if (!$training_id) {
    $_SESSION['message'] = 'Invalid training ID';
    header("Location: " . (strpos($user_role, 'head') !== false ? 'office_head_dashboard.php' : 'staff_dashboard.php'));
    exit();
}

try {
    // Check if training exists and user has permission
    $stmt = $pdo->prepare("SELECT user_id, office_code FROM training_records WHERE id = ?");
    $stmt->execute([$training_id]);
    $training = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$training) {
        $_SESSION['message'] = 'Training record not found';
        header("Location: " . (strpos($user_role, 'head') !== false ? 'office_head_dashboard.php' : 'staff_dashboard.php'));
        exit();
    }
    
    // Authorization check
    $can_delete = false;
    if ($user_role === 'staff' && $training['user_id'] === $user_id) {
        $can_delete = true;
    } elseif ($user_role === 'head') {
        // Office head can delete training for staff in their office
        $stmt = $pdo->prepare("SELECT office_code FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $head_office = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($head_office && $head_office['office_code'] === $training['office_code']) {
            $can_delete = true;
        }
    } elseif (in_array($user_role, ['unit_director', 'unit director', 'admin'])) {
        // Unit directors and admins can delete any training
        $can_delete = true;
    }
    
    if (!$can_delete) {
        $_SESSION['message'] = 'You do not have permission to delete this training record';
        header("Location: " . (strpos($user_role, 'head') !== false ? 'office_head_dashboard.php' : 'staff_dashboard.php'));
        exit();
    }
    
    // Delete training record
    $stmt = $pdo->prepare("DELETE FROM training_records WHERE id = ?");
    $stmt->execute([$training_id]);
    
    $_SESSION['message'] = 'Training record deleted successfully';
    
} catch (Exception $e) {
    $_SESSION['message'] = 'Error deleting training: ' . $e->getMessage();
}

// Redirect back to appropriate dashboard
if (strpos($user_role, 'head') !== false) {
    header("Location: office_head_dashboard.php");
} else {
    header("Location: staff_dashboard.php");
}
exit();
?>
