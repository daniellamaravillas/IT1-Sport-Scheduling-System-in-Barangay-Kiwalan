<?php
session_start();
include 'db.php';

if (($_SESSION['account_level'] ?? 'user') !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['schedule_id'])) {
    header("Location: pending.php");
    exit();
}

$scheduleID = $_POST['schedule_id'];

$conn->begin_transaction();
try {
    // Get StatusID for "declined", insert it if not exists.
    $stmt = $conn->prepare("SELECT StatusID FROM Updated_Status WHERE updated_status = 'declined' LIMIT 1");
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO Updated_Status (updated_status) VALUES ('declined')");
        $stmt->execute();
        $declinedStatusID = $stmt->insert_id;
        $stmt->close();
    } else {
        $stmt->bind_result($declinedStatusID);
        $stmt->fetch();
        $stmt->close();
    }
    // Update Schedule with declined status.
    $stmt = $conn->prepare("UPDATE Schedule SET StatusID = ? WHERE ScheduleID = ?");
    $stmt->bind_param("ii", $declinedStatusID, $scheduleID);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    $_SESSION['alert_message'] = "Schedule request #$scheduleID has been declined. User will see that the schedule is unavailable.";
    header("Location: pending.php");
    exit();
} catch(Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>
