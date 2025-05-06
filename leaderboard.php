<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

// Fetch ranking data
$sql = "
    SELECT U.Name, U.Role, G.Points_Earned, G.Badges_Achieved, G.Leaderboard_Rank
    FROM Gamification G
    JOIN Users U ON G.User_ID = U.User_ID
    ORDER BY G.Leaderboard_Rank ASC
";
$result = $conn->query($sql);
if (!$result) {
    die("SQL Error: " . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leaderboard — Project Management</title>
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
      <li><a href="team.php">Team</a></li>
      <li><a href="leaderboard.php" class="active">Leaderboard</a></li>
      <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
    </ul>
    <a href="logout.php" class="logout-button">Logout</a>
  </div>

  <div class="main-content">
    <header>
      <h1>🏆 Leaderboard</h1>
    </header>

    <div class="card card-leaderboard">
      <table class="leaderboard-table">
        <thead>
          <tr>
            <th style="width:80px">Rank</th>
            <th>Name</th>
            <th>Role</th>
            <th style="width:100px">Points</th>
            <th style="width:120px">Badges</th>
            <th style="width:200px">Progress</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()):
              $points = (int)$row['Points_Earned'];
              $rank   = (int)$row['Leaderboard_Rank'];
              $level      = floor($points / 50) + 1;
              $progress   = $points - (($level - 1) * 50);
              $percent    = round(($progress / 50) * 100);
              $medal      = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '🔸'));
          ?>
          <tr>
            <td class="rank-cell"><?= $medal ?><br><span class="rank-number"><?= $rank ?></span></td>
            <td><?= htmlspecialchars($row['Name']) ?></td>
            <td><?= htmlspecialchars($row['Role']) ?></td>
            <td><?= $points ?></td>
            <td><?= htmlspecialchars($row['Badges_Achieved'] ?: '—') ?></td>
            <td>
              <div class="progress-bar">
                <div class="progress-bar-fill" style="width: <?= $percent ?>%;"></div>
              </div>
              <span class="progress-text"><?= $percent ?>%</span>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <script>
window.addEventListener('load', () => {
  document.querySelectorAll('.progress-bar-fill').forEach(bar => {
    const target = bar.getAttribute('style').match(/width:\s*([\d.]+)%/)[1] + '%';
    bar.style.width = '0';
    setTimeout(() => bar.style.width = target, 100);
  });
});
</script>

      <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
  </div>

</body>
</html>
