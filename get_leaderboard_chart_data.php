<?php
include __DIR__ . '/includes/db_connection.php';

$filter = $_GET['filter'] ?? 'week';

// Determine date range
switch ($filter) {
    case 'month':
        $startDate = date('Y-m-01');
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        break;
    case 'all':
    default:
        $startDate = '1970-01-01'; // All time
        break;
}

// Fetch leaderboard data
$sql = "
    SELECT 
        U.Name, 
        DATE(TS.Submission_Date) as date, 
        SUM(
            CASE 
                WHEN T.Priority = 'High' THEN 15
                WHEN T.Priority = 'Medium' THEN 10
                WHEN T.Priority = 'Low' THEN 5
                ELSE 5
            END
        ) AS total_points
    FROM TaskSubmissions TS
    JOIN Tasks T ON TS.Task_ID = T.Task_ID
    JOIN Users U ON TS.Submitted_By = U.User_ID
    WHERE TS.Status = 'Approved' AND TS.Submission_Date >= ?
    GROUP BY U.Name, DATE(TS.Submission_Date)
    ORDER BY date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $startDate);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
