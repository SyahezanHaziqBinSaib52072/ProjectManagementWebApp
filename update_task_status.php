<?php
session_start();
include __DIR__ . '/includes/db_connection.php';
include __DIR__ . '/includes/level_functions.php';


header('Content-Type: text/plain');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submission_id = $_POST['submission_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$submission_id || !in_array($status, ['Approved', 'Rejected'])) {
        echo "❌ Invalid parameters.";
        exit();
    }

    // Get submission + task + user info
    $getQuery = "
        SELECT TS.Task_ID, TS.Submitted_By, TS.Status AS CurrentStatus, T.Priority
        FROM tasksubmissions TS
        JOIN tasks T ON TS.Task_ID = T.Task_ID
        WHERE TS.Submission_ID = ?
    ";
    $stmt = $conn->prepare($getQuery);
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "❌ Submission not found.";
        exit();
    }

    $submission = $result->fetch_assoc();

    if ($submission['CurrentStatus'] !== 'Pending') {
        echo "⚠️ This submission has already been reviewed.";
        exit();
    }

    // Update task submission status
    $updateQuery = "UPDATE tasksubmissions SET Status = ?, Submission_Date = NOW() WHERE Submission_ID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $status, $submission_id);
    $stmt->execute();

    // Get the Task ID for later
    $taskID = $submission['Task_ID'];

    if ($status === 'Approved') {
        // ✅ If approved, apply gamification logic and mark task as Completed

        $userID = $submission['Submitted_By'];
        $priority = $submission['Priority'];

        // Determine points based on priority
        $points = match ($priority) {
            'High' => 15,
            'Medium' => 10,
            'Low' => 5,
            default => 0
        };

        // Add points
        $updatePoints = "UPDATE gamification SET Points_Earned = Points_Earned + ? WHERE User_ID = ?";
        $stmt = $conn->prepare($updatePoints);
        $stmt->bind_param("ii", $points, $userID);
        $stmt->execute();

        // Update level
        $stmt = $conn->prepare("SELECT Points_Earned FROM gamification WHERE User_ID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalPoints = $result->fetch_assoc()['Points_Earned'];

        $newLevel = determineLevel($totalPoints);
        $stmt = $conn->prepare("UPDATE gamification SET Level = ? WHERE User_ID = ?");
        $stmt->bind_param("ii", $newLevel, $userID);
        $stmt->execute();

        // Update badge
        $badge = '';
        if ($totalPoints >= 100) $badge = 'Legendary Leader';
        elseif ($totalPoints >= 50) $badge = 'Task Master';
        elseif ($totalPoints >= 10) $badge = 'Rookie Contributor';

        if ($badge) {
            $stmt = $conn->prepare("UPDATE gamification SET Badges_Achieved = ? WHERE User_ID = ?");
            $stmt->bind_param("si", $badge, $userID);
            $stmt->execute();
        }

        // ✅ Mark the task itself as Completed
        $stmt = $conn->prepare("UPDATE tasks SET Status = 'Completed' WHERE Task_ID = ?");
        $stmt->bind_param("i", $taskID);
        $stmt->execute();

        include __DIR__ . '/php_html/update_leaderboard.php';

    } elseif ($status === 'Rejected') {
        // ❌ If rejected, we DO NOT complete the task
        // Task stays "In Progress" for user to resubmit
        // No need to update task status
    }

    echo "✅ Submission $status successfully.";
} else {
    echo "❌ Invalid request.";
}
?>
