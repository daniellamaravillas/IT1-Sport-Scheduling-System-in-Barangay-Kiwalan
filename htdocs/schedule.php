<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'navigation.php';
// ...existing code...
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="home.css">
    
</head>
<body>
<?php
// ...existing code...
?>
<div class="button-container">
    <a href="schedule.php"><button>List Of Schedule</button></a>
    <a href="client.php"><button>Clients List</button></a>
</div>
    <h2>Schedule List</h2>
<?php
// ...existing code...

// Database connection
$servername = "127.0.0.1";
$username = "mariadb";
$password = "mariadb";
$dbname = "mariadb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch schedule data with INNER JOIN
$sql = "SELECT c.clients_name, e.events_name, 
               DATE_FORMAT(s.start_date_time, '%M %d, %Y %H:%i:%s') AS start_date_time, 
               DATE_FORMAT(s.end_date_time, '%M %d, %Y %H:%i:%s') AS end_date_time, 
               us.updated_status, s.ScheduleID 
        FROM Schedule AS s
        INNER JOIN Events AS e ON s.EventID = e.EventID
        INNER JOIN Updated_Status AS us ON s.StatusID = us.StatusID
        INNER JOIN Clients AS c ON s.ClientID = c.ClientID";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'>
            <tr>
                <th>Client</th>
                <th>Event Name</th>
                <th>Start Date Time</th>
                <th>End Date Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["clients_name"] . "</td>
                <td>" . $row["events_name"] . "</td>
                <td>" . $row["start_date_time"] . "</td>
                <td>" . $row["end_date_time"] . "</td>
                <td>" . $row["updated_status"] . "</td>
                <td>
                    <button class='btn-edit' onclick='editSchedule(" . $row["ScheduleID"] . ")'>Edit</button>
                    <button class='btn-delete' onclick='deleteSchedule(" . $row["ScheduleID"] . ")'>Delete</button>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}

$conn->close();
?>
<script>
function editSchedule(scheduleID) {
    window.location.href = 'edit_schedule.php?id=' + scheduleID;
}

function deleteSchedule(scheduleID) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        window.location.href = 'deleted_schedule.php?id=' + scheduleID;
    }
}
</script>
</body>
</html>