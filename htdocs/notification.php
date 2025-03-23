<?php
session_start();
include 'db.php';
include 'navigation.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Get tomorrow's date
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Fetch schedules
$stmt = $conn->prepare(
    "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name 
     FROM Schedule s
     JOIN Events e ON s.EventID = e.EventID
     JOIN Clients c ON e.ClientID = c.ClientID
     WHERE DATE(s.start_date_time) >= CURDATE()
     ORDER BY s.start_date_time ASC"
);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <style>
        .notification-card {
            margin-bottom: 15px;
            border-left: 5px solid #ccc;
        }
        .notification-card.urgent {
            border-left-color: #0d6efd; /* Default to blue for urgent cards */
            background-color: rgba(13, 110, 253, 0.1); /* Light blue background */
        }
        .notification-card.urgent:first-of-type {
            border-left-color: #0d6efd; /* First urgent card is red */
            background-color: rgba(78, 126, 214, 0.1); /* Light red background */
        }
        .notification-badge {
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 10px;
        }
        .urgent-badge {
            background-color: #dc3545; /* Keep the count badge red */
            color: white;
        }
        .badge.bg-danger {
            background-color: #0d6efd !important; /* Red for first notification */
        }
        .badge.bg-primary {
            background-color: #0d6efd !important; /* Blue for other notifications */
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>
            <i class="bi bi-bell"></i> Notifications
            <?php 
            // Count tomorrow's schedules
            $tomorrowCount = 0;
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
                if (date('Y-m-d', strtotime($row['start_date_time'])) === $tomorrow) {
                    $tomorrowCount++;
                }
            }
            if ($tomorrowCount > 0) {
                echo "<span class='notification-badge urgent-badge'>$tomorrowCount</span>";
            }
            ?>
        </h2>

        <div class="notifications-container">
            <?php
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
                $scheduleDate = date('Y-m-d', strtotime($row['start_date_time']));
                $isUrgent = ($scheduleDate === $tomorrow);
                ?>
                <div class="card notification-card <?php echo $isUrgent ? 'urgent' : ''; ?>">
                    <div class="card-body">
                        <h5 class="card-title d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($row['Events_name']); ?>
                            <?php if ($isUrgent): ?>
                                <span class="badge bg-danger">Tomorrow!</span>
                            <?php endif; ?>
                        </h5>
                        <div class="card-text">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><i class="bi bi-person"></i> <strong>Client:</strong> 
                                        <?php echo htmlspecialchars($row['clients_name']); ?>
                                    </p>
                                    <p><i class="bi bi-calendar-event"></i> <strong>Start Date:</strong> 
                                        <?php echo date('F j, Y', strtotime($row['start_date_time'])); ?>
                                    </p>
                                    <p><i class="bi bi-calendar-check"></i> <strong>End Date:</strong> 
                                        <?php echo date('F j, Y', strtotime($row['end_date_time'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="bi bi-clock"></i> <strong>Start Time:</strong> 
                                        <?php echo date('g:i A', strtotime($row['start_date_time'])); ?>
                                    </p>
                                    <p><i class="bi bi-clock-fill"></i> <strong>End Time:</strong> 
                                        <?php echo date('g:i A', strtotime($row['end_date_time'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            if ($result->num_rows === 0) {
                echo "<p class='text-muted'>No upcoming schedules.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
