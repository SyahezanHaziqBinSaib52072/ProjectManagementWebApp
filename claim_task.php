<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Handle form POST to claim a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $stmt = $conn->prepare("
      UPDATE Tasks
      SET Assigned_To = ?, Status = 'In Progress'
      WHERE Task_ID = ?
    ");
    $stmt->bind_param("ii", $user_id, $_POST['task_id']);
    $stmt->execute();
    header("Location: claim_task.php");
    exit();
}

// Fetch unassigned tasks
$query  = "SELECT Task_ID, Task_Name, Description FROM Tasks WHERE Assigned_To IS NULL";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Claim Task — Project Management</title>
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
      <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
    <?php else: ?>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="my_project.php">My Project</a></li>
      <li><a href="claim_task.php" class="active">Claim Task</a></li>
      <li><a href="rewards.php">🎁 Rewards</a></li>
      <li><a href="redemption_history.php">📜 My Redemptions</a></li>
    <?php endif; ?>
  </ul>
  <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="main-content">
  <header>
    <h1>Claim a Task</h1>
  </header>

  <?php if ($result->num_rows > 0): ?>
    <div class="claim-grid">
      <?php while ($task = $result->fetch_assoc()): ?>
        <div class="claim-card">
          <h3><?= htmlspecialchars($task['Task_Name']) ?></h3>
          <p><?= nl2br(htmlspecialchars($task['Description'] ?: '—')) ?></p>
          <form method="POST" action="claim_task.php">
            <input type="hidden" name="task_id" value="<?= $task['Task_ID'] ?>">
            <button type="submit" class="claim-btn">Claim Task</button>
          </form>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="no-tasks">There are no unassigned tasks to claim right now.</div>
  <?php endif; ?>
</div>

</body>
</html>
