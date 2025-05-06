<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Project</title>
    <link rel="stylesheet" href="assets/css/styles.css">

</head>
<body>

<div class="sidebar">
    <h2>Dashboard</h2>
    <ul>
        <?php if ($role === 'Admin' || $role === 'Manager'): ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="my_project.php" class="active">My Project</a></li>
            <li><a href="manage_tasks.php">Tasks</a></li>
            <li><a href="assign_tasks.php">Assign Task</a></li>
            <li><a href="team.php">Team</a></li>
            <li><a href="leaderboard.php">Leaderboard</a></li>
            <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
        <?php else: ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="my_project.php" class="active">My Project</a></li>
            <li><a href="claim_task.php">Claim Task</a></li>
            <li><a href="rewards.php">🎁 Rewards</a></li>
            <li><a href="redemption_history.php">📜 My Redemptions</a></li>
        <?php endif; ?>
    </ul>
    <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="main-content">
    <header>
        <h1>My Project – Welcome, <?= ucfirst($role) ?></h1>
    </header>

    <?php
    // Fetch tasks but override Status to 'Completed' if there's an approved submission
    $query = "
      SELECT 
        T.Task_ID,
        T.Task_Name,
        T.Description,
        CASE
          WHEN EXISTS (
            SELECT 1 
            FROM TaskSubmissions TS
            WHERE TS.Task_ID     = T.Task_ID
              AND TS.Submitted_By= ?
              AND TS.Status      = 'Approved'
          ) THEN 'Completed'
          ELSE T.Status
        END AS DisplayStatus
      FROM Tasks T
      WHERE T.Assigned_To = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $tasksResult = $stmt->get_result();
    ?>

    <section>
      <h2>Assigned Tasks</h2>

      <div class="projects-grid">
        <?php if ($tasksResult->num_rows > 0): ?>
          <?php while ($task = $tasksResult->fetch_assoc()): ?>
            <?php
              $status       = $task['DisplayStatus'];
              $badge_class  = 'badge-' . strtolower(str_replace(' ', '-', $status));
            ?>
            <div class="project-card">
              <h3><?= htmlspecialchars($task['Task_Name']) ?></h3>
              <p><?= nl2br(htmlspecialchars($task['Description'] ?: '—')) ?></p>
              <p>
                Status:
                <span class="badge <?= $badge_class ?>">
                  <?= htmlspecialchars($status) ?>
                </span>
              </p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-tasks">No assigned tasks found.</div>
        <?php endif; ?>
      </div>
    </section>
</div>

</body>
</html>
