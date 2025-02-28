<?php
session_start();
include 'db.php';

$errorMsg = '';

// New view mode: show schedule details for a given date with a close button
if (isset($_GET['viewSchedules']) && $_GET['viewSchedules'] == 1 && isset($_GET['date'])) {
    $date = $_GET['date'];
    $stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, us.updated_status 
                            FROM Schedule s 
                            JOIN Events e ON s.EventID = e.EventID 
                            JOIN Clients c ON e.ClientID = c.ClientID 
                            JOIN Updated_Status us ON s.StatusID = us.StatusID 
                            WHERE DATE(s.start_date_time) = ? 
                            ORDER BY s.start_date_time ASC");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<h2>Schedule Details for " . htmlspecialchars($date) . "</h2>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr>
             <th>Schedule ID</th>
             <th>Event</th>
             <th>Client</th>
             <th>Status</th>
             <th>Start Date/Time</th>
             <th>End Date/Time</th>
          </tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['ScheduleID']) . "</td>
                <td>" . htmlspecialchars($row['Events_name']) . "</td>
                <td>" . htmlspecialchars($row['clients_name']) . "</td>
                <td>" . htmlspecialchars($row['updated_status']) . "</td>
                <td>" . htmlspecialchars($row['start_date_time']) . "</td>
                <td>" . htmlspecialchars($row['end_date_time']) . "</td>
              </tr>";
    }
    echo "</tbody></table>";
    // Add a close button, styled with Bootstrap and custom CSS if needed
    echo "<div class='text-center mt-3'>";
    echo "<button type='button' class='btn btn-secondary btn-close' onclick=\"window.location.href='calendar.php';\">Close</button>";
    echo "</div>";
    exit();
}

$accountLevel = $_SESSION['account_level'] ?? 'user'; // added account level check

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $clientName    = trim($_POST['client_name']);
    $contactNumber = trim($_POST['contact_number']);
    $location      = trim($_POST['location']);
    $eventName     = trim($_POST['event_name']);
    $startDT       = $_POST['start_date_time'];
    $endDT         = $_POST['end_date_time'];
    $statusInput   = trim($_POST['status']); // "confirm" or "cancel"

    // If not admin force pending request
    if ($accountLevel !== 'admin') {
        $statusInput = 'pending';
    }

    $conn->begin_transaction();
    try {
        // Validate that the end datetime is after the start datetime
        if (strtotime($startDT) >= strtotime($endDT)) {
            throw new Exception("Error: End date/time must be after the start date/time.");
        }

        // Check if a schedule already exists with the same start and end datetime
        $stmtConflict = $conn->prepare("SELECT ScheduleID FROM Schedule WHERE start_date_time = ? AND end_date_time = ?");
        $stmtConflict->bind_param("ss", $startDT, $endDT);
        $stmtConflict->execute();
        $stmtConflict->store_result();
        if ($stmtConflict->num_rows > 0) {
            $stmtConflict->close();
            throw new Exception("Error: The selected schedule slot is already taken. Please choose a different date/time.");
        }
        $stmtConflict->close();
        
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
        $scheduleID = $stmt->insert_id;
        $stmt->close();
        
        // Commit transaction
        $conn->commit();

        // Check if the schedule status is pending
        if ($statusInput === 'pending') {
            echo "<script>
                  alert('Schedule request submitted successfully. Please wait for admin approval.');
                  window.location.href='calendar.php';
                  </script>";
            exit();
        } else {
            // Query the joined details for the inserted schedule (for non-pending status)
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
            
            echo "<script>
                  alert('Schedule added successfully: ID: " . htmlspecialchars($joined['ScheduleID']) . ", Event: " . htmlspecialchars($joined['Events_name']) . ", Client: " . htmlspecialchars($joined['clients_name']) . ", Status: " . htmlspecialchars($joined['updated_status']) . "');
                  window.location.href='calendar.php';
                  </script>";
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $errorMsg = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Schedule</title>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Bootstrap CSS (optional for better alert styling) -->
    <style>
        /* General Page Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #d9ede4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 50%;
            max-width: 600px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        /* Alert Styling */
        .alert {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        /* Form Styling */
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        /* Button Styling */
        .btn-primary {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        /* Enhanced Radio Button Group Styling */
        .radio-group {
            display: flex;
            gap: 10px;
        }
        .radio-group input[type="radio"] {
            display: none;
        }
        .radio-group label {
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .radio-group input[type="radio"]:checked + label {
            background-color: #4caf50;
            border-color: #4caf50;
            color: white;
        }
        /* Close Button */
        .btn-close {
            background-color: #bbb;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-close:hover {
            background-color: #999;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Insert New Schedule</h2>
    <?php if (!empty($errorMsg)) { ?>
        <div class="alert"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php } ?>
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
            <!-- Changed type to text for Flatpickr -->
            <input type="text" name="start_date_time" id="start_date_time" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="end_date_time">End Date/Time:</label>
            <!-- Changed type to text for Flatpickr -->
            <input type="text" name="end_date_time" id="end_date_time" class="form-control" required>
        </div>
        <!-- Enhanced status radio buttons for admin -->
        <?php if ($accountLevel === 'admin') { ?>
            <div class="form-group">
                <label>Status:</label>
                <div class="radio-group">
                    <input type="radio" name="status" id="status_confirm" value="confirm" required>
                    <label for="status_confirm">Confirm</label>
                    <input type="radio" name="status" id="status_cancel" value="cancel" required>
                    <label for="status_cancel">Cancel</label>
                </div>
            </div>
        <?php } else { ?>
            <input type="hidden" name="status" value="pending" />
            <p>Status: Pending request (awaiting admin approval)</p>
        <?php } ?>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    const startPicker = flatpickr("#start_date_time", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        onChange: function(selectedDates) {
            if (selectedDates.length) {
                endPicker.set("minDate", selectedDates[0]);
            }
        }
    });
    const endPicker = flatpickr("#end_date_time", {
        enableTime: true,
        dateFormat: "Y-m-d H:i"
    });
</script>
</body>
</html>
