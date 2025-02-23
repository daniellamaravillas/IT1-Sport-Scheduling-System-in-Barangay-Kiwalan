<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'navigation.php';

$schedule_id = $_GET['id'] ?? null;
if (!$schedule_id) {
    die("No schedule ID provided.");
}
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? '';
    $start_date_time = $_POST['start_date_time'] ?? '';
    $end_date_time = $_POST['end_date_time'] ?? '';
    if ($event_id && $start_date_time && $end_date_time) {
        $stmt = $conn->prepare("UPDATE Schedule SET EventID = ?, start_date_time = ?, end_date_time = ? WHERE ScheduleID = ?");
        if ($stmt) {
            $stmt->bind_param("issi", $event_id, $start_date_time, $end_date_time, $schedule_id);
            if ($stmt->execute()) {
                $message = "Schedule updated successfully.";
            } else {
                $message = "Error updating schedule: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        $message = "Please fill in all fields.";
    }
}

// Retrieve current schedule details
$stmt = $conn->prepare("SELECT EventID, start_date_time, end_date_time FROM Schedule WHERE ScheduleID = ?");
if ($stmt) {
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $stmt->close();
    if (!$schedule) {
        die("Schedule not found.");
    }
} else {
    die("Error: " . $conn->error);
}

// Retrieve events for dropdown with client name
$sql = "SELECT Events.EventID, Events.Events_name, Clients.clients_name AS ClientName 
        FROM Events 
        INNER JOIN Clients ON Events.ClientID = Clients.ClientID";
$events_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Schedule</title>
    <link rel="stylesheet" href="homes.css">
    <style>
    /* Minimal styling similar to create_schedule */
    .container { max-width: 500px; margin: 50px auto; padding: 20px; background: #fff; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); border-radius: 8px; }
    .form-label { font-weight: bold; margin-bottom: 5px; display: block; color: #007bff; }
    .form-control { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ced4da; border-radius: 5px; }
    .btn-primary { background-color: #007bff; border: none; padding: 10px; color: white; border-radius: 5px; cursor: pointer; }
    .btn-primary:hover { background-color: #0056b3; }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Schedule</h2>
    <?php if($message) echo "<p>$message</p>"; ?>
    <form action="edit_schedule.php?id=<?= htmlspecialchars($schedule_id) ?>" method="POST">
        <div class="mb-3">
            <label for="event_id" class="form-label">Event</label>
            <select name="event_id" id="event_id" class="form-control" required>
                <option value="">Select an event</option>
                <?php while($row = $events_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['EventID']) ?>" <?= ($row['EventID'] == $schedule['EventID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['EventID']) ?> - <?= htmlspecialchars($row['Events_name']) ?> (<?= htmlspecialchars($row['ClientName']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="start_date_time" class="form-label">Start Date and Time</label>
            <input type="datetime-local" name="start_date_time" id="start_date_time" class="form-control" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($schedule['start_date_time']))) ?>" required>
        </div>
        <div class="mb-3">
            <label for="end_date_time" class="form-label">End Date and Time</label>
            <input type="datetime-local" name="end_date_time" id="end_date_time" class="form-control" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($schedule['end_date_time']))) ?>" required>
        </div>
        <button type="submit" class="btn-primary">Update Schedule</button>
    </form>
</div>
</body>
</html>
<?php $conn->close(); ?>