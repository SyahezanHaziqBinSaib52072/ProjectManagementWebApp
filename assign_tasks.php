<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin','Manager'])) {
    die("Access Denied.");
}
include __DIR__ . '/includes/db_connection.php';

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name    = trim($_POST['task_name']);
    $description  = trim($_POST['description']);
    $priority     = $_POST['priority'];
    $assigned_to  = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;

    // Points based on priority
    $points = match ($priority) {
        'High'   => 30,
        'Medium' => 20,
        'Low'    => 10,
        default  => 10,
    };

    // Insert task
    $stmt = $conn->prepare("
      INSERT INTO tasks 
        (Task_Name, Description, Assigned_To, Points, Priority, Status) 
      VALUES 
        (?, ?, ?, ?, ?, 'Pending')
    ");
    if (!$stmt) {
        $error = "SQL prepare error: " . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param("ssiss", $task_name, $description, $assigned_to, $points, $priority);
        if ($stmt->execute()) {
            $success = "Task \"{$task_name}\" created successfully.";
        } else {
            $error = "Execution error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// Fetch members for the dropdown
$members = $conn->query("SELECT User_ID, Name FROM users WHERE Role = 'Member' ORDER BY Name");
$member_list = [];
while ($m = $members->fetch_assoc()) {
    $member_list[$m['User_ID']] = $m['Name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign a New Task</title>
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
      <li><a href="assign_tasks.php" class="active">Assign Task</a></li>
      <li><a href="team.php">Team</a></li>
      <li><a href="leaderboard.php">Leaderboard</a></li>
      <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
    </ul>
    <a href="logout.php" class="logout-button">Logout</a>
  </div>

  <div class="main-content">
    <header>
      <h1>Assign a New Task</h1>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
    </header>

    <div class="card form-card">
      <form method="POST" action="assign_tasks.php" class="task-form">
        <div class="form-group">
          <label for="task_name">Task Name</label>
          <input type="text" id="task_name" name="task_name" required>
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="4" required></textarea>
        </div>

        <div class="form-group">
          <label for="assigned_to">Assign To</label>
          <select id="assigned_to" name="assigned_to">
            <option value="">— Leave Unassigned —</option>
            <?php foreach ($member_list as $id => $name): ?>
              <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="priority">Priority</label>
          <select id="priority" name="priority" required>
            <option value="High">High (30 pts)</option>
            <option value="Medium" selected>Medium (20 pts)</option>
            <option value="Low">Low (10 pts)</option>
          </select>
        </div>

        <button type="submit" class="btn btn-assign">Create Task</button>
      </form>
    </div>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
  </div>

</body>
</html>
