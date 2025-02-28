<?php
session_start();
include 'db.php'; // ...existing DB connection code...
include 'navigation.php';

$date = isset($_GET['date']) ? $_GET['date'] : '';
if(!$date) {
    die("Invalid date.");
}

// Updated query to join the Status and select its value
$sql = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, c.contact_number, c.location, us.updated_status
        FROM Schedule s
        JOIN Events e ON s.EventID = e.EventID
        JOIN Clients c ON e.ClientID = c.ClientID
        JOIN Updated_Status us ON s.StatusID = us.StatusID
        WHERE DATE(s.start_date_time) = '$date'
        ORDER BY s.start_date_time ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedules for <?php echo $date; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- ...existing head content... -->
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4">Schedules for <?php echo $date; ?></h2>
    <!-- Updated back button with transparent background, no border and red '✖' -->
    <div class="d-flex justify-content-end mb-3">
        <a href="calendar.php" class="btn" style="background: transparent; border: none; color: red;">✖</a>
    </div>
    <?php if($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Contact Number</th>
                        <th>Location</th>
                        <th>Event</th>
                        <th>Start Date/Time</th>
                        <th>End Date/Time</th>
                        <th>Status</th>
                        <th>Actions</th> <!-- Added actions column -->
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $highlighted = []; // Track first schedule per client
                    while($row = $result->fetch_assoc()): 
                          $startFormatted = date("l, F j, Y g:i A", strtotime($row['start_date_time']));
                          $endFormatted   = date("l, F j, Y g:i A", strtotime($row['end_date_time']));
                          $client = $row['clients_name'];
                          $highlightClass = !isset($highlighted[$client]) ? 'table-warning' : '';
                          $highlighted[$client] = true;
                    ?>
                        <tr class="<?php echo $highlightClass; ?>">
                            <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
                            <td><?php echo $startFormatted; ?></td>
                            <td><?php echo $endFormatted; ?></td>
                            <td>
                                <?php 
                                $status = $row['updated_status'];
                                if ($status === 'Confirmed') {
                                    echo "<span style='color: blue;'>".htmlspecialchars($status)."</span>";
                                } elseif (in_array(strtolower($status), ['cancel', 'cancelled'])) {
                                    echo "<span style='color: red;'>".htmlspecialchars($status)."</span>";
                                } else {
                                    echo htmlspecialchars($status);
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href='edit_schedule.php?id=<?php echo $row['ScheduleID']; ?>'">
                                    Edit
                                </button>
                                &nbsp;
                                <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Are you sure you want to delete this schedule?')) { window.location.href='delete_schedule.php?id=<?php echo $row['ScheduleID']; ?>'; }">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">No schedule for this day</p>
    <?php endif; ?>
    <div class="text-center mt-3">
    </div>
</div>
<!-- ...existing JS code... -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
