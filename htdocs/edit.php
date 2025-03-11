<?php
session_start();
include 'db.php';
include 'navigation.php';

if (isset($_GET['id'])) {
    $scheduleId = $_GET['id'];
} elseif (isset($_GET['ScheduleID'])) {
    $scheduleId = $_GET['ScheduleID'];
} else {
    die("No schedule ID provided.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date_time = $_POST['start_date_time'];
    $end_date_time = $_POST['end_date_time'];
    
    $sql = "UPDATE Schedule SET start_date_time = ?, end_date_time = ? WHERE ScheduleID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssi", $start_date_time, $end_date_time, $scheduleId);
    if ($stmt->execute()) {
        header("Location: schedule_detail.php?date=" . date("Y-m-d", strtotime($start_date_time)));
        exit();
    } else {
        $error = "Error updating schedule: " . $stmt->error;
    }
}

$sql = "SELECT * FROM Schedule WHERE ScheduleID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $scheduleId);
stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Schedule not found.");
}
$schedule = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Schedule</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- ...existing head content... -->
</head>
<body>
<div class="container mt-4">
    <h2>Edit Schedule</h2>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="start_date_time">Start Date/Time</label>
            <input type="datetime-local" name="start_date_time" id="start_date_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_date_time'])); ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date_time">End Date/Time</label>
            <input type="datetime-local" name="end_date_time" id="end_date_time" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_date_time'])); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Schedule</button>
        <a href="schedule.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<!-- ...existing JS code... -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
