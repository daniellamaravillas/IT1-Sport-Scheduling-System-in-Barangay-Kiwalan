<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}

include 'db.php';

$date = isset($_GET['date']) ? $_GET['date'] : '';
if (!$date) {
    echo "<div class='alert alert-danger'>Invalid date.</div>";
    exit();
}

// Check if the schedule time is full
$timeCheckSql = "SELECT COUNT(*) as count FROM Schedule WHERE DATE(start_date_time) = '$date' AND 
                (TIME(start_date_time) BETWEEN '08:00:00' AND '19:00:00' OR TIME(end_date_time) BETWEEN '08:00:00' AND '19:00:00')";
$timeCheckResult = $conn->query($timeCheckSql);
$timeCheckRow = $timeCheckResult->fetch_assoc();
$isFull = $timeCheckRow['count'] >= 11; // Assuming 11 slots from 8 AM to 7 PM

// Updated query to join the Status and select its value
$sql = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.date_schedule, e.Events_name, c.clients_name, c.contact_number, c.location, us.updated_status
        FROM Schedule s
        JOIN Events e ON s.EventID = e.EventID
        JOIN Clients c ON e.ClientID = c.ClientID
        JOIN Updated_Status us ON s.StatusID = us.StatusID
        WHERE DATE(s.start_date_time) = ?
        ORDER BY s.start_date_time ASC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0):
?>
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
                <th>Date Schedule</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $highlighted = []; // Track first schedule per client
            while($row = $result->fetch_assoc()): 
                $startFormatted = date("l, F j, Y g:i A", strtotime($row['start_date_time']));
                $endFormatted   = date("l, F j, Y g:i A", strtotime($row['end_date_time']));
                $dateScheduleFormatted = date("l, F j, Y", strtotime($row['date_schedule']));
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
                    <td><?php echo $dateScheduleFormatted; ?></td>
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
                        <button type="button" class="btn btn-sm btn-success btn-edit" data-id="<?php echo $row['ScheduleID']; ?>">
                            Edit
                        </button>
                        &nbsp;
                        <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $row['ScheduleID']; ?>">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="alert alert-info">No schedule for this day</div>
<?php endif; ?>

<?php
// Add a button to create a new schedule for this date
echo '<div class="text-center mt-3">';
echo '<a href="create_schedule.php?date=' . htmlspecialchars($date) . '" class="btn btn-primary">Create New Schedule</a>';
echo '</div>';

$stmt->close();
?>