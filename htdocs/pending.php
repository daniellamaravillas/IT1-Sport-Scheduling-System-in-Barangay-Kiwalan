<?php
session_start();
include 'db.php';

$accountLevel = $_SESSION['account_level'] ?? 'user';
$isAdmin = ($accountLevel === 'admin');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if($isAdmin) {
    // Admin: fetch pending requests
    $pendingSql = "SELECT s.ScheduleID, e.Events_name as Event_name, c.clients_name, c.location, c.contact_number, s.start_date_time, s.end_date_time 
                   FROM Schedule s
                   JOIN Events e ON s.EventID = e.EventID
                   JOIN Clients c ON e.ClientID = c.ClientID
                   JOIN Updated_Status us ON s.StatusID = us.StatusID
                   WHERE us.updated_status = 'pending'
                   ORDER BY s.ScheduleID DESC";
    $pendingResult = $conn->query($pendingSql);
} else {
    // User: check for declined notifications.
    if(isset($_SESSION['user_id'])) {
        $declinedStmt = $conn->prepare("SELECT s.ScheduleID FROM Schedule s JOIN Updated_Status us ON s.StatusID = us.StatusID WHERE s.user_id = ? AND us.updated_status = 'declined' LIMIT 1");
        $declinedStmt->bind_param("i", $_SESSION['user_id']);
        $declinedStmt->execute();
        $declinedStmt->store_result();
        if($declinedStmt->num_rows > 0) {
            $_SESSION['alert_message_declined'] = "One or more of your schedule requests have been declined by the admin.";
        }
        $declinedStmt->close();

        // Check for accepted notifications.
        $acceptedStmt = $conn->prepare("SELECT s.ScheduleID FROM Schedule s JOIN Updated_Status us ON s.StatusID = us.StatusID WHERE s.user_id = ? AND us.updated_status = 'accepted' LIMIT 1");
        $acceptedStmt->bind_param("i", $_SESSION['user_id']);
        $acceptedStmt->execute();
        $acceptedStmt->store_result();
        if($acceptedStmt->num_rows > 0) {
            $_SESSION['alert_message_accepted'] = "Your schedule request has been accepted by the admin.";
        }
        $acceptedStmt->close();

        // Check for unavailable schedule notifications.
        $unavailableStmt = $conn->prepare("SELECT s.ScheduleID FROM Schedule s JOIN Updated_Status us ON s.StatusID = us.StatusID WHERE s.user_id = ? AND us.updated_status = 'unavailable' LIMIT 1");
        $unavailableStmt->bind_param("i", $_SESSION['user_id']);
        $unavailableStmt->execute();
        $unavailableStmt->store_result();
        if($unavailableStmt->num_rows > 0) {
            $_SESSION['alert_message_unavailable'] = "Your schedule request is currently unavailable.";
        }
        $unavailableStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdmin ? 'Pending Requests' : 'Notifications'; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="calendar.css">
    <link rel="stylesheet" href="custom_bootstrap.css">
</head>
<body>
<div class="container mt-4">
<?php if($isAdmin){ ?>
    <h3>Pending Requests</h3>
    <?php if($pendingResult->num_rows > 0){ ?>
        <div class="alert alert-info" role="alert">
            You have new pending requests.
        </div>
    <?php } ?>
    <!-- Pending requests table -->
    <?php if($pendingResult->num_rows > 0) { ?>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Event</th>
                    <th>Location</th>
                    <th>Contact Number</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $first = true; 
            while($row = $pendingResult->fetch_assoc()) { 
            ?>
                <tr <?php if($first){ echo 'class="table-warning"'; $first = false; } ?>>
                    <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Event_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>    
                    <td><?php echo htmlspecialchars($row['start_date_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_date_time']); ?></td>
                    <td>
                        <a href="accept_request.php?id=<?php echo urlencode($row['ScheduleID']); ?>" class="btn btn-sm btn-success">Accept</a>
                        <a href="decline_request.php?id=<?php echo urlencode($row['ScheduleID']); ?>" class="btn btn-sm btn-danger">Decline</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p class="mt-4">No pending requests.</p>
    <?php } ?>
<?php } else { ?>
    <h3>Notifications</h3>
    <?php if(isset($_SESSION['alert_message_accepted'])) { ?>
        <div class="alert alert-success" role="alert">
            <?php echo $_SESSION['alert_message_accepted']; unset($_SESSION['alert_message_accepted']); ?>
        </div>
    <?php } ?>
    <?php if(isset($_SESSION['alert_message_declined'])) { ?>
        <div class="alert alert-warning" role="alert">
            <?php echo $_SESSION['alert_message_declined']; unset($_SESSION['alert_message_declined']); ?>
        </div>
    <?php } ?>
    <?php if(isset($_SESSION['alert_message_unavailable'])) { ?>
        <div class="alert alert-secondary" role="alert">
            <?php echo $_SESSION['alert_message_unavailable']; unset($_SESSION['alert_message_unavailable']); ?>
        </div>
    <?php } ?>
    <?php if(!isset($_SESSION['alert_message_accepted']) && !isset($_SESSION['alert_message_declined']) && !isset($_SESSION['alert_message_unavailable'])) { ?>
        <p class="mt-4">No notifications.</p>
    <?php } ?>
<?php } ?>
</div>
</body>
</html>
