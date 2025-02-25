<?php
session_start();
include 'db.php';
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
if (!isset($_GET['id'])) {
    echo "Schedule ID not provided.";
    exit();
}
$scheduleID = intval($_GET['id']);
include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$stmt = $conn->prepare("DELETE FROM Schedule WHERE ScheduleID = ?");
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$stmt->close();

$_SESSION['notification'] = "Schedule deleted successfully.";
$date = isset($_GET['date']) ? $_GET['date'] : '';
if ($date) {
    header("Location: insert_schedule.php?viewSchedules=1&date=" . urlencode($date));
} else {
    header("Location: calendar.php");
}
exit();
?>
