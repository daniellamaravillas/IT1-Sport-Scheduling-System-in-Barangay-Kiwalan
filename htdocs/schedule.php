<?php
session_start();
include 'db.php'; // ...existing DB connection code...
include 'navigation.php';

$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

if($searchTerm !== ''){
	$query = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, c.contact_number, c.location
	          FROM Schedule s
	          JOIN Events e ON s.EventID = e.EventID
	          JOIN Clients c ON e.ClientID = c.ClientID
	          WHERE c.clients_name LIKE '%$searchTerm%' OR c.location LIKE '%$searchTerm%' OR e.Events_name LIKE '%$searchTerm%'
	          ORDER BY s.start_date_time ASC";
} else {
	$query = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, c.contact_number, c.location
	          FROM Schedule s
	          JOIN Events e ON s.EventID = e.EventID
	          JOIN Clients c ON e.ClientID = c.ClientID
	          ORDER BY s.start_date_time ASC";
}
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Table</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- ...existing head content... -->
</head>
<body>
<div class="container mt-4">
    <center><h2 class="mb-4">List of the Schedule</h2></center>
    <!-- Added search form -->
    <form method="get" action="schedule.php" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
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
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                          $startFormatted = date("l, F j, Y g:i A", strtotime($row['start_date_time']));
                          $endFormatted   = date("l, F j, Y g:i A", strtotime($row['end_date_time']));
                    ?>
                        <tr class="table-success">
                            <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
                            <td><?php echo $startFormatted; ?></td>
                            <td><?php echo $endFormatted; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No result can be found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Optional: Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
