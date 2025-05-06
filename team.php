<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

// Fetch all users
$sql    = "SELECT Name, Email, Role FROM Users ORDER BY Role ASC, Name ASC";
$result = $conn->query($sql);
if (!$result) {
    die("❌ SQL Error: " . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Team Overview — Project Management</title>
  <link rel="stylesheet" href="assets/css/styles.css">

</head>
<body>

  <div class="sidebar">
    <h2>Dashboard</h2>
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="my_project.php">My Project</a></li>
      <li><a href="claim_task.php">Claim Task</a></li>
      <li><a href="manage_tasks.php">Tasks</a></li>
      <li><a href="assign_tasks.php">Assign Task</a></li>
      <li><a href="team.php" class="active">Team</a></li>
      <li><a href="leaderboard.php">Leaderboard</a></li>
      <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
    </ul>
    <a href="logout.php" class="logout-button">Logout</a>
  </div>

  <div class="main-content">
    <header>
      <h1>👥 Team Members</h1>
    </header>

    <?php if ($result->num_rows > 0): ?>
      <div class="team-grid">
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="team-card">
            <h3><?= htmlspecialchars($row['Name']) ?></h3>
            <p><?= htmlspecialchars($row['Email']) ?></p>
            <span class="role-badge"><?= htmlspecialchars(strtolower($row['Role'])) ?></span>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="no-team">No team members found.</div>
    <?php endif; ?>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
  </div>

</body>
</html>
