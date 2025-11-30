<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = connect_db();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    if ($action === 'create') {
        // debug: log incoming post data to help diagnose client-side failures
        // write an initial debug entry so we can inspect what the server receives
        try {
            $debugPath = __DIR__ . DIRECTORY_SEPARATOR . 'training_api_debug.log';
            $dbg = [
                'ts' => date('c'),
                'event' => 'create_called',
                'session_user_id' => $_SESSION['user_id'] ?? null,
                'post' => $_POST,
                'files' => isset($_FILES) ? array_map(function($f){ return ['name'=>$f['name'],'size'=>$f['size'],'error'=>$f['error']]; }, $_FILES) : []
            ];
            @file_put_contents($debugPath, json_encode($dbg) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // ignore logging failures
        }
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $completion_date = $_POST['completion_date'] ?? null;
        if ((!$start_date || !$end_date) && $completion_date) {
            $start_date = $completion_date;
            $end_date = $completion_date;
        }
        $status = $_POST['status'] ?? 'upcoming';
        $venue = $_POST['venue'] ?? null;

        if (!$title || !$start_date || !$end_date) {
            throw new Exception('Title and start/end dates are required');
        }

        // infer office_code from users table if available
        $stmt = $pdo->prepare("SELECT office_code FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $office = $stmt->fetch(PDO::FETCH_ASSOC);
        $office_code = $office['office_code'] ?? null;

        $ins = $pdo->prepare("INSERT INTO training_records (user_id, title, description, start_date, end_date, status, venue, office_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$user_id, $title, $description, $start_date, $end_date, $status, $venue, $office_code]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit();
    }

    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('Missing id');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $completion_date = $_POST['completion_date'] ?? null;
        if ((!$start_date || !$end_date) && $completion_date) {
            $start_date = $completion_date;
            $end_date = $completion_date;
        }
        $status = $_POST['status'] ?? 'upcoming';
        $venue = $_POST['venue'] ?? null;
        if ($venue === null) {
            $cur = $pdo->prepare("SELECT venue FROM training_records WHERE id = ? AND user_id = ?");
            $cur->execute([$id, $user_id]);
            $row = $cur->fetch(PDO::FETCH_ASSOC);
            if ($row && array_key_exists('venue', $row)) {
                $venue = $row['venue'];
            }
        }

        $upd = $pdo->prepare("UPDATE training_records SET title = ?, description = ?, start_date = ?, end_date = ?, status = ?, venue = ? WHERE id = ? AND user_id = ?");
        $upd->execute([$title, $description, $start_date, $end_date, $status, $venue, $id, $user_id]);

        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'upload_proof') {
        // file upload expects field 'proof' and 'training_id'
        $training_id = intval($_POST['training_id'] ?? 0);
        if (!$training_id) throw new Exception('Missing training_id');
        if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) throw new Exception('No file uploaded');

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'proofs';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $orig = $_FILES['proof']['name'];
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $safe = 'proof_' . $training_id . '_' . $user_id . '_' . time() . '.' . $ext;
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $safe;

        if (!move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) throw new Exception('Failed to save file');

        // record proof
        $relPath = 'uploads/proofs/' . $safe;
        $ins = $pdo->prepare("INSERT INTO training_proofs (training_id, user_id, file_path, status) VALUES (?, ?, ?, 'pending')");
        $ins->execute([$training_id, $user_id, $relPath]);

        // mark training as having proof uploaded
        $u = $pdo->prepare("UPDATE training_records SET proof_uploaded = 1 WHERE id = ? AND user_id = ?");
        $u->execute([$training_id, $user_id]);

        // notify office head(s) and unit director(s)
        // find office_code for training
        $stmt = $pdo->prepare("SELECT office_code, title FROM training_records WHERE id = ?");
        $stmt->execute([$training_id]);
        $tr = $stmt->fetch(PDO::FETCH_ASSOC);
        $office_code = $tr['office_code'] ?? null;
        $title = $tr['title'] ?? 'Training';

        $message = "Proof of completion uploaded for: $title";
        // notify unit directors (role 'unit director')
        $nd = $pdo->prepare("SELECT user_id FROM users WHERE role IN ('unit_director','unit director')");
        $nd->execute();
        $unitDirectors = $nd->fetchAll(PDO::FETCH_COLUMN);
        foreach ($unitDirectors as $ud) {
            $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $insn->execute([$ud, 'Proof Uploaded', $message]);
        }

        // notify office heads within same office_code
        if ($office_code) {
            $nh = $pdo->prepare("SELECT user_id FROM users WHERE role = 'head' AND office_code = ?");
            $nh->execute([$office_code]);
            $heads = $nh->fetchAll(PDO::FETCH_COLUMN);
            foreach ($heads as $h) {
                $insn = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $insn->execute([$h, 'Proof Uploaded', $message]);
            }
        }

        echo json_encode(['success' => true, 'file' => $relPath]);
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Exception $e) {
    // log the exception for server-side debugging
    try {
        $debugPath = __DIR__ . DIRECTORY_SEPARATOR . 'training_api_debug.log';
        $err = [ 'ts' => date('c'), 'event' => 'exception', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString() ];
        @file_put_contents($debugPath, json_encode($err) . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Exception $ee) {
        // ignore
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
