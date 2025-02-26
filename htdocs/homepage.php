<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'navigation.php';

$email = $_SESSION['email'];

// Secure query using prepared statements
$query = "SELECT username FROM users WHERE email = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Database error: " . $conn->error);
}

$username = $user ? htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') : "Guest";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Home</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .highlight-red {
            background-color: #f8d7da;
            border: 2px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
        }
        .highlight-blue {
            background-color: #d1ecf1;
            border: 2px solid #bee5eb;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container mt-4 text-center">
    <div class="card">
        <div class="card-header text-center">
            <h2>Welcome, <?php echo $username; ?>!</h2>
        </div>
        <div class="card-body text-center">
            <p>Here you can find the latest schedules for sports events.</p>
            <p>Explore the different events available in our community.</p>
            <?php
            // Query today's schedule with person (client) details
            $today = date('Y-m-d');
            $queryToday = "SELECT s.ScheduleID, s.start_date_time, e.Events_name, c.clients_name 
                           FROM Schedule s 
                           JOIN Events e ON s.EventID = e.EventID 
                           JOIN Clients c ON e.ClientID = c.ClientID 
                           WHERE DATE(s.start_date_time) = ?";
            if($stmtToday = $conn->prepare($queryToday)) {
                $stmtToday->bind_param("s", $today);
                $stmtToday->execute();
                $resultToday = $stmtToday->get_result();
                // Use red highlight if schedule exists; blue if not.
                $highlightClass = ($resultToday->num_rows > 0) ? 'highlight-red' : 'highlight-blue';
                ?>
                <div class="<?php echo $highlightClass; ?>">
                    <h4>Today's Schedule (<?php echo date('l, F j, Y'); ?>):</h4>
                    <?php if($resultToday->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while($row = $resultToday->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <?php 
                                    $time = date('h:i A', strtotime($row['start_date_time']));
                                    echo "{$row['Events_name']} at {$time} - Scheduled for: " . htmlspecialchars($row['clients_name']);
                                    ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <!-- Added button below the schedule list -->
                        <button class="btn btn-primary btn-sm mt-2" onclick="window.location.href='calendar.php'">View Schedule</button>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <p class="mb-0">No event scheduled for today.</p>
                            <button class="btn btn-primary btn-sm ml-2" onclick="window.location.href='calendar.php'">View Schedule</button>
                        </div>
                    <?php endif; ?>
                </div>
                <?php 
                $stmtToday->close();
            }
            ?>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
