<?php
session_start();
include __DIR__ . '/includes/db_connection.php';

header("Content-Type: text/plain");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submission_id']) && isset($_SESSION['user_id'])) {
    $submission_id = $_POST['submission_id'];
    $user_id = $_SESSION['user_id'];

    // Only delete if status is pending and belongs to this user
    $sql = "DELETE FROM TaskSubmissions 
            WHERE Submission_ID = ? AND Submitted_By = ? AND Status = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $submission_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "✅ Submission deleted.";
    } else {
        echo "❌ Cannot delete submission (already approved/rejected or doesn't belong to you).";
    }
} else {
    echo "❌ Invalid request.";
}
?>
