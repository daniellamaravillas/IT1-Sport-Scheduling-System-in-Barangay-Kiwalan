<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Move this to the top before using session variables

include 'db.php';
include 'navigation.php';
// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];

// Prepare the query safely
$query = "SELECT nickname FROM users WHERE email = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Database error: " . $conn->error);
}

// Set nickname safely
$nickname = $user ? htmlspecialchars($user['nickname'], ENT_QUOTES, 'UTF-8') : "Guest";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Home</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="home.css"stylesheet">
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><?php echo "Welcome, " . $nickname . "!"; ?></h2>
        </div>
        <div class="card-body">
            <h3>Barangay Kiwalan Sport Scheduling</h3>
            <p>Here you can find the latest schedules for sports events.</p>
            <p>Explore the different events available in our community.</p>
       
        </div>
        <button class="btn btn-primary w-100" onclick="window.location.href='calendar.php'">View Calendar</button>
    </div>
</div>
</body>
</html>