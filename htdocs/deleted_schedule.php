<?php
// Check if client ID is provided
if (!isset($_GET['id'])) {
    header("Location: schedule.php");
    exit();
}

$scheduleID = intval($_GET['id']);

// Database connection details
$servername = "127.0.0.1";
$username = "mariadb";
$password = "mariadb";
$dbname = "mariadb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete client using a prepared statement
$stmt = $conn->prepare("DELETE FROM Schedule WHERE ScheduleID = ?");
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$stmt->close();
$conn->close();

// Redirect back to client list
header("Location: schedule.php");
exit();
?>