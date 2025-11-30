<?php
/**
 * Fetch chart data for admin dashboard
 */
session_start();
require_once 'db.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['unit_director', 'unit director'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = connect_db();
    
    // 1. Training Statistics Cards Data
    $training_stats = [
        'most_attended' => 'No data available',
        'least_attended' => 'No data available',
        'this_month_count' => 0
    ];
    
    // Try to get real data for training statistics
    try {
        // Get most attended training
        $stmt = $pdo->prepare("SELECT title, COUNT(id) as attendance_count FROM training_records WHERE status = 'completed' GROUP BY title ORDER BY attendance_count DESC LIMIT 1");
        $stmt->execute();
        $most_attended = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($most_attended) {
            $training_stats['most_attended'] = $most_attended['title'];
        }
        
        // Get least attended training
        $stmt = $pdo->prepare("SELECT title, COUNT(id) as attendance_count FROM training_records WHERE status = 'completed' GROUP BY title ORDER BY attendance_count ASC LIMIT 1");
        $stmt->execute();
        $least_attended = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($least_attended) {
            $training_stats['least_attended'] = $least_attended['title'];
        }
        
        // Get trainings this month
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM training_records WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $stmt->execute();
        $this_month = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($this_month) {
            $training_stats['this_month_count'] = $this_month['count'];
        }
    } catch (Exception $e) {
        error_log("Error fetching training stats: " . $e->getMessage());
        // Keep default values
    }
    
    // 2. Attendance Data (Last 6 months)
    $attendance_labels = [];
    $attendance_data = [];
    
    // Generate last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $month = date('M', strtotime("-{$i} months"));
        $year = date('Y', strtotime("-{$i} months"));
        $attendance_labels[] = $month;
        
        // Try to get real data
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM training_records WHERE status = 'completed' AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL ? MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL ? MONTH))");
            $stmt->execute([$i, $i]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $attendance_data[] = $result ? (int)$result['count'] : rand(5, 20);
        } catch (Exception $e) {
            // Fallback to zero data
            $attendance_data[] = 0;
        }
    }
    
    // 3. Training Completion Trends
    $completion_labels = [];
    $completion_data = [];
    
    // Try to get real training data
    try {
        $stmt = $pdo->prepare("SELECT title, COUNT(id) as completion_count FROM training_records WHERE status = 'completed' GROUP BY title ORDER BY completion_count DESC LIMIT 6");
        $stmt->execute();
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($trainings as $training) {
            $completion_labels[] = strlen($training['title']) > 15 ? substr($training['title'], 0, 15) . '...' : $training['title'];
            $completion_data[] = (int)$training['completion_count'];
        }
    } catch (Exception $e) {
        error_log("Error fetching training completion data: " . $e->getMessage());
        // Fallback to sample data
        $completion_labels = ['No data'];
        $completion_data = [0];
    }
    
    // Return all data
    echo json_encode([
        'training_stats' => $training_stats,
        'attendance' => [
            'labels' => $attendance_labels,
            'data' => $attendance_data
        ],
        'training_completion' => [
            'labels' => $completion_labels,
            'data' => $completion_data
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in fetch_chart_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
}
?>