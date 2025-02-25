<?php
session_start();
if (!isset($_SESSION['email']) || ($_SESSION['account_level'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}
if (!isset($_GET['id'])) {
    echo "No schedule ID provided.";
    exit();
}
$scheduleID = intval($_GET['id']);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'db.php';
$conn->begin_transaction();
try {
    // Retrieve StatusID for "declined"; insert if missing
    $stmt = $conn->prepare("SELECT StatusID FROM Updated_Status WHERE updated_status = ? LIMIT 1");
    $declineStatus = 'declined';
    $stmt->bind_param("s", $declineStatus);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO Updated_Status (updated_status) VALUES (?)");
        $stmt->bind_param("s", $declineStatus);
        $stmt->execute();
        $statusID = $stmt->insert_id;
        $stmt->close();
    } else {
        $stmt->bind_result($statusID);
        $stmt->fetch();
        $stmt->close();
    }
    // Update pending schedule to "declined"
    $stmt = $conn->prepare("UPDATE Schedule SET StatusID = ? WHERE ScheduleID = ?");
    $stmt->bind_param("ii", $statusID, $scheduleID);
    $stmt->execute();
    $stmt->close();
    $conn->commit();
    echo "<script>
            alert('Schedule request declined. The user has been notified.');
            window.location.href='calendar.php';
          </script>";
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>
