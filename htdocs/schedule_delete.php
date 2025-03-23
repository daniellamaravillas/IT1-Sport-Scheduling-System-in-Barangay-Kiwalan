<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Check if ScheduleID is provided and form was submitted
if (!isset($_GET['ScheduleID'])) {
    header("Location: schedule.php");
    exit();
}

$scheduleID = $conn->real_escape_string($_GET['ScheduleID']);

// Fetch schedule details for confirmation
$query = "SELECT s.*, e.Events_name, c.clients_name 
          FROM Schedule s
          JOIN Events e ON s.EventID = e.EventID
          JOIN Clients c ON e.ClientID = c.ClientID
          WHERE s.ScheduleID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

// Only process delete if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleteQuery = "DELETE FROM Schedule WHERE ScheduleID = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $scheduleID);

    if ($stmt->execute()) {
        header("Location: schedule.php?msg=deleted");
    } else {
        header("Location: schedule.php?msg=error");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .delete-card {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            background-color: #fff;
        }
        .warning-icon {
            color: #dc3545;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .schedule-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <div class="delete-card">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle warning-icon"></i>
                <h4 class="mb-4">Delete Schedule Confirmation</h4>
            </div>
            
            <div class="schedule-details">
                <p><strong>Event:</strong> <?php echo htmlspecialchars($schedule['Events_name']); ?></p>
                <p><strong>Client:</strong> <?php echo htmlspecialchars($schedule['clients_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($schedule['start_date_time'])); ?></p>
                <p><strong>Time:</strong> 
                    <?php echo date('g:i A', strtotime($schedule['start_date_time'])); ?> - 
                    <?php echo date('g:i A', strtotime($schedule['end_date_time'])); ?>
                </p>
            </div>

            <p class="text-danger text-center mb-4">
                Are you sure you want to delete this schedule? This action cannot be undone.
            </p>

            <form method="post" class="d-flex justify-content-center gap-3">
                <a href="schedule.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Delete Schedule</button>
            </form>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
