<?php
include __DIR__ . '/includes/db_connection.php';

$sql = "SELECT User_ID, Points_Earned FROM Gamification ORDER BY Points_Earned DESC, User_ID ASC";
$result = $conn->query($sql);

$rank = 1;
while ($row = $result->fetch_assoc()) {
    $user_id = $row['User_ID'];

    $update = "UPDATE Gamification SET Leaderboard_Rank = ? WHERE User_ID = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ii", $rank, $user_id);
    $stmt->execute();

    $rank++;
}

echo "✅ Leaderboard ranks updated!";
?>
