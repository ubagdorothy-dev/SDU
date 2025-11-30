<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<div class="alert alert-danger">Not authenticated.</div>';
    exit();
}

$pdo = connect_db();
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, title, description, start_date, end_date, status, venue FROM training_records WHERE user_id = ? ORDER BY start_date DESC");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = [];
}

if (empty($rows)) {
    echo '<div class="alert alert-info">No training records found.</div>';
    exit();
}

echo '<div class="table-responsive">';
echo '<table class="table table-striped">';
echo '<thead><tr><th>Training Title</th><th>Description</th><th>Date</th><th>Status</th><th>Venue</th></tr></thead>';
echo '<tbody>';
foreach ($rows as $r) {
    $title = htmlspecialchars($r['title'] ?? '');
    $desc = htmlspecialchars($r['description'] ?? '');
    $sd = htmlspecialchars($r['start_date'] ?? '');
    $ed = htmlspecialchars($r['end_date'] ?? '');
    $st = htmlspecialchars($r['status'] ?? '');
    $vn = htmlspecialchars($r['venue'] ?? '');
    $badge = $st === 'completed' ? 'bg-success' : 'bg-warning';
    echo '<tr>';
    echo '<td>' . $title . '</td>';
    echo '<td>' . $desc . '</td>';
    echo '<td>' . $sd . ' — ' . $ed . '</td>';
    echo '<td><span class="badge ' . $badge . '">' . ucfirst($st) . '</span></td>';
    echo '<td>' . $vn . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

?>
