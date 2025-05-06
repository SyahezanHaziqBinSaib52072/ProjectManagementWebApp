<?php
session_start();
include __DIR__ . '/includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $task_id = $_POST['task_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$task_id || !in_array($new_status, ['In Progress', 'Completed'])) {
        echo "❌ Invalid request.";
        exit();
    }

    // Make sure the task belongs to the user
    $stmt = $conn->prepare("UPDATE tasks SET Status = ? WHERE Task_ID = ? AND Assigned_To = ?");
    $stmt->bind_param("sii", $new_status, $task_id, $user_id);
    if ($stmt->execute()) {
        echo "✅ Task marked as $new_status.";
    } else {
        echo "❌ Failed to update.";
    }
}
?>
