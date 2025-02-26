<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h2 { text-align: center; color: #333; }
        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }
        .navigation .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: 0.3s;
        }
        .navigation .btn:hover { background-color: #0056b3; }
        #currentMonthYear {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th { 
            background: url('https://i.pinimg.com/736x/99/6a/00/996a00a97d04af213316d5a4a6b38a93.jpg') no-repeat center center;
            background-size: cover;
            color: white; 
        }
        th, td {
            border: 1px solid #ddd;
            text-align: center;
            padding: 15px;
            font-size: 16px;
        }
        td { 
            background-color: #f9f9f9; 
            cursor: pointer; 
            position: relative; /* Added so badge can be positioned absolutely */
        }
        /* New badge positioning */
        .schedule-count {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            padding: 2px 5px;
            font-size: 12px;
            border-radius: 50%;
        }
        .event {
            display: block;
            text-align: center;
            font-size: 14px;
            padding: 5px;
            margin: 2px 0;
            border-radius: 5px;
        }
        .event-birthday { background-color: #28a745; color: white; }
        .event-doctors { background-color: #dc3545; color: white; }
        .event-holiday { background-color: #ffc107; color: black; }
        .mt-3 { margin-top: 20px; }
        .btn-success {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-success:hover { background-color: #218838; }
        .today { background-color: #ffeb3b; font-weight: bold; }
        /* New CSS for schedule statuses */
        .schedule-confirm {
            color: blue;
            font-size: 12px;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .schedule-cancel {
            color: red;
            font-size: 12px;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    <div class="container" style="margin-top: 120px;">
        <h2>Event Calendar</h2>
        <div class="navigation">
            <!-- Changed "Previous" to left arrow -->
            <a href="?year=<?php echo $prev->format('Y'); ?>&month=<?php echo $prev->format('n'); ?>" class="btn">&larr;</a>
            <span id="currentMonthYear"><?php echo $currentDate->format('F Y'); ?></span>
            <!-- Changed "Next" to right arrow -->
            <a href="?year=<?php echo $next->format('Y'); ?>&month=<?php echo $next->format('n'); ?>" class="btn">&rarr;</a>
        </div>
        <table>
            <tr>
                <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
            </tr>
            <?php
            $cell = 0;
            echo "<tr>";
            // Empty cells before the start of the month
            for ($i = 0; $i < $startDay; $i++) {
                echo "<td></td>";
                $cell++;
            }
            // Days of the month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentCellDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
                $classes = [];
                if ($currentCellDate == $currentDateStr) { 
                    $classes[] = 'today';
                }
                if (isset($bookings[$currentCellDate])) {
                    $classes[] = 'has-schedule';
                }
                $classString = implode(" ", $classes);
                echo "<td class='$classString' onclick=\"window.location.href='schedule_detail.php?date={$currentCellDate}'\">";
                echo $day;
                if (isset($bookings[$currentCellDate])) {
                    $numSchedules = count($bookings[$currentCellDate]);
                    echo "<span class='schedule-count'>{$numSchedules}</span>";
                    $firstEvent = $bookings[$currentCellDate][0]['Events_name'];
                    // Transform event name for confirmed schedules
                    if (strtolower($firstEvent) === 'badminton') {
                        $firstEvent = 'Confirmed';
                    }
                    // Determine status class based on event name
                    if ($firstEvent === 'Confirmed') {
                        $statusClass = 'schedule-confirm';
                    } elseif (strtolower($firstEvent) === 'cancelled' || strtolower($firstEvent) === 'cancel') {
                        $statusClass = 'schedule-cancel';
                    } else {
                        $statusClass = ''; // optional: leave unstyled or add default styling
                    }
                    if ($statusClass) {
                        echo "<div class='{$statusClass}'>{$firstEvent}</div>";
                    } else {
                        echo "<div>{$firstEvent}</div>";
                    }
                }
                echo "</td>";
                $cell++;
                if ($cell % 7 == 0) {
                    echo "</tr><tr>";
                }
            }
            // Fill remaining cells in the last row
            while ($cell % 7 != 0) {
                echo "<td></td>";
                $cell++;
            }
            echo "</tr>";
            ?>
        </table>
        <div class="mt-3 text-center">
            <a href="insert_schedule.php" class="btn btn-success">Create Schedule</a>
        </div>
    </div>
</body>
</html>
