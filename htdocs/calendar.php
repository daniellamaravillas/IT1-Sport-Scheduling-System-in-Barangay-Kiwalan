<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
        include 'db.php'; // add database connection if not already included
        include 'navigation.php'; // add navigation bar if not already included
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
        WHERE us.updated_status != 'pending'
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
    <style>
        /* General Styling */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(to right, #FFEDD5, #FFE4C4);
    color: #A45C40;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Calendar Container */
.container {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    text-align: center;
}

/* Navigation */
.navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.navigation a {
    background-color: #FF8A5B;
    color: white;
    padding: 10px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.navigation a:hover {
    background-color: #E76F51;
    box-shadow: 0px 4px 10px rgba(231, 111, 81, 0.5);
}

/* Current Month & Year */
#currentMonthYear {
    font-size: 22px;
    font-weight: bold;
    color: #E76F51;
}

/* Calendar Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

th, td {
    text-align: center;
    padding: 15px;
    font-size: 16px;
    transition: 0.3s;
}

/* Header Row */
th {
    color: #E76F51;
    font-weight: bold;
}

/* Calendar Cells */
td {
    cursor: pointer;
    background: #FFE4C4;
    border-radius: 10px;
    transition: 0.3s;
    position: relative;
}

/* Hover Effect */
td:hover {
    background: #E9C46A;
    transform: scale(1.1);
}

/* Selected Date */
td.selected {
    background-color: #E76F51;
    color: white;
    font-weight: bold;
    box-shadow: 0px 4px 10px rgba(231, 111, 81, 0.5);
}

/* Booking Badges */
.badge-danger {
    background-color: #D62828;
    color: white;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: 12px;
    position: absolute;
    bottom: 5px;
    right: 5px;
}

.badge-secondary {
    background-color: #6C757D;
    color: white;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: 12px;
    position: absolute;
    bottom: 5px;
    left: 5px;
}

/* Create Schedule Button */
.btn-success {
    background-color: #2A9D8F;
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
    display: inline-block;
    margin-top: 15px;
}

.btn-success:hover {
    background-color: #21867A;
    box-shadow: 0px 4px 10px rgba(42, 157, 143, 0.5);
}

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
    <center><h2>Calendar Schedule</h2></center>
    <div class="navigation text-center">
        <a href="?year=<?php echo $prev->format('Y'); ?>&month=<?php echo $prev->format('n'); ?>" class="btn btn-primary">Previous</a>
        <span id="currentMonthYear"><?php echo $currentDate->format('F Y'); ?></span>
        <a href="?year=<?php echo $next->format('Y'); ?>&month=<?php echo $next->format('n'); ?>" class="btn btn-primary">Next</a>
    </div>
    <table style="margin: 0 auto;">
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
                // Count active and cancelled appointments
                $activeCount = 0;
                $cancelCount = 0;
                foreach ($bookings[$currentCellDate] as $appointment) {
                    if (strtolower($appointment['updated_status']) === 'cancel') {
                        $cancelCount++;
                    } else {
                        $activeCount++;
                    }
                }
                if ($activeCount > 0) {
                    echo " <span class='badge badge-danger' style='cursor:pointer;' onclick=\"event.stopPropagation(); window.location.href='insert_schedule.php?viewSchedules=1&date={$currentCellDate}';\">Scheduled ({$activeCount})</span>";
                }
                if ($cancelCount > 0) {
                    echo " <span class='badge badge-secondary' style='cursor:pointer;' onclick=\"event.stopPropagation(); window.location.href='insert_schedule.php?viewSchedules=1&date={$currentCellDate}';\">Cancelled ({$cancelCount})</span>";
                }
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
    <!-- New Create Schedule button added below the table -->
    <div class="mt-3 text-center">
        <a href="insert_schedule.php" class="btn btn-success">Create Schedule</a>
    </div>
    <!-- Optionally, include further calendar functionality -->
</div>

<!-- Bootstrap Modal for View Schedule Details -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true">
  </div>
</div>
</body>
</html>