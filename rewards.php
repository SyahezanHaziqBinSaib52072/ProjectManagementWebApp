<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

// ── Flash messages from redeem_reward.php ──
$redeem_error   = $_SESSION['redeem_error']   ?? null;
$redeem_success = $_SESSION['redeem_success'] ?? null;
unset($_SESSION['redeem_error'], $_SESSION['redeem_success']);

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// 1) Fetch user’s current points
$ptsStmt = $conn->prepare("
    SELECT `Points_Earned`
    FROM `Gamification`
    WHERE `User_ID` = ?
");
$ptsStmt->bind_param("i", $user_id);
$ptsStmt->execute();
$ptsStmt->bind_result($userPts);
$ptsStmt->fetch();
$ptsStmt->close();

// 2) Fetch rewards using the correct column names
$sql = "
    SELECT `Reward_ID`,
           `Name`,
           `Description`,
           `Image_URL`,
           `Point_Cost`
    FROM `Rewards`
    ORDER BY `Point_Cost` ASC
";
$rewards = $conn->query($sql);
if ($rewards === false) {
    die("MySQL Error in Rewards query: " . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Rewards — Project Management</title>
  <link rel="stylesheet" href="assets/css/styles.css">

</head>
<body>

<div class="sidebar">
  <h2>Dashboard</h2>
  <ul>
    <?php if ($role === 'Admin' || $role === 'Manager'): ?>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="my_project.php">My Project</a></li>
      <li><a href="manage_tasks.php">Tasks</a></li>
      <li><a href="assign_tasks.php">Assign Task</a></li>
      <li><a href="team.php">Team</a></li>
      <li><a href="leaderboard.php">Leaderboard</a></li>
      <li><a href="admin_rewards.php" class="active">Manage Rewards</a></li>
    <?php else: ?>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="my_project.php">My Project</a></li>
      <li><a href="claim_task.php">Claim Task</a></li>
      <li><a href="rewards.php" class="active">🎁 Rewards</a></li>
      <li><a href="redemption_history.php">📜 My Redemptions</a></li>
    <?php endif; ?>
  </ul>
  <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="main-content">
  <header>
    <h1>Rewards</h1>
    <p class="current-points">You have <strong><?= htmlspecialchars($userPts) ?></strong> points</p>
  </header>

  <?php if ($redeem_error): ?>
    <div class="alert alert-error">
      <?= htmlspecialchars($redeem_error) ?>
    </div>
  <?php endif; ?>

  <?php if ($redeem_success): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($redeem_success) ?>
    </div>
  <?php endif; ?>


  <?php if ($rewards->num_rows > 0): ?>
    <div class="rewards-grid">
      <?php while ($r = $rewards->fetch_assoc()): ?>
        <div class="reward-card">
          <?php if (!empty($r['Image_URL'])): ?>
            <img src="<?= htmlspecialchars($r['Image_URL']) ?>"
                 alt="<?= htmlspecialchars($r['Name']) ?>"
                 class="reward-image" />
          <?php endif; ?>
          <h3><?= htmlspecialchars($r['Name']) ?></h3>
          <p><?= nl2br(htmlspecialchars($r['Description'] ?: '—')) ?></p>
          <div class="card-footer">
            <span class="cost-badge"><?= htmlspecialchars($r['Point_Cost']) ?> pts</span>
            <form method="POST" action="redeem_reward.php">
              <input type="hidden" name="reward_id" value="<?= $r['Reward_ID'] ?>">
              <button
                type="submit"
                class="redeem-btn"
                <?= $userPts < $r['Point_Cost'] ? 'disabled' : '' ?>
              >
                <?= $userPts < $r['Point_Cost'] ? 'Insufficient Points' : 'Redeem' ?>
              </button>
            </form>

          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="no-tasks">No rewards available at the moment.</div>
  <?php endif; ?>
</div>

</body>
</html>
