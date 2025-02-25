<?php
session_start();
include 'db.php';

if (!isset($_SESSION['account_level']) || $_SESSION['account_level'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
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
        // Update Schedule with declined status only.
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
} elseif (isset($_GET['id'])) {
    $scheduleID = $_GET['id'];
} else {
    header("Location: pending.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decline Request</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h3>Decline Schedule Request #<?php echo htmlspecialchars($scheduleID); ?></h3>
    <form method="post" action="">
        <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($scheduleID); ?>">
        <button type="submit" class="btn btn-danger">Decline Request</button>
    </form>
</div>
</body>
</html>
