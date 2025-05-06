<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';

// --- Handle Reassignment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_task_id'])) {
    $task_id     = intval($_POST['reassign_task_id']);
    $new_user_id = $_POST['new_assignee'] !== '' ? intval($_POST['new_assignee']) : null;

    if ($new_user_id) {
        // Assign and auto-set status to In Progress
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET Assigned_To = ?, Status = 'In Progress' 
            WHERE Task_ID = ?
        ");
        $stmt->bind_param("ii", $new_user_id, $task_id);
    } else {
        // Unassign and revert to Pending
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET Assigned_To = NULL, Status = 'Pending' 
            WHERE Task_ID = ?
        ");
        $stmt->bind_param("i", $task_id);
    }
    $stmt->execute();
    $reassign_success = true;
}

// Fetch all tasks with computed DisplayStatus
$tasks_sql = "
    SELECT
      T.Task_ID,
      T.Task_Name,
      T.Description,
      T.Points,
      T.Assigned_To,
      U.Name AS Assignee,
      CASE
        WHEN EXISTS (
          SELECT 1 FROM TaskSubmissions TS
          WHERE TS.Task_ID = T.Task_ID
            AND TS.Status = 'Approved'
        ) THEN 'Completed'
        WHEN T.Assigned_To IS NOT NULL THEN 'In Progress'
        ELSE 'Pending'
      END AS DisplayStatus
    FROM tasks T
    LEFT JOIN users U ON T.Assigned_To = U.User_ID
    ORDER BY
      FIELD(
        CASE
          WHEN EXISTS (
            SELECT 1 FROM TaskSubmissions TS
            WHERE TS.Task_ID = T.Task_ID
              AND TS.Status = 'Approved'
          ) THEN 'Completed'
          WHEN T.Assigned_To IS NOT NULL THEN 'In Progress'
          ELSE 'Pending'
        END,
        'Pending','In Progress','Completed'
      ),
      T.Task_ID DESC
";
$task_result = $conn->query($tasks_sql);
if (!$task_result) {
    die("MySQL Error: " . $conn->error);
}

// Fetch member list
$members = $conn->query("SELECT User_ID, Name FROM users WHERE Role = 'Member'");
$member_list = [];
while ($row = $members->fetch_assoc()) {
    $member_list[$row['User_ID']] = $row['Name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Tasks — Project Management</title>
  <link rel="stylesheet" href="assets/css/styles.css">

</head>
<body>

<div class="sidebar">
  <h2>Dashboard</h2>
  <ul>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="my_project.php">My Project</a></li>
    <li><a href="claim_task.php">Claim Task</a></li>
    <li><a href="manage_tasks.php" class="active">Tasks</a></li>
    <li><a href="assign_tasks.php">Assign Task</a></li>
    <li><a href="team.php">Team</a></li>
    <li><a href="leaderboard.php">Leaderboard</a></li>
    <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
  </ul>
  <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="main-content">
  <header>
    <h1>Task Management</h1>
    <?php if (!empty($reassign_success)): ?>
      <div class="alert alert-success">
        ✅ Task reassigned successfully and status updated.
      </div>
    <?php endif; ?>
  </header>

  <table class="tasks-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Task Name</th>
        <th>Description</th>
        <th>Assigned To</th>
        <th>Points</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($task = $task_result->fetch_assoc()): 
          $status     = $task['DisplayStatus'];
          $badge_class= 'badge-' . strtolower(str_replace(' ', '-', $status));
      ?>
      <tr>
        <td><?= $task['Task_ID'] ?></td>
        <td><?= htmlspecialchars($task['Task_Name']) ?></td>
        <td><?= nl2br(htmlspecialchars($task['Description'] ?: '—')) ?></td>
        <td><?= htmlspecialchars($task['Assignee'] ?: 'Unassigned') ?></td>
        <td><?= $task['Points'] ?></td>
        <td>
          <span class="badge <?= $badge_class ?>">
            <?= htmlspecialchars($status) ?>
          </span>
        </td>
        <td>
          <!-- Delete -->
          <form method="POST" action="delete_task.php" class="inline-form" onsubmit="return confirm('Delete this task?');">
            <input type="hidden" name="task_id" value="<?= $task['Task_ID'] ?>">
            <button type="submit" class="btn btn-danger">Delete</button>
          </form>

          <!-- Reassign -->
          <form method="POST" action="" class="inline-form">
            <input type="hidden" name="reassign_task_id" value="<?= $task['Task_ID'] ?>">
            <select name="new_assignee">
              <option value="">Unassign</option>
              <?php foreach ($member_list as $id => $name): ?>
                <option value="<?= $id ?>"
                  <?= ($task['Assigned_To'] == $id ? 'selected' : '') ?>>
                  <?= htmlspecialchars($name) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Reassign</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

</body>
</html>
