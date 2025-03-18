<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'navigation.php';

$scheduleID = isset($_GET['ScheduleID']) ? (int)$_GET['ScheduleID'] : 0;
if ($scheduleID === 0) {
    die("Invalid Schedule ID");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDateTime = $_POST['start_date_time'];
    $endDateTime = $_POST['end_date_time'];
    $eventID = $_POST['eventID'];
    $statusID = $_POST['statusID'] === 'cancel' ? null : $_POST['statusID'];
    $clientName = $_POST['client_name'];
    $contactNumber = $_POST['contact_number'];
    $location = $_POST['location'];

    // Validate inputs
    if (empty($startDateTime) || empty($endDateTime) || empty($eventID) || empty($clientName) || empty($contactNumber) || empty($location)) {
        $error = "All fields are required.";
    } elseif (strtotime($startDateTime) >= strtotime($endDateTime)) {
        $error = "Start time must be before end time.";
    } else {
        // Update schedule in the database
        $stmt = $conn->prepare("UPDATE Schedule s
                                JOIN Events e ON s.EventID = e.EventID
                                JOIN Clients c ON e.ClientID = c.ClientID
                                SET s.start_date_time = ?, s.end_date_time = ?, s.EventID = ?, s.StatusID = ?, c.clients_name = ?, c.contact_number = ?, c.location = ?
                                WHERE s.ScheduleID = ?");
        $stmt->bind_param("ssiiissi", $startDateTime, $endDateTime, $eventID, $statusID, $clientName, $contactNumber, $location, $scheduleID);
        if ($stmt->execute()) {
            $success = "Schedule updated successfully.";
            // Fetch updated schedule details
            $stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.EventID, s.StatusID, e.Events_name, us.updated_status, c.clients_name, c.contact_number, c.location
                                    FROM Schedule s
                                    JOIN Events e ON s.EventID = e.EventID
                                    JOIN Updated_Status us ON s.StatusID = us.StatusID
                                    JOIN Clients c ON e.ClientID = c.ClientID
                                    WHERE s.ScheduleID = ?");
            $stmt->bind_param("i", $scheduleID);
            $stmt->execute();
            $schedule = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Failed to update schedule.";
        }
        $stmt->close();
    }
}

// Fetch schedule details
$stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.EventID, s.StatusID, e.Events_name, us.updated_status, c.clients_name, c.contact_number, c.location
                        FROM Schedule s
                        JOIN Events e ON s.EventID = e.EventID
                        JOIN Updated_Status us ON s.StatusID = us.StatusID
                        JOIN Clients c ON e.ClientID = c.ClientID
                        WHERE s.ScheduleID = ?");
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch events and statuses for the form
$events = $conn->query("SELECT EventID, Events_name FROM Events")->fetch_all(MYSQLI_ASSOC);
$statuses = $conn->query("SELECT StatusID, updated_status FROM Updated_Status")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(243, 237, 237);
            color: #ffffff;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #333;
            padding: 20px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #555;
            border-radius: 4px;
            background: #222;
            color: #fff;
        }
        .form-group button {
            padding: 10px 15px;
            background-color: #28a745;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #218838;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .success {
            color: #28a745;
            margin-bottom: 15px;
        }
        .schedule-details {
            margin-top: 20px;
            background: #444;
            padding: 15px;
            border-radius: 8px;
        }
        .schedule-details h2 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Schedule</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="schedule-details">
                <h2>Updated Schedule Details</h2>
                <p><strong>Client Name:</strong> <?php echo htmlspecialchars($schedule['clients_name']); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($schedule['contact_number']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($schedule['location']); ?></p>
                <p><strong>Start Date & Time:</strong> <?php echo date('Y-m-d H:i', strtotime($schedule['start_date_time'])); ?></p>
                <p><strong>End Date & Time:</strong> <?php echo date('Y-m-d H:i', strtotime($schedule['end_date_time'])); ?></p>
                <p><strong>Event:</strong> <?php echo htmlspecialchars($schedule['Events_name']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($schedule['updated_status']); ?></p>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="client_name">Client Name</label>
                <input type="text" name="client_name" id="client_name" value="<?php echo htmlspecialchars($schedule['clients_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($schedule['contact_number']); ?>" required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($schedule['location']); ?>" required>
            </div>
            <div class="form-group">
                <label for="start_date_time">Start Date & Time</label>
                <input type="datetime-local" name="start_date_time" id="start_date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_date_time'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date_time">End Date & Time</label>
                <input type="datetime-local" name="end_date_time" id="end_date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_date_time'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="eventID">Event</label>
                <select name="eventID" id="eventID" required>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['EventID']; ?>" <?php echo $event['EventID'] == $schedule['EventID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($event['Events_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="statusID">Status</label>
                <select name="statusID" id="statusID" required>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['StatusID']; ?>" <?php echo $status['StatusID'] == $schedule['StatusID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($status['updated_status']); ?></option>
                    <?php endforeach; ?>
                    <option value="cancel">Cancel</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit">Update Schedule</button>
            </div>
        </form>
    </div>
</body>
</html>
