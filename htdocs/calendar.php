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

include 'db.php'; // add database connection if not already included

$sql = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, us.updated_status
        FROM Schedule s
        JOIN Events e ON s.EventID = e.EventID
        JOIN Clients c ON e.ClientID = c.ClientID
        JOIN Updated_Status us ON s.StatusID = us.StatusID
        ORDER BY s.start_date_time ASC";
$result = $conn->query($sql);

// Build bookings array indexed by date (Y-m-d)
$bookings = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['start_date_time']));
    if (!isset($bookings[$date])) {
        $bookings[$date] = [];
    }
    $bookings[$date][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Schedule</title>
    <!-- Added Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="calendar.css">
    <style>
        table { border-collapse: collapse; width:100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .highlight {
            background-color: yellow;
            color: black;
            border: 2px solid red;
            padding: 4px;
            border-radius: 4px;
        }
    </style>
    <!-- Added Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script>
      // Function to load booking details for a given date and show modal
      function loadBookings(date) {
          $.ajax({
              url: 'booking_details.php',
              type: 'GET',
              data: { date: date },
              success: function(data) {
                  $('#bookingModalContent').html(data);
                  $('#bookingModal').modal('show');
              },
              error: function() {
                  alert('Error loading booking details.');
              }
          });
      }

      $(document).ready(function(){
          $('#currentMonthYear').on('click', function() {
              $(this).toggleClass('highlight');
          });
      });
    </script>
</head>
<body>
<div class="container">
    <h2>Calendar Schedule</h2>
    <div class="navigation">
        <a href="?year=<?php echo $prev->format('Y'); ?>&month=<?php echo $prev->format('n'); ?>">Previous</a>
        <span id="currentMonthYear"><?php echo $currentDate->format('F Y'); ?></span>
        <a href="?year=<?php echo $next->format('Y'); ?>&month=<?php echo $next->format('n'); ?>">Next</a>
    </div>
    <table>
        <tr>
            <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
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
        // Print days of the month with booking marks if applicable
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentCellDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
            echo "<td onclick=\"window.location.href='insert_schedule.php?year={$year}&month={$month}&day={$day}'\" style='cursor:pointer;'>";
            echo $day;
            if (isset($bookings[$currentCellDate])) {
                // Display booking mark and number of bookings
                $count = count($bookings[$currentCellDate]);
                echo " <span class='badge badge-danger' style='cursor:pointer;' onclick=\"event.stopPropagation(); loadBookings('{$currentCellDate}');\">Booked ({$count})</span><br>";
            }
            echo "</td>";
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
    <!-- Optionally, include further calendar functionality -->
</div>

<!-- Bootstrap Modal for View Schedule Details -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" id="bookingModalContent">
      <!-- AJAX loaded view schedule details will appear here -->
    </div>
  </div>
</div>
</body>
</html>