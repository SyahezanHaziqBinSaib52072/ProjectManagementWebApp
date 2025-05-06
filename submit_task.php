<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/includes/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Member') {
    echo json_encode(['status'=>'error','message'=>'Access denied.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid request method.']);
    exit();
}

$user_id     = $_SESSION['user_id'];
$task_id     = intval($_POST['task_id'] ?? 0);
$description = trim($_POST['description']  ?? '');
$filePath    = null;

// 1) Check for existing PENDING or APPROVED submissions only
$check = $conn->prepare("
    SELECT Submission_ID 
    FROM TaskSubmissions 
    WHERE Task_ID     = ? 
      AND Submitted_By = ?
      AND Status IN ('Pending','Approved')
");
$check->bind_param("ii", $task_id, $user_id);
$check->execute();
$exists = $check->get_result()->num_rows;

if ($exists > 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'You already have a Pending or Approved submission for this task.'
    ]);
    exit();
}

// 2) Handle optional file upload
if (!empty($_FILES['file']['name'])) {
    $uploadDir  = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $tmpName    = $_FILES['file']['tmp_name'];
    $original   = basename($_FILES['file']['name']);
    $targetName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/','_', $original);
    $targetPath = $uploadDir . $targetName;

    if (move_uploaded_file($tmpName, $targetPath)) {
        // Store relative path for download links
        $filePath = 'uploads/' . $targetName;
    } else {
        echo json_encode(['status'=>'error','message'=>'File upload failed.']);
        exit();
    }
}

// 3) Insert new submission with Status = 'Pending'
$insert = $conn->prepare("
    INSERT INTO TaskSubmissions 
        (Task_ID, Submitted_By, Description, FilePath, Status, Submission_Date)
    VALUES (?, ?, ?, ?, 'Pending', NOW())
");
$insert->bind_param("iiss", $task_id, $user_id, $description, $filePath);
if ($insert->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Task submitted successfully. Awaiting approval.'
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $conn->error
    ]);
}

exit();
?>
