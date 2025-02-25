<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'db.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$stmt = $conn->prepare("
    SELECT s.ScheduleID, s.start_date_time, s.end_date_time, 
           e.Events_name, c.clients_name, c.contact_number, c.location, us.updated_status
    FROM Schedule s
    JOIN Events e ON s.EventID = e.EventID 
    JOIN Clients c ON e.ClientID = c.ClientID 
    JOIN Updated_Status us ON s.StatusID = us.StatusID
    WHERE DATE(s.start_date_time) = ?
    ORDER BY s.start_date_time ASC
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="modal-header">
  <h5 class="modal-title">View Schedule for <?php echo htmlspecialchars(date('F j, Y', strtotime($date))); ?></h5>
  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
<div class="modal-body">
    <table class="table table-bordered">
        <thead>
            <tr>
              <th>Client</th>
              <th>Contact Number</th>
              <th>Location</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
              <th>Event</th>
              <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
              <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
              <td><?php echo htmlspecialchars($row['location']); ?></td>
              <td><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($row['start_date_time']))); ?></td>
              <td><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($row['end_date_time']))); ?></td>
              <td><?php echo htmlspecialchars($row['updated_status']); ?></td>
              <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
              <td>
                <a href='edit_schedule.php?ScheduleID=<?php echo $row['ScheduleID']; ?>' class='btn btn-sm btn-primary'>Edit</a>
                <a href='delete_schedule.php?ScheduleID=<?php echo $row['ScheduleID']; ?>' class='btn btn-sm btn-danger'>Delete</a>
              </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
<?php
$stmt->close();
?>
