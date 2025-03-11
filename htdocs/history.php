<?php
session_start();
include 'db.php';
include 'navigation.php';

// Section 1: Clients Booked Schedules with updated status
// Assumed schema details:
// - Clients: ClientID, clients_name, contact_number, location, ...
// - Events: EventID, Events_name, ClientID, ...
// - Schedule: ScheduleID, start_date_time, end_date_time, EventID, StatusID, ...
// - Updated_Status: StatusID, updated_status, ...
$querySchedules = "SELECT c.ClientID, c.clients_name, c.contact_number, c.location, 
                          s.ScheduleID, s.start_date_time, s.end_date_time, 
                          e.Events_name, us.updated_status 
                   FROM Clients c
                   JOIN Events e ON c.ClientID = e.ClientID
                   JOIN Schedule s ON e.EventID = s.EventID
                   JOIN Updated_Status us ON s.StatusID = us.StatusID
                   ORDER BY s.start_date_time DESC";
$resultSchedules = $conn->query($querySchedules);

// Removed history details query
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Full History</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- ...existing head code... -->
</head>
<body>
    <div class="container mt-5">
        <h2>Clients Previous Schedules</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Client ID</th>
                    <th>Client Name</th>
                    <th>Contact Number</th>
                    <th>Location</th>
                    <th>Schedule ID</th>
                    <th>Event</th>
                    <th>Start Date/Time</th>
                    <th>End Date/Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $resultSchedules->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ClientID']); ?></td>
                    <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo htmlspecialchars($row['ScheduleID']); ?></td>
                    <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
                    <td><?php echo date("F j, Y, g:i a", strtotime($row['start_date_time'])); ?></td>
                    <td><?php echo date("F j, Y, g:i a", strtotime($row['end_date_time'])); ?></td>
                    <td><?php echo (strtolower($row['updated_status']) == 'cancelled' ? 'Cancelled' : 'Completed'); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Removed History Details table -->

        <a href="calendar.php" class="btn btn-secondary">Back to Calendar</a>
    </div>
    <!-- ...existing footer code... -->
</body>
</html>
