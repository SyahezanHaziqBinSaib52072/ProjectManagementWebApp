<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Fetch this user’s redemption history
$sql = "
    SELECT 
      R.Redemption_ID,
      R.Redeemed_At,
      Re.Reward_ID,
      Re.Name,
      Re.Description,
      Re.Image_URL,
      Re.Point_Cost
    FROM Redemptions R
    JOIN Rewards Re 
      ON R.Reward_ID = Re.Reward_ID
    WHERE R.User_ID = ?
    ORDER BY R.Redeemed_At DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Redemptions</title>
  <link rel="stylesheet" href="assets/css/styles.css">

</head>
<body>

<div class="sidebar">
  <h2>Dashboard</h2>
  <ul>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="my_project.php">My Project</a></li>
    <li><a href="claim_task.php">Claim Task</a></li>
    <li><a href="rewards.php">🎁 Rewards</a></li>
    <li><a href="redemption_history.php" class="active">📜 My Redemptions</a></li>
  </ul>
  <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="main-content">
  <header>
    <h1>My Redemptions</h1>
  </header>

  <?php if ($history && $history->num_rows > 0): ?>
    <div class="history-grid">
      <?php while ($h = $history->fetch_assoc()): ?>
        <div class="history-card">
          <?php if (!empty($h['Image_URL'])): ?>
            <img 
              src="<?= htmlspecialchars($h['Image_URL']) ?>" 
              alt="<?= htmlspecialchars($h['Name']) ?>" 
              class="history-image"
            >
          <?php endif; ?>
          <h3><?= htmlspecialchars($h['Name']) ?></h3>
          <p class="spent"><?= htmlspecialchars($h['Point_Cost']) ?> pts</p>
          <small class="redemption-date">
            <?= date("M j, Y", strtotime($h['Redeemed_At'])) ?>
          </small>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="no-history">
      You haven't redeemed any rewards yet.
    </div>
  <?php endif; ?>
</div>

</body>
</html>
