<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'navigation.php';
error_reporting(E_ALL);

// Determine current month/year or use GET parameters
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');

$currentDate = DateTime::createFromFormat('Y-n-j', "$year-$month-1");
$daysInMonth = $currentDate->format('t');
$startDay = $currentDate->format('w');

$prev = clone $currentDate;
$prev->modify('-1 month');
$next = clone $currentDate;
$next->modify('+1 month');

// Fetch schedules
$sql = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, us.updated_status
        FROM Schedule s
        JOIN Events e ON s.EventID = e.EventID
        JOIN Clients c ON e.ClientID = c.ClientID
        JOIN Updated_Status us ON s.StatusID = us.StatusID
        WHERE us.updated_status != 'pending'
        ORDER BY s.start_date_time ASC";
$result = $conn->query($sql);

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['start_date_time']));
    if (!isset($bookings[$date])) {
        $bookings[$date] = [];
    }
    $bookings[$date][] = $row;
}

$currentDateStr = date('Y-m-d');

// Fetch username
$email = $_SESSION['email'];
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
    <!-- Add Bootstrap CSS for modal functionality -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            text-align: center; /* Center the text */
            padding-top: 50px; /* Ensure content is visible despite the top bar */
            padding-left: 250px; /* Ensure content is visible despite the sidebar */
        }
        
        .container {
            display: flex;
            flex-direction: row;
            max-width: 1200px;
            width: 95%;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(245, 245, 245, 0.08);
            border-radius: 10px;
            margin: 20px auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: 20px; /* Adjusted margin-top */
        }
        
        @media (max-width: 992px) {
            body {
                padding-left: 0; /* Remove left padding on smaller screens */
            }
            .container {
                flex-direction: column;
                margin-top: 70px; /* Adjusted margin-top for smaller screens */
            }
        }
        
        .calendar-container, .tasks-container {
            padding: 20px;
        }
        
        .calendar-container {
            flex: 1;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            min-width: 320px;
            transition: transform 0.2s ease;
        }
        
        .calendar-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tasks-container {
            flex: 2;
        }
        
        h2 {
            text-align: center;
            color: #333;
            font-weight: 500;
            margin-bottom: 25px;
            position: relative;
        }
        
        h2:after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #007bff;
            margin: 8px auto 0;
            border-radius: 2px;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 5px;
        }
        
        .navigation .btn {
            background-color: #0d6efd;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-weight: 500;
        }
        
        .navigation .btn:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        
        #currentMonthYear {
            font-weight: 600;
            font-size: 1.2rem;
            color: #444;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            background: white;
            margin-bottom: 20px;
        }
        
        th { 
            background-color: #0d6efd;
            color: white; 
            padding: 12px 10px;
            font-weight: 500;
            border-radius: 4px;
        }
        
        td {
            border: 1px solid #e9ecef;
            text-align: center;
            padding: 12px 8px;
            position: relative;
            border-radius: 4px;
            transition: all 0.2s ease;
            height: 45px;
        }
        
        td:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 2;
        }
        
        .vacant {
            background-color: #e7f5ea;
            cursor: default;
        }
        
        .booked {
            background-color: #fff0f0;
            cursor: pointer;
        }
        
        .booked:hover {
            background-color: #ffe6e6;
        }
        
        .done {
            background-color: #f8f9fa;
            color: #adb5bd;
        }
        
        .today {
            background-color: #fff7d6;
            box-shadow: inset 0 0 0 2px #ffc107;
            font-weight: bold;
        }
        
        .schedule-count {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: #dc3545;
            color: white;
            padding: 2px 5px;
            font-size: 11px;
            border-radius: 10px;
            min-width: 18px;
        }
        
        .highlight-red {
            background-color: #fff0f0;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(220,53,69,0.1);
            transition: all 0.3s ease;
        }
        
        .highlight-blue {
            background-color: #e9f7fb;
            border-left: 4px solid #0dcaf0;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(13,202,240,0.1);
            transition: all 0.3s ease;
        }
        
        .highlight-red:hover, .highlight-blue:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .list-group {
            list-style-type: none;
            padding: 0;
            margin: 10px 0;
        }
        
        .list-group-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        .faded-text {
            color: #6c757d;
            font-style: italic;
        }
        
        .introduction-container {
            background-color: #ffffff;
            border-left: 4px solid #0d6efd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .introduction-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .create-schedule-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn-success {
            background-color: #198754;
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-weight: 500;
        }
        
        .btn-success:hover {
            background-color: #157347;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(25,135,84,0.2);
        }
        
        .modal-xl {
            max-width: 90%;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 98%;
                padding: 10px;
                margin: 10px auto;
            }
            
            .calendar-container, .tasks-container {
                padding: 15px 10px;
            }
            
            th, td {
                padding: 8px 5px;
                font-size: 14px;
            }
            
            .schedule-count {
                padding: 1px 4px;
                font-size: 10px;
            }
            
            .modal-xl {
                max-width: 95%;
            }
        }
        
        @media (max-width: 576px) {
            td {
                height: 40px;
                padding: 5px;
            }
            
            th {
                padding: 8px 5px;
            }
        }
        
        /* Animation for calendar cells */
        @keyframes pulseAnimation {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
        
        .booked.today {
            animation: pulseAnimation 2s infinite;
        }
        
        /* Improve accessibility */
        .btn:focus, a:focus {
            outline: 2px solid #0d6efd;
            outline-offset: 2px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navigation = document.querySelector('.navigation');
            const btnLeft = navigation.querySelector('.btn:first-child');
            const btnRight = navigation.querySelector('.btn:last-child');

            btnLeft.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                btnLeft.classList.add('move-left');
                setTimeout(() => {
                    btnLeft.classList.remove('move-left');
                    window.location.href = btnLeft.href; // Navigate after the animation
                }, 300); // Remove the class after the transition duration
            });

            btnRight.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                btnRight.classList.add('move-right');
                setTimeout(() => {
                    btnRight.classList.remove('move-right');
                    window.location.href = btnRight.href; // Navigate after the animation
                }, 300); // Remove the class after the transition duration
            });
        });
    </script>
</head>
<body>
    <!-- New hover area for triggering the effect -->
    <div id="leftHover" style="position: fixed; left:0; top:0; width:50px; height:100vh; z-index:9999;"></div>
    <?php include 'navigation.php'; ?>
    <div class="container" style="margin-top: 20px; height: 620px;">
        <div class="tasks-container">
            <?php
            // Query today's schedule with person (client) details
            $today = date('Y-m-d');
            $queryToday = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name 
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
            <div class="introduction-container" style="margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 8px;">
                <center><h4>Welcome to the Barangay Kiwalan <?php echo $username; ?></h4></center>
                <p>Here you can find the latest schedules for sports events.</p>
                <p>Explore the different events available in our community.</p>
                <center><b><p class="faded-text">@Schedule now in Barangay Kiwalan</p></b></center>
                
            </div>
            <br> <br> <br> <br> <br> 
                <div class="<?php echo $highlightClass; ?>" style="text-align: center;">
                    <h4>Today's Schedule (<?php echo date('l, F j, Y'); ?>):</h4>
                    <?php if($resultToday->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while($row = $resultToday->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <?php 
                                    $startTime = date('h:i A', strtotime($row['start_date_time']));
                                    $endTime = date('h:i A', strtotime($row['end_date_time']));
                                    echo "{$row['Events_name']} from {$startTime} to {$endTime} - Scheduled for: " . htmlspecialchars($row['clients_name']);
                                    ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <p class="mb-0">No event scheduled for today.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php 
                $stmtToday->close();
            }
            ?>
        </div>
        <div class="calendar-container">
            <h2><i class="fas fa-calendar-alt"></i> Schedule</h2>
            <div class="navigation">
                <a href="?year=<?php echo $prev->format('Y'); ?>&month=<?php echo $prev->format('n'); ?>" class="btn">&larr;</a>
                <span id="currentMonthYear"><?php echo $currentDate->format('F Y'); ?></span>
                <a href="?year=<?php echo $next->format('Y'); ?>&month=<?php echo $next->format('n'); ?>" class="btn">&rarr;</a>
            </div>
            <table>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
                <?php
                $cell = 0;
                echo "<tr>";
                for ($i = 0; $i < $startDay; $i++) {
                    echo "<td></td>";
                    $cell++;
                }
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $currentCellDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $classes = [];
                    if ($currentCellDate == $currentDateStr) {
                        $classes[] = 'today';
                    } elseif ($currentCellDate < $currentDateStr) {
                        $classes[] = 'done';
                    } elseif (isset($bookings[$currentCellDate])) {
                        $classes[] = 'booked';
                    } else {
                        $classes[] = 'vacant';
                    }
                    $classString = implode(" ", $classes);
                    echo "<td class='$classString'>";
                    if (isset($bookings[$currentCellDate])) {
                        // Make booked dates trigger the modal
                        echo "<a href='#' class='show-schedule' data-date='{$currentCellDate}'>$day</a>";
                        $numSchedules = count($bookings[$currentCellDate]);
                        echo "<div class='schedule-count'>$numSchedules</div>";
                    } else {
                        // All other dates (vacant, done, today without bookings) are not clickable
                        echo "<span>$day</span>";
                    }
                    echo "</td>";
                    $cell++;
                    if ($cell % 7 == 0) {
                        echo "</tr><tr>";
                    }
                }
                while ($cell % 7 != 0) {
                    echo "<td></td>";
                    $cell++;
                }
                echo "</tr>";
                ?>
            </table>
            <div class="create-schedule-container">
                <a href="create_schedule.php" class="btn btn-success">Create Schedule</a>
            </div>
        </div>
    </div>
    
    <!-- Schedule Details Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Schedule Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div id="scheduleContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Required JS for Bootstrap and our custom functionality -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // New effect: when mouse enters the left hover area, shift container right without blurring.
        const leftHover = document.getElementById('leftHover');
        const container = document.querySelector('.container');
        leftHover.addEventListener('mouseenter', function() {
            container.style.transform = "translateX(20px)";
        });
        leftHover.addEventListener('mouseleave', function() {
            container.style.transform = "translateX(0)";
        });
        
        // Handle schedule modal loading
        $(document).ready(function() {
            $('.show-schedule').on('click', function(e) {
                e.preventDefault();
                
                const date = $(this).data('date');
                
                // Show the modal with loading spinner
                $('#scheduleModal').modal('show');
                $('.modal-loading').show();
                $('#scheduleContent').hide();
                
                // Load schedule details via AJAX
                $.ajax({
                    url: 'get_schedule_details.php',
                    type: 'GET',
                    data: { date: date },
                    success: function(response) {
                        // Hide loading spinner and display content
                        $('.modal-loading').hide();
                        $('#scheduleContent').html(response).show();
                        
                        // Update modal title with formatted date
                        const formattedDate = new Date(date).toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        $('#scheduleModalLabel').text('Schedule Details for ' + formattedDate);
                        
                        // Add event handlers for the edit and delete buttons in the modal
                        $('#scheduleContent').on('click', '.btn-edit', function() {
                            const scheduleId = $(this).data('id');
                            window.location.href = 'edit_schedule.php?ScheduleID=' + scheduleId;
                        });
                        
                        $('#scheduleContent').on('click', '.btn-delete', function() {
                            const scheduleId = $(this).data('id');
                            if(confirm('Are you sure you want to delete this schedule?')) {
                                window.location.href = 'delete_schedule.php?ScheduleID=' + scheduleId;
                            }
                        });
                    },
                    error: function() {
                        $('.modal-loading').hide();
                        $('#scheduleContent').html('<div class="alert alert-danger">Failed to load schedule details.</div>').show();
                    }
                });
            });
        });
    </script>
</body>
</html>