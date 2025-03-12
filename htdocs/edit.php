<?php
session_start();
include 'db.php';
include 'navigation.php';

if (!isset($_GET['ScheduleID'])) {
    die("No schedule ID provided.");
}
$scheduleId = (int) $_GET['ScheduleID']; // cast to integer for safety

// On POST, process the update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date_time = $_POST['start_date_time'];
    $end_date_time = $_POST['end_date_time'];
    // New fields
    $client_name    = $_POST['client_name'];
    $contact_number = $_POST['contact_number'];
    $location       = $_POST['location'];
    $event_name     = $_POST['event_name'];
    
    $stmt = $conn->prepare("UPDATE Schedule SET start_date_time = ?, end_date_time = ?, client_name = ?, contact_number = ?, location = ?, event_name = ? WHERE ScheduleID = ?");
    if (!$stmt) { die("Prepare failed: " . $conn->error); }
    $stmt->bind_param("ssssssi", $start_date_time, $end_date_time, $client_name, $contact_number, $location, $event_name, $scheduleId);
    if ($stmt->execute()) {
        header("Location: schedule.php?success=updated");
        exit();
    } else {
        $error = "Error updating schedule: " . $stmt->error;
    }
}

// Fetch current schedule details
$stmt = $conn->prepare("SELECT start_date_time, end_date_time, client_name, contact_number, location, event_name FROM Schedule WHERE ScheduleID = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("i", $scheduleId);
$stmt->execute();
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
    <!-- ...existing head content... -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
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
            <input type="datetime-local" name="start_date_time" id="start_date_time" class="form-control"
                value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_date_time'])); ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date_time">End Date/Time</label>
            <input type="datetime-local" name="end_date_time" id="end_date_time" class="form-control"
                value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_date_time'])); ?>" required>
        </div>
        <!-- New fields -->
        <div class="form-group">
            <label for="client_name">Client Name</label>
            <input type="text" name="client_name" id="client_name" class="form-control"
                value="<?php echo htmlspecialchars($schedule['client_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" name="contact_number" id="contact_number" class="form-control"
                value="<?php echo htmlspecialchars($schedule['contact_number']); ?>" required>
        </div>
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" name="location" id="location" class="form-control"
                value="<?php echo htmlspecialchars($schedule['location']); ?>" required>
        </div>
        <div class="form-group">
            <label for="event_name">Event Name</label>
            <input type="text" name="event_name" id="event_name" class="form-control"
                value="<?php echo htmlspecialchars($schedule['event_name']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Schedule</button>
        <a href="schedule.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
