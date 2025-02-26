<?php
session_start();
include 'db.php';
error_reporting(E_ALL);

// Calculate tomorrow's date and format it as letters
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$tomorrowFormatted = date('l, F j, Y', strtotime($tomorrow));

// Fetch events scheduled for tomorrow
$sql = "SELECT s.ScheduleID, s.start_date_time, e.Events_name 
        FROM Schedule s 
        JOIN Events e ON s.EventID = e.EventID 
        WHERE DATE(s.start_date_time) = '$tomorrow'";
$result = $conn->query($sql);
$count = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upcoming Reminders</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS (optional) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <div class="container" style="margin-top: 120px;">
        <?php if ($count > 0): ?>
            <div class="alert alert-danger" role="alert">
                You have upcoming event reminders for tomorrow (<?php echo $tomorrowFormatted; ?>):
                <?php if ($count === 1 || $count === 2): ?>
                    <p class="mb-0"><strong>Alert:</strong> Only <?php echo $count; ?> event<?php echo $count > 1 ? 's' : ''; ?> scheduled for tomorrow.</p>
                <?php endif; ?>
                <ul>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li>
                            <?php 
                            $time = date('h:i A', strtotime($row['start_date_time'])); 
                            echo "{$row['Events_name']} at {$time}";
                            ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary" role="alert">
                No upcoming events for tomorrow.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
