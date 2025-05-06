<?php
session_start();
include __DIR__ . '/includes/db_connection.php';

$user_id = $_SESSION['user_id'];
$reward_id = $_POST['reward_id'] ?? null;

if (!$reward_id) {
    echo "Invalid reward ID.";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM Rewards WHERE Reward_ID = ?");
$stmt->bind_param("i", $reward_id);
$stmt->execute();
$reward = $stmt->get_result()->fetch_assoc();

if (!$reward) {
    echo "Reward not found.";
    exit();
}

$stmt = $conn->prepare("SELECT Points_Earned FROM Gamification WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userPoints = $stmt->get_result()->fetch_assoc()['Points_Earned'];

if ($userPoints < $reward['Point_Cost']) {
    $_SESSION['redeem_error'] = "❌ Not enough points to redeem this reward.";
    header("Location: rewards.php");
    exit();
}

// Deduct and log
$stmt = $conn->prepare("UPDATE Gamification SET Points_Earned = Points_Earned - ? WHERE User_ID = ?");
$stmt->bind_param("ii", $reward['Point_Cost'], $user_id);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO Redemptions (Reward_ID, User_ID) VALUES (?, ?)");
$stmt->bind_param("ii", $reward_id, $user_id);
$stmt->execute();

$_SESSION['redeem_success'] = "✅ Successfully redeemed '{$reward['Name']}'!";
header("Location: rewards.php");
exit();
?>
