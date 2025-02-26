<?php
session_start();
include 'db.php';
include 'navigation.php';

// Validate schedule ID
if (!isset($_GET['id'])) {
    die("Invalid schedule ID.");
}
$scheduleID = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input values, e.g. start and end dates and status
    $start_date_time = $_POST['start_date_time']; // add proper validation as needed
    $end_date_time = $_POST['end_date_time'];     // add proper validation as needed
    $statusID = $_POST['statusID'];               // add proper validation as needed
    // ...other input fields as needed...

    // Update query
    $sql = "UPDATE Schedule SET start_date_time = '$start_date_time', end_date_time = '$end_date_time', StatusID = '$statusID' WHERE ScheduleID = '$scheduleID'";
    if ($conn->query($sql) === TRUE) {
        header("Location: schedule_detail.php?date=" . date("Y-m-d", strtotime($start_date_time)));
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Retrieve existing schedule values with join on Updated_Status
$sql = "SELECT s.*, us.updated_status FROM Schedule s JOIN Updated_Status us ON s.StatusID = us.StatusID WHERE s.ScheduleID = '$scheduleID'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $schedule = $result->fetch_assoc();
} else {
    die("Schedule not found.");
}
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
    <form method="post">
        <div class="form-group">
            <label for="start_date_time">Start Date/Time</label>
            <input type="datetime-local" name="start_date_time" class="form-control" 
                   value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_date_time'])); ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date_time">End Date/Time</label>
            <input type="datetime-local" name="end_date_time" class="form-control" 
                   value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_date_time'])); ?>" required>
        </div>
        <div class="form-group">
            <label for="statusID">Status</label>
            <select name="statusID" class="form-control" required>
                <option value="1" <?php if($schedule['StatusID'] == 1) echo "selected"; ?>>Confirmed</option>
                <option value="2" <?php if($schedule['StatusID'] == 2) echo "selected"; ?>>Cancelled</option>
                <!-- add other status options as required -->
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="schedule_detail.php?date=<?php echo date('Y-m-d', strtotime($schedule['start_date_time'])); ?>" class="btn btn-secondary">Back</a>
    </form>
</div>
<!-- ...existing JS code... -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
