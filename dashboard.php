<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include __DIR__ . '/includes/db_connection.php';
include __DIR__ . '/includes/level_functions.php';



$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// — Fetch User’s Name —
$userStmt = $conn->prepare("SELECT Name FROM Users WHERE User_ID = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userName = $user['Name'] ?? 'User';

// 1) Gamification data
$gquery = "
    SELECT Points_Earned, Badges_Achieved, Leaderboard_Rank, Level
    FROM Gamification
    WHERE User_ID = ?
";
$stmt = $conn->prepare($gquery);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Fetch result
$result = $stmt->get_result();
$gdata  = $result->fetch_assoc();

// If no row yet, insert default and re-fetch
if ( ! $gdata ) {
    $init = $conn->prepare("
        INSERT INTO Gamification (User_ID)
        VALUES (?)
    ");
    $init->bind_param("i", $user_id);
    $init->execute();
    
    // now re-run the select
    $stmt->execute();
    $result = $stmt->get_result();
    $gdata  = $result->fetch_assoc();
}

// Compute progress
$level      = $gdata['Level'] ?? 1;
$currentMin = levelToMinPoints($level);
$nextMin    = levelToMinPoints($level + 1);
$progress   = ($nextMin > $currentMin)
            ? (($gdata['Points_Earned'] - $currentMin) / ($nextMin - $currentMin)) * 100
            : 100;

// 2) Leaderboard labels
$labelStmt = $conn->prepare("
    SELECT DISTINCT U.Name
    FROM Users U
    JOIN Gamification G ON U.User_ID = G.User_ID
    ORDER BY U.Name
");
$labelStmt->execute();
$labels = $labelStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3) Pending approvals (admin/manager)
if ($role === 'Admin' || $role === 'Manager') {
    $aprSql = "
        SELECT TS.Submission_ID, T.Task_Name, TS.Description, TS.FilePath,
               U.Name AS Submitter, TS.Status, TS.Submission_Date
        FROM TaskSubmissions TS
        JOIN Tasks T ON TS.Task_ID = T.Task_ID
        JOIN Users U ON TS.Submitted_By = U.User_ID
        WHERE TS.Status = 'Pending'
        ORDER BY TS.Submission_Date DESC
    ";
    $aprResult = $conn->query($aprSql);
}

// 4) Member tasks & submissions
if ($role === 'Member') {
    // Available tasks
    $taskQuery = "
        SELECT Task_ID, Task_Name
        FROM Tasks
        WHERE Assigned_To = ?
          AND Task_ID NOT IN (
            SELECT Task_ID FROM TaskSubmissions
            WHERE Submitted_By = ? AND Status IN ('Pending','Approved')
          )
        ORDER BY Task_ID DESC
    ";
    $tstmt = $conn->prepare($taskQuery);
    $tstmt->bind_param("ii", $user_id, $user_id);
    $tstmt->execute();
    $taskResult = $tstmt->get_result();

    // Past submissions
    $subSql = "
        SELECT S.Submission_ID, T.Task_Name, S.Description, S.FilePath,
               S.Status, S.Submission_Date
        FROM TaskSubmissions S
        JOIN Tasks T ON S.Task_ID = T.Task_ID
        WHERE S.Submitted_By = ?
        ORDER BY S.Submission_Date DESC
    ";
    $sstmt = $conn->prepare($subSql);
    $sstmt->bind_param("i", $user_id);
    $sstmt->execute();
    $subResult = $sstmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="assets/css/styles.css">

  
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>Dashboard</h2>
  <ul>
    <?php if ($role === 'Admin' || $role === 'Manager'): ?>
      <li><a href="my_project.php">My Project</a></li>
      <li><a href="manage_tasks.php">Tasks</a></li>
      <li><a href="assign_tasks.php">Assign Task</a></li>
      <li><a href="team.php">Team</a></li>
      <li><a href="leaderboard.php">Leaderboard</a></li>
      <li><a href="admin_rewards.php">⚙️ Manage Rewards</a></li>
    <?php endif; ?>
    <?php if ($role === 'Member'): ?>
      <li><a href="my_project.php">My Project</a></li>
      <li><a href="claim_task.php">Claim Task</a></li>
      <li><a href="rewards.php">🎁 Rewards</a></li>
      <li><a href="redemption_history.php">📜 My Redemptions</a></li>
    <?php endif; ?>
  </ul>
  <a href="logout.php" class="logout-button">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
   <!-- ── New User Info Bar ── -->
   <div class="user-info">
    <p class="greeting">Welcome, <strong><?= htmlspecialchars($userName) ?></strong></p>
    <p class="role-display"><?= htmlspecialchars(ucfirst($role)) ?></p>
  </div>
  <div class="dashboard-grid">

    <!-- 1) Performance Card -->
    <div class="card card-stats">
      <div class="perf-header">
        <h2>Your Performance</h2>
        <i class="fas fa-trophy performance-icon"></i>
      </div>
      <p class="level">Level <strong><?= $level ?></strong></p>
      <div class="progress-bar large">
        <div class="progress-fill" style="width:<?= round($progress) ?>%;"></div>
      </div>
      <div class="perf-stats">
        <div class="stat">
          <span class="stat-label">Points</span>
          <span class="stat-value"><?= $gdata['Points_Earned'] ?></span>
        </div>
        <div class="stat">
          <span class="stat-label">Badge</span>
          <span class="stat-value"><?= $gdata['Badges_Achieved'] ?: '—' ?></span>
        </div>
        <div class="stat">
          <span class="stat-label">Rank</span>
          <span class="stat-value"><?= $gdata['Leaderboard_Rank'] ?></span>
        </div>
      </div>
    </div>

    <!-- 2) Leaderboard Card -->
    <div class="card card-leaderboard">
      <h2>🏆 Leaderboard</h2>
      <select id="leaderboard-filter">
        <option value="week">This Week</option>
        <option value="month">This Month</option>
        <option value="all" selected>All Time</option>
      </select>
      <canvas id="leaderboardChart"></canvas>
    </div>

    <!-- 3) Approvals or Submission Card -->
    <div class="card card-approvals">
      <?php if ($role === 'Admin' || $role === 'Manager'): ?>
        <h2>Pending Task Approvals</h2>
        <table class="tasks-table">
          <thead>
            <tr>
              <th>Task</th><th>Description</th><th>File</th>
              <th>By</th><th>Status</th><th>On</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $aprResult->fetch_assoc()):
                $fileLink = $row['FilePath']
                  ? "<a href='{$row['FilePath']}' target='_blank'>Download</a>"
                  : "—";
            ?>
              <tr>
                <td><?= htmlspecialchars($row['Task_Name']) ?></td>
                <td><?= htmlspecialchars($row['Description']) ?></td>
                <td><?= $fileLink ?></td>
                <td><?= htmlspecialchars($row['Submitter']) ?></td>
                <td><?= htmlspecialchars($row['Status']) ?></td>
                <td><?= htmlspecialchars($row['Submission_Date']) ?></td>
                <td>
                  <button onclick="approveTask(<?= $row['Submission_ID'] ?>)">✔️</button>
                  <button onclick="rejectTask(<?= $row['Submission_ID'] ?>)">❌</button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: /* Member */ ?>
        <h2>Submit a Task for Approval</h2>
        <form id="taskSubmissionForm" class="submission-form" enctype="multipart/form-data" method="POST">
          <select name="task_id" required>
            <?php if ($taskResult->num_rows > 0): ?>
              <?php while ($task = $taskResult->fetch_assoc()): ?>
                <option value="<?= $task['Task_ID'] ?>"><?= htmlspecialchars($task['Task_Name']) ?></option>
              <?php endwhile; ?>
            <?php else: ?>
              <option disabled>No tasks to submit</option>
            <?php endif; ?>
          </select><br><br>
          <textarea name="description" rows="3" required></textarea><br><br>
          <input type="file" name="file"><br><br>
          <button type="submit">Submit Task</button>
        </form>

        <?php if ($subResult->num_rows > 0): ?>
          <h3>Your Submitted Tasks</h3>
          <table class="submission-history">
            <thead>
              <tr><th>Task</th><th>Status</th><th>On</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php while ($s = $subResult->fetch_assoc()):
                  $deleteBtn = $s['Status'] === 'Pending'
                    ? "<button onclick=\"deleteSubmission({$s['Submission_ID']})\">Delete</button>"
                    : '—';
              ?>
                <tr>
                  <td><?= htmlspecialchars($s['Task_Name']) ?></td>
                  <td><?= htmlspecialchars($s['Status']) ?></td>
                  <td><?= htmlspecialchars($s['Submission_Date']) ?></td>
                  <td><?= $deleteBtn ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div><!-- /.dashboard-grid -->
</div><!-- /.main-content -->

<!-- Popup Modal -->
<div id="popup-modal" class="popup-modal">
  <div class="popup-content">
    <p id="popup-message"></p>
    <button type="button" id="popup-close">Okay</button>
  </div>
</div>

<script>
  const modal = document.getElementById("popup-modal");
  const msgEl = document.getElementById("popup-message");
  const closeBtn = document.getElementById("popup-close");

  function showPopup(message, success = true) {
    msgEl.textContent = message;
    msgEl.style.color = success ? "#28a745" : "#dc3545";
    modal.classList.add("show");
  }

  function closePopup() {
    modal.classList.remove("show");
    window.location.reload();
  }
  closeBtn.addEventListener("click", closePopup);

  document.getElementById("taskSubmissionForm")
    ?.addEventListener("submit", function(e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch("submit_task.php", { method: "POST", body: fd })
        .then(r => r.json())
        .then(d => showPopup(d.message, d.status === "success"))
        .catch(() => showPopup("Error submitting task", false));
    });

// Approve/Reject/Delete
function approveTask(id){ fetch("update_task_status.php",{
  method:"POST",
  headers:{"Content-Type":"application/x-www-form-urlencoded"},
  body:`submission_id=${id}&status=Approved`
}).then(()=>location.reload()); }
function rejectTask(id){ fetch("update_task_status.php",{
  method:"POST",
  headers:{"Content-Type":"application/x-www-form-urlencoded"},
  body:`submission_id=${id}&status=Rejected`
}).then(()=>location.reload()); }
function deleteSubmission(id){
  if(confirm("Are you sure?"))
    fetch("delete_submission.php",{
      method:"POST",
      headers:{"Content-Type":"application/x-www-form-urlencoded"},
      body:`submission_id=${id}`
    }).then(()=>location.reload());
}

// Leaderboard Chart
let chartInstance;
const COLORS = ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff','#ff9f40'];
function loadLeaderboardChart(filter="week"){
  fetch(`get_leaderboard_chart_data.php?filter=${filter}`)
    .then(r=>r.json())
    .then(data=>{
      const grouped={},labels=new Set();
      data.forEach(r=>{
        grouped[r.Name]??={};
        grouped[r.Name][r.date]=+r.total_points;
        labels.add(r.date);
      });
      const lbls=Array.from(labels).sort(),
            ds=Object.entries(grouped).map(([name,rec],i)=>({
        label:name,
        data:lbls.map(d=>rec[d]||0),
        fill:false,
        tension:0.3,
        borderColor:COLORS[i%COLORS.length]
      }));
      if(chartInstance)chartInstance.destroy();
      chartInstance=new Chart(
        document.getElementById("leaderboardChart"),{
          type:"line",
          data:{labels:lbls,datasets:ds},
          options:{
            responsive:true,
            interaction:{mode:"index",intersect:false},
            plugins:{legend:{display:true,position:"bottom"}},
            scales:{y:{beginAtZero:true}}
          }
        }
      );
    });
}
document.addEventListener("DOMContentLoaded",()=>{
  loadLeaderboardChart("week");
  document.getElementById("leaderboard-filter")
    .addEventListener("change",e=>loadLeaderboardChart(e.target.value));
});
</script>

</body>
</html>
