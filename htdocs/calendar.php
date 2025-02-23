<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);

// Determine current month/year or use GET parameters
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : date('n');

// Calculate dates for navigation
$currentDate = DateTime::createFromFormat('Y-n-j', "$year-$month-1");
$daysInMonth = $currentDate->format('t');
$startDay = $currentDate->format('w'); // 0 (for Sunday) through 6

// Previous and next month calculation
$prev = clone $currentDate;
$prev->modify('-1 month');
$next = clone $currentDate;
$next->modify('+1 month');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Schedule</title>
    <link rel="stylesheet" href="calendar.css">
    <style>
        table { border-collapse: collapse; width:100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Calendar Schedule</h2>
    <div class="navigation">
        <a href="?year=<?php echo $prev->format('Y'); ?>&month=<?php echo $prev->format('n'); ?>">Previous</a>
        <span><?php echo $currentDate->format('F Y'); ?></span>
        <a href="?year=<?php echo $next->format('Y'); ?>&month=<?php echo $next->format('n'); ?>">Next</a>
    </div>
    <table>
        <tr>
            <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
            <th>Thu</th><th>Fri</th><th>Sat</th>
        </tr>
        <?php
        // Start building the calendar grid.
        $cell = 0;
        echo "<tr>";
        // Print empty cells until the first day.
        for ($i = 0; $i < $startDay; $i++) {
            echo "<td></td>";
            $cell++;
        }
        // Print days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            echo "<td onclick=\"window.location.href='insert_client.php?year={$year}&month={$month}&day={$day}'\" style='cursor:pointer;'>$day</td>";
            $cell++;
            if ($cell % 7 == 0) {
                echo "</tr><tr>";
            }
        }
        // Fill in the remaining cells of the last week.
        while ($cell % 7 != 0) {
            echo "<td></td>";
            $cell++;
        }
        echo "</tr>";
        ?>
    </table>
    <!-- Optionally, include the schedule form or further calendar functionality -->
</div>
</body>
</html>
