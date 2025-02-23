<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Join Clients, Events and Schedule tables
$query = "SELECT s.ScheduleID, c.clients_name, c.contact_number, c.location, e.Events_name, s.start_date_time, s.end_date_time
          FROM Schedule s
          JOIN Events e ON s.EventID = e.EventID
          JOIN Clients c ON e.ClientID = c.ClientID";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule List</title>
    <style>
        .schedule-form { border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; }
        .schedule-form div { margin-bottom: 8px; }
        .schedule-form label { display: block; }
        .schedule-form input { width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>
<div class="container">
    <h2>Schedule List</h2>
    <?php while($row = $result->fetch_assoc()) { ?>
    <form method="POST" action="update_schedule.php" class="schedule-form">
        <div>
            <label>Schedule ID:
                <!-- Display ScheduleID and pass it via hidden field -->
                <?php echo $row['ScheduleID']; ?>
                <input type="hidden" name="ScheduleID" value="<?php echo $row['ScheduleID']; ?>">
            </label>
        </div>
        <div>
            <label>Client Name:
                <input type="text" name="clients_name" value="<?php echo htmlspecialchars($row['clients_name']); ?>"></input>
            </label>
        </div>
        <div>
            <label>Contact Number:
                <input type="text" name="contact_number" value="<?php echo htmlspecialchars($row['contact_number']); ?>"></input>
            </label>
        </div>
        <div>
            <label>Location:
                <input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>"></input>
            </label>
        </div>
        <div>
            <label>Event Name:
                <input type="text" name="Events_name" value="<?php echo htmlspecialchars($row['Events_name']); ?>"></input>
            </label>
        </div>
        <div>
            <label>Start Date:
                <input type="datetime-local" name="start_date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($row['start_date_time'])); ?>"></input>
            </label>
        </div>
        <div>
            <label>End Date:
                <input type="datetime-local" name="end_date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($row['end_date_time'])); ?>"></input>
            </label>
        </div>
        <div>
            <button type="submit" class="btn-primary">Submit</button>
        </div>
    </form>
    <?php } ?>
</div>
</body>
</html>
<?php $conn->close(); ?>
