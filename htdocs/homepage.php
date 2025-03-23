<?php
session_start();
include 'db.php';
include 'navigation.php';

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    // Redirect to the login page if not logged in
    header("Location: index.php");
    exit();
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Get today's and tomorrow's dates
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Fetch schedules for both today and tomorrow
$stmt = $conn->prepare(
    "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, 
            e.Events_name, c.clients_name
     FROM Schedule s
     JOIN Events e ON s.EventID = e.EventID
     JOIN Clients c ON e.ClientID = c.ClientID
     WHERE (DATE(s.start_date_time) <= ? AND DATE(s.end_date_time) >= ?) OR
           (DATE(s.start_date_time) <= ? AND DATE(s.end_date_time) >= ?)
     ORDER BY s.start_date_time ASC"
);
$stmt->bind_param("ssss", $tomorrow, $today, $tomorrow, $tomorrow);
$stmt->execute();
$result = $stmt->get_result();

// Separate schedules by day
$todaySchedules = [];
$tomorrowSchedules = [];
while ($row = $result->fetch_assoc()) {
    $start_date = date('Y-m-d', strtotime($row['start_date_time']));
    $end_date = date('Y-m-d', strtotime($row['end_date_time']));
    
    // Add to today's schedules if the event overlaps with today
    if ($end_date >= $today && $start_date <= $today) {
        $todaySchedules[] = $row;
    }
    
    // Add to tomorrow's schedules if the event overlaps with tomorrow
    if ($end_date >= $tomorrow && $start_date <= $tomorrow) {
        $tomorrowSchedules[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - Schedules</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Layout */
        .content-container {
            margin-left: 230px;
            padding: 20px;
        }

        /* Welcome Section */
        .welcome-section {
            margin-top: 50px;
            margin-bottom: 40px;
            text-align: center;
        }
        .welcome-section h4 {
            font-weight: 600;
            margin-bottom: 15px;
        }
        .welcome-section .username {
            color: #0d6efd;
        }
        .welcome-section p {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .welcome-section .faded-text {
            color: #6c757d;
            font-style: italic;
        }

        /* Schedule Sections */
        .schedule-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .schedule-section h2 {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        /* Schedule Cards */
        .schedule-card {
            margin-bottom: 15px;
            border: none;
            border-left: 5px solid;
            border-radius: 8px;
            transition: transform 0.2s ease;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
        }
        .today-card {
            border-left-color: #198754;
            background-color: rgba(25, 135, 84, 0.1);
        }
        .tomorrow-card {
            border-left-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
        }

        /* Schedule Count Badges */
        .schedule-count {
            font-size: 0.9rem;
            padding: 5px 15px;
            border-radius: 20px;
            margin-left: 10px;
            font-weight: 500;
        }
        .today-count {
            color: #198754;
            background-color: rgba(25, 135, 84, 0.1);
        }
        .tomorrow-count {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
        }

        /* No Schedule State */
        .no-schedule {
            padding: 30px;
            text-align: center;
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .no-schedule i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #adb5bd;
        }
        .no-schedule p {
            margin: 0;
            font-size: 1.1rem;
        }

        /* Card Content */
        .card-body {
            padding: 1.25rem;
        }
        .card-title {
            color: #212529;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .card-text {
            color: #495057;
            line-height: 1.6;
        }
        .card-text strong {
            color: #212529;
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <div class="content-container">
        <div class="container welcome-section text-center">
            <h4>Welcome to Barangay Kiwalan Sports Scheduling <span class="username"><?php echo $username; ?></span></h4>
            <p>Here you can find the latest schedules for sports events.</p>
            <p>Explore the different events available in our community.</p>
            <b><p class="faded-text">@Schedule now in Barangay Kiwalan</p></b>
        </div>

        <div class="container mt-4">
            <div class="row">
                <!-- Today's Schedule Section -->
                <div class="col-md-6">
                    <div class="schedule-section">
                        <h2>
                            Today's Schedule
                            <span class="schedule-count today-count">
                                <?php echo count($todaySchedules); ?> Schedule<?php echo count($todaySchedules) !== 1 ? 's' : ''; ?>
                            </span>
                        </h2>

                        <div class="schedules mt-4">
                            <?php if (!empty($todaySchedules)): ?>
                                <?php foreach ($todaySchedules as $schedule): ?>
                                    <div class="card schedule-card today-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($schedule['Events_name']); ?></h5>
                                            <p class="card-text">
                                                <strong>Client:</strong> <?php echo htmlspecialchars($schedule['clients_name']); ?><br>
                                                <strong>Time:</strong> 
                                                <?php 
                                                    echo date('M d, Y g:i A', strtotime($schedule['start_date_time'])) . ' - ' . 
                                                         date('M d, Y g:i A', strtotime($schedule['end_date_time'])); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="card">
                                    <div class="no-schedule">
                                        <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No schedules for today</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tomorrow's Schedule Section -->
                <div class="col-md-6">
                    <div class="schedule-section">
                        <h2>
                            Tomorrow's Schedule
                            <span class="schedule-count tomorrow-count">
                                <?php echo count($tomorrowSchedules); ?> Schedule<?php echo count($tomorrowSchedules) !== 1 ? 's' : ''; ?>
                            </span>
                        </h2>

                        <div class="schedules mt-4">
                            <?php if (!empty($tomorrowSchedules)): ?>
                                <?php foreach ($tomorrowSchedules as $schedule): ?>
                                    <div class="card schedule-card tomorrow-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($schedule['Events_name']); ?></h5>
                                            <p class="card-text">
                                                <strong>Client:</strong> <?php echo htmlspecialchars($schedule['clients_name']); ?><br>
                                                <strong>Time:</strong> 
                                                <?php 
                                                    echo date('M d, Y g:i A', strtotime($schedule['start_date_time'])) . ' - ' . 
                                                         date('M d, Y g:i A', strtotime($schedule['end_date_time'])); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="card">
                                    <div class="no-schedule">
                                        <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No schedules for tomorrow</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

