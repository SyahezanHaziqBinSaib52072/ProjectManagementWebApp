<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    die("Access Denied");
}

include __DIR__ . '/includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $task_id = $_POST['task_id'];

    $stmt = $conn->prepare("DELETE FROM tasks WHERE Task_ID = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();

    header("Location: manage_tasks.php");
    exit();
}
?>
