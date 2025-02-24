<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $clientName    = trim($_POST['client_name']);
    $contactNumber = trim($_POST['contact_number']); // treat as string if necessary
    $location      = trim($_POST['location']);
    $eventName     = trim($_POST['event_name']);
    $startDT       = $_POST['start_date_time'];
    $endDT         = $_POST['end_date_time'];
    $statusInput   = trim($_POST['status']); // "confirm" or "cancel"
    
    $conn->begin_transaction();
    try {
        // Look up ClientID by client name using store_result()
        $stmt = $conn->prepare("SELECT ClientID FROM Clients WHERE clients_name = ? LIMIT 1");
        $stmt->bind_param("s", $clientName);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt->close();
            // Client not found: insert new client (using ID=1 for default user)
            $stmt = $conn->prepare("INSERT INTO Clients (clients_name, contact_number, location, ID) VALUES (?, ?, ?, ?)");
            $defaultUserID = 1;
            $stmt->bind_param("sssi", $clientName, $contactNumber, $location, $defaultUserID);
            $stmt->execute();
            $clientID = $stmt->insert_id;
            $stmt->close();
        } else {
            $stmt->bind_result($clientID);
            $stmt->fetch();
            $stmt->close();
        }
        
        // Insert event record
        $stmt = $conn->prepare("INSERT INTO Events (Events_name, ClientID) VALUES (?, ?)");
        $stmt->bind_param("si", $eventName, $clientID);
        $stmt->execute();
        $eventID = $stmt->insert_id;
        $stmt->close();
        
        // Look up StatusID from Updated_Status using store_result()
        $stmt = $conn->prepare("SELECT StatusID FROM Updated_Status WHERE updated_status = ? LIMIT 1");
        $stmt->bind_param("s", $statusInput);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt->close();
            // Status not found: insert the new status into Updated_Status
            $stmt = $conn->prepare("INSERT INTO Updated_Status (updated_status) VALUES (?)");
            $stmt->bind_param("s", $statusInput);
            $stmt->execute();
            $statusID = $stmt->insert_id;
            $stmt->close();
        } else {
            $stmt->bind_result($statusID);
            $stmt->fetch();
            $stmt->close();
        }
        
        // Insert into Schedule with chosen StatusID
        $stmt = $conn->prepare("INSERT INTO Schedule (start_date_time, end_date_time, EventID, StatusID) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $startDT, $endDT, $eventID, $statusID);
        $stmt->execute();
        $scheduleID = $stmt->insert_id; // capture the new ScheduleID
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Query the joined details for the inserted schedule
        $stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, us.updated_status 
        FROM Schedule s 
        JOIN Events e ON s.EventID = e.EventID 
        JOIN Clients c ON e.ClientID = c.ClientID 
        JOIN Updated_Status us ON s.StatusID = us.StatusID 
        WHERE s.ScheduleID = ?");
        $stmt->bind_param("i", $scheduleID);
        $stmt->execute();
        $result_join = $stmt->get_result();
        $joined = $result_join->fetch_assoc();
        $stmt->close();
        
        // Show alert with joined details then redirect
        echo "<script>
              alert('Schedule added successfully: ID: " . htmlspecialchars($joined['ScheduleID']) . ", Event: " . htmlspecialchars($joined['Events_name']) . ", Client: " . htmlspecialchars($joined['clients_name']) . ", Status: " . htmlspecialchars($joined['updated_status']) . "');
              window.location.href='calendar.php';
              </script>";
    } catch(Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Schedule</title>
    <link rel="stylesheet" href="calendar.css">
    <!-- Optional: add Bootstrap if needed -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Insert New Schedule</h2>
    <form method="post" action="">
        <div class="form-group">
            <label for="client_name">Name of Client:</label>
            <input type="text" name="client_name" id="client_name" class="form-control" required>
        </div>
        <!-- New fields for client contact information -->
        <div class="form-group">
            <label for="contact_number">Contact Number:</label>
            <input type="text" name="contact_number" id="contact_number" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="location">Location:</label>
            <input type="text" name="location" id="location" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="event_name">Event Name:</label>
            <input type="text" name="event_name" id="event_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="start_date_time">Start Date/Time:</label>
            <input type="datetime-local" name="start_date_time" id="start_date_time" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="end_date_time">End Date/Time:</label>
            <input type="datetime-local" name="end_date_time" id="end_date_time" class="form-control" required>
        </div>
        <!-- New status option -->
        <div class="form-group">
            <label>Status:</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status_confirm" value="confirm" required>
                <label class="form-check-label" for="status_confirm">Confirm</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="status_cancel" value="cancel" required>
                <label class="form-check-label" for="status_cancel">Cancel</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
</body>
</html>
