<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Access denied.");
}
include __DIR__ . '/includes/db_connection.php';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM Rewards WHERE Reward_ID = $id");
    header("Location: admin_rewards.php");
    exit();
}

// Handle creation
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = trim($_POST['name']);
    $desc        = trim($_POST['description']);
    $cost        = intval($_POST['point_cost']);
    $image       = trim($_POST['image_url']);

    $stmt = $conn->prepare("
      INSERT INTO Rewards (Name, Description, Point_Cost, Image_URL)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssis", $name, $desc, $cost, $image);
    $stmt->execute();
    $stmt->close();

    $success = "Reward “{$name}” added.";
}

// Fetch all rewards
$rewards = $conn->query("SELECT Reward_ID, Name, Description, Point_Cost, Image_URL FROM Rewards ORDER BY Point_Cost ASC");
if (!$rewards) {
    die("SQL Error: " . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Manage Rewards</title>
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
      <li><a href="leaderboard.php">Leaderboard</a></li>
      <li><a href="admin_rewards.php" class="active">⚙️ Manage Rewards</a></li>
    </ul>
    <a href="logout.php" class="logout-button">Logout</a>
  </div>

  <div class="main-content">
    <header>
      <h1>🎯 Reward Management</h1>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
    </header>

    <!-- Creation Form -->
    <div class="card form-card">
      <form method="POST" action="admin_rewards.php" class="task-form">
        <div class="form-group">
          <label for="name">Reward Name</label>
          <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-group">
          <label for="point_cost">Points Required</label>
          <input type="number" id="point_cost" name="point_cost" min="1" required>
        </div>
        <div class="form-group">
          <label for="image_url">Image URL (optional)</label>
          <input type="text" id="image_url" name="image_url">
        </div>
        <button type="submit" class="btn btn-assign">Add Reward</button>
      </form>
    </div>

    <!-- Existing Rewards Table -->
    <div class="card card-rewards">
      <table class="tasks-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Cost</th>
            <th>Image</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = $rewards->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['Name']) ?></td>
            <td><?= nl2br(htmlspecialchars($r['Description'])) ?></td>
            <td><?= intval($r['Point_Cost']) ?> pts</td>
            <td>
              <?php if ($r['Image_URL']): ?>
                <img src="<?= htmlspecialchars($r['Image_URL']) ?>" alt="" class="reward-thumb">
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td>
              <a href="admin_rewards.php?delete=<?= $r['Reward_ID'] ?>"
                 class="btn btn-danger"
                 onclick="return confirm('Delete this reward?');">
                🗑 Delete
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
  </div>

</body>
</html>
