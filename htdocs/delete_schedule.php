<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'db.php';

if (!isset($_GET['ScheduleID'])) {
    header("Location: calendar.php");
    exit();
}
$scheduleID = $_GET['ScheduleID'];

$stmt = $conn->prepare("DELETE FROM Schedule WHERE ScheduleID = ?");
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$stmt->close();

header("Location: calendar.php");
exit();
?>
