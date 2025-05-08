<?php
session_start();
include __DIR__ . '/includes/db_connection.php';

// Only handle POST submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Grab and sanitize inputs
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// Fetch user + role from database
$stmt = $conn->prepare("
    SELECT User_ID, Password, Role
    FROM Users
    WHERE Email = ?
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Plaintext check—switch to password_verify() if hashed in future
    if ($password === $user['Password']) {
        // ✅ AUTH SUCCESS: set sessions and redirect
        $_SESSION['user_id'] = $user['User_ID'];
        $_SESSION['role']    = $user['Role'];
        header('Location: dashboard.php');
        exit;
    }
}

// ❌ AUTH FAILED: redirect back to login
header('Location: index.html?login=failed');
exit;
