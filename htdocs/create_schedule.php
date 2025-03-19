<?php
session_start();
include 'db.php';
include 'navigation.php';
$errorMsg = '';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// New view mode: show schedule details for a given date with a close button
if (isset($_GET['viewSchedules']) && $_GET['viewSchedules'] == 1 && isset($_GET['date'])) {
    $date = $_GET['date'];
    $stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.date_schedule, e.Events_name, c.clients_name, us.updated_status 
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
             <th>Date Schedule</th>
          </tr></thead><tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['ScheduleID']) . "</td>
                <td>" . htmlspecialchars($row['Events_name']) . "</td>
                <td>" . htmlspecialchars($row['clients_name']) . "</td>
                <td>" . htmlspecialchars($row['updated_status']) . "</td>
                <td>" . htmlspecialchars($row['start_date_time']) . "</td>
                <td>" . htmlspecialchars($row['end_date_time']) . "</td>
                <td>" . htmlspecialchars($row['date_schedule']) . "</td>
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
    $dateSchedule  = $_POST['date_schedule'];
    
    // Extract the date part from start_date_time for redirect
    $scheduleDate = date('Y-m-d', strtotime($startDT));

    // If not admin force pending request
    if ($accountLevel !== 'admin') {
        $statusInput = 'pending';
    }

    // Validate that the end datetime is after the start datetime
    if (strtotime($startDT) >= strtotime($endDT)) {
        $errorMsg = "End date/time must be after the start date/time.";
    } else {
        $conn->begin_transaction();
        try {
            // Check for any overlapping schedules - IMPROVED ALGORITHM
            $stmtOverlap = $conn->prepare(
                "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name 
                FROM Schedule s
                JOIN Events e ON s.EventID = e.EventID
                WHERE DATE(s.date_schedule) = DATE(?) 
                AND (
                    (? BETWEEN s.start_date_time AND s.end_date_time) OR 
                    (? BETWEEN s.start_date_time AND s.end_date_time) OR
                    (s.start_date_time BETWEEN ? AND ?) OR
                    (s.end_date_time BETWEEN ? AND ?)
                )"
            );
            $stmtOverlap->bind_param("sssssss", 
                $dateSchedule, 
                $startDT, $endDT,  // Check if new start or end time is within existing schedule
                $startDT, $endDT,  // Check if existing start time is within new schedule
                $startDT, $endDT   // Check if existing end time is within new schedule
            );
            $stmtOverlap->execute();
            $overlapResult = $stmtOverlap->get_result();
            
            if ($overlapResult->num_rows > 0) {
                // Get details about the conflicting schedule for a more helpful error message
                $conflictRow = $overlapResult->fetch_assoc();
                $conflictStart = date('h:i A', strtotime($conflictRow['start_date_time']));
                $conflictEnd = date('h:i A', strtotime($conflictRow['end_date_time']));
                
                throw new Exception(
                    "Schedule conflict detected: Your requested time overlaps with an existing event '" . 
                    htmlspecialchars($conflictRow['Events_name']) . "' scheduled from $conflictStart to $conflictEnd. " .
                    "Please select a different time slot."
                );
            }
            $stmtOverlap->close();
            
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
            $stmt = $conn->prepare("INSERT INTO Schedule (start_date_time, end_date_time, EventID, StatusID, date_schedule) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $startDT, $endDT, $eventID, $statusID, $dateSchedule);
            $stmt->execute();
            $scheduleID = $stmt->insert_id;
            $stmt->close();
            
            // Commit transaction
            $conn->commit();

            // Check if the schedule status is pending
            if ($statusInput === 'pending') {
                echo "<script>
                      alert('Schedule request submitted successfully. Please wait for admin approval.');
                      window.location.href='schedule_detail.php?date=" . $scheduleDate . "';
                      </script>";
                exit();
            } else {
                // Query the joined details for the inserted schedule (for non-pending status)
                $stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.date_schedule, e.Events_name, c.clients_name, us.updated_status 
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
                      window.location.href='schedule_detail.php?date=" . $scheduleDate . "';
                      </script>";
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errorMsg = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1E293B;
            --accent-color: #2779E2;
            --light-bg: #f5f7fa;
            --dark-text: #333;
            --light-text: #fff;
            --error-color: #dc3545;
            --success-color: #28a745;
            --border-radius: 8px;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        /* General Page Styling */
        body {
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 2rem;
        }

        .container {
            background: var(--light-text);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            transition: var(--transition);
        }
        
        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        /* Header styles */
        .form-header {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--accent-color);
        }

        h2 {
            color: var(--primary-color);
            font-weight: 600;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6c757d;
            text-align: center;
            font-size: 1rem;
            margin-bottom: 0;
        }

        /* Alert Styling */
        .alert {
            color: var(--dark-text);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease;
            border-left: 4px solid var(--error-color);
            background-color: rgba(220, 53, 69, 0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(39, 121, 226, 0.25);
            outline: none;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }

        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }

        /* Button Styling */
        .btn-primary {
            background-color: var(--accent-color);
            color: var(--light-text);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #1b69c9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(27, 105, 201, 0.3);
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
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            margin-bottom: 0;
        }

        .radio-group input[type="radio"]:checked + label {
            background-color: var(--accent-color);
            border-color: var (--accent-color);
            color: var(--light-text);
        }

        /* Flatpickr Customization */
        .flatpickr-calendar {
            width: 100%;
            max-width: 320px;
            font-size: 14px;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
        }

        .flatpickr-day.today {
            background-color: rgba(39, 121, 226, 0.2);
            border-color: rgba(39, 121, 226, 0.2);
        }

        .flatpickr-day.selected {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        /* Background Logo */
        .bg-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            width: 70%;
            height: auto;
            z-index: 0;
        }

        /* Date Highlighting */
        .today-highlight {
            background-color: var(--accent-color) !important;
            color: var(--light-text) !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
                margin: 0 15px;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                margin-bottom: 1rem;
            }
            
            .flatpickr-calendar {
                max-width: 280px;
            }
            
            .radio-group {
                flex-direction: column;
            }
        }

        /* Add styles for the calendar view */
        .calendar-container {
            height: 400px;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .fc-event {
            cursor: pointer;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.85em;
        }

        .fc-event.busy {
            background-color: rgba(220, 53, 69, 0.8);
            border-color: #dc3545;
        }

        .fc-event.available {
            background-color: rgba(40, 167, 69, 0.8);
            border-color: #28a745;
        }

        .fc-event.pending {
            background-color: rgba(255, 193, 7, 0.8);
            border-color: #ffc107;
        }

        /* Conflict resolution modal */
        .conflict-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .conflict-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow);
        }

        .conflict-modal h3 {
            color: var(--error-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .conflict-modal h3 i {
            margin-right: 0.5rem;
        }

        .suggested-times {
            margin: 1.5rem 0;
            max-height: 200px;
            overflow-y: auto;
        }

        .time-suggestion {
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .time-suggestion:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .time-suggestion.selected {
            background-color: rgba(39, 121, 226, 0.1);
            border-color: var(--accent-color);
        }

        .conflict-modal-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        /* Confirmation modal */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .confirm-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: var (--border-radius);
            max-width: 500px;
            width: 90%;
            box-shadow: var (--shadow);
        }

        .confirm-modal h3 {
            color: var(--accent-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .confirm-modal h3 i {
            margin-right: 0.5rem;
        }

        .schedule-details {
            margin: 1.5rem 0;
            padding: 15px;
            background-color: rgba(39, 121, 226, 0.05);
            border-radius: var(--border-radius);
        }

        .schedule-details p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .schedule-details i {
            width: 20px;
            margin-right: 10px;
            color: var(--accent-color);
        }

        .confirm-modal-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        /* Visual feedback for available times */
        .time-availability {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.5rem;
        }

        .time-slot-indicator {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .indicator {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .indicator.busy {
            background-color: #dc3545;
        }

        .indicator.available {
            background-color: #28a745;
        }

        .indicator.pending {
            background-color: #ffc107;
        }

        /* Time suggestions styling */
        .time-suggestion i {
            color: var(--accent-color);
        }

        /* Tabs for form/calendar view */
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .nav-tabs .nav-link:hover {
            color: var(--accent-color);
        }

        .nav-tabs .nav-link.active {
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
        }

        .tab-content {
            padding-top: 1rem;
        }

        /* Additional responsive adjustments */
        @media (max-width: 576px) {
            .calendar-container {
                height: 350px;
            }
            
            .conflict-modal-content,
            .confirm-modal-content {
                padding: 1.25rem;
            }
            
            .time-suggestion {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .time-suggestion .btn-sm {
                margin-top: 8px;
            }
        }

        /* Add these styles to your existing CSS in create_schedule.php */
        .fc-day-has-confirmed {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .fc-day-has-pending {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .fc-day-available {
            background-color: rgba(40, 167, 69, 0.05);
        }

        .event-indicator {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .event-indicator.confirmed {
            background-color: #dc3545;
        }

        .event-indicator.pending {
            background-color: #ffc107;
        }

        .event-tooltip {
            font-size: 12px;
            line-height: 1.4;
            text-align: left;
        }

        /* Make the calendar more responsive */
        @media (max-width: 768px) {
            .fc-header-toolbar {
                flex-direction: column;
                align-items: center;
            }
            .fc-toolbar-chunk {
                margin-bottom: 10px;
            }
        }

        /* Enhanced time suggestion styling */
        .time-suggestion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }

        .time-suggestion:hover {
            background-color: rgba(39, 121, 226, 0.05);
            transform: translateX(5px);
            border-color: var(--accent-color);
        }

        .time-suggestion.selected {
            background-color: rgba(39, 121, 226, 0.1);
            border-color: var(--accent-color);
            box-shadow: 0 2px 5px rgba(39, 121, 226, 0.2);
        }
    </style>
</head>

<body>
    <div class="main-content with-mini-sidebar">
        <div class="container mt-4">
            <?php if (!empty($errorMsg)) { ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php } ?>
            
            <div class="form-header">
                <h2><i class="fas fa-calendar-plus mr-2"></i>Create New Schedule</h2>
                <p class="subtitle">Book a time slot for your sports event</p>
            </div>
            
            <!-- Tabs for switching between form and calendar view -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="form-tab" data-toggle="tab" href="#form-view" role="tab" aria-controls="form-view" aria-selected="true">
                        <i class="fas fa-edit mr-2"></i>Schedule Form
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="calendar-tab" data-toggle="tab" href="#calendar-view" role="tab" aria-controls="calendar-view" aria-selected="false">
                        <i class="fas fa-calendar-alt mr-2"></i>Availability Calendar
                    </a>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Form View Tab -->
                <div class="tab-pane fade show active" id="form-view" role="tabpanel" aria-labelledby="form-tab">
                    <div style="position: relative;">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgJbMfeLLbE_Wh3cK3RK8s0a-P9hvTwYfHpw&s" alt="Logo" class="bg-logo">
                        
                        <!-- Time availability legend -->
                        <div class="time-availability">
                            <h6 class="mb-2">Time Slot Availability:</h6>
                            <div class="time-slot-indicator">
                                <div class="indicator available"></div>
                                <span>Available</span>
                            </div>
                            <div class="time-slot-indicator">
                                <div class="indicator busy"></div>
                                <span>Booked</span>
                            </div>
                            <div class="time-slot-indicator">
                                <div class="indicator pending"></div>
                                <span>Pending Approval</span>
                            </div>
                        </div>
                        
                        <form method="post" action="" style="position: relative; z-index: 1;" id="scheduleForm">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="client_name"><i class="fas fa-user mr-2"></i>Name of Client:</label>
                                        <input type="text" name="client_name" id="client_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="contact_number"><i class="fas fa-phone mr-2"></i>Contact Number:</label>
                                        <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="location"><i class="fas fa-map-marker-alt mr-2"></i>Location:</label>
                                <input type="text" name="location" id="location" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_name"><i class="fas fa-calendar-alt mr-2"></i>Event Name:</label>
                                <input type="text" name="event_name" id="event_name" class="form-control" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="start_date_time"><i class="fas fa-clock mr-2"></i>Start Date/Time:</label>
                                        <input type="text" name="start_date_time" id="start_date_time" class="form-control" required readonly>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="end_date_time"><i class="fas fa-clock mr-2"></i>End Date/Time:</label>
                                        <input type="text" name="end_date_time" id="end_date_time" class="form-control" required readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_schedule"><i class="fas fa-calendar-check mr-2"></i>Date Schedule</label>
                                <input type="text" class="form-control" id="date_schedule" name="date_schedule" required readonly>
                            </div>
                            
                            <?php if ($accountLevel === 'admin') { ?>
                                <div class="form-group">
                                    <label><i class="fas fa-check-circle mr-2"></i>Status:</label>
                                    <div class="radio-group">
                                        <input type="radio" name="status" id="status_confirm" value="confirm" required checked>
                                        <label for="status_confirm">Confirm</label>
                                        
                                        <input type="radio" name="status" id="status_pending" value="pending">
                                        <label for="status_pending">Pending</label>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <input type="hidden" name="status" value="pending" />
                                <div class="form-group">
                                    <div class="alert" style="background-color: rgba(255, 193, 7, 0.1); border-left-color: #ffc107;">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Status: Your request will be submitted as pending and require admin approval.
                                    </div>
                                </div>
                            <?php } ?>
                            
                            <button type="button" class="btn btn-primary" id="submitScheduleBtn">
                                <i class="fas fa-save mr-2"></i>Submit Schedule Request
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Calendar View Tab -->
                <div class="tab-pane fade" id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
                    <div class="calendar-container" id="calendar"></div>
                    <div class="mb-3">
                        <p class="text-muted"><small>Click on any date to view and select available time slots</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Conflict Resolution Modal -->
    <div class="conflict-modal" id="conflictModal">
        <div class="conflict-modal-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Schedule Conflict</h3>
            <p>Your requested time slot conflicts with an existing schedule.</p>
            <div class="alert" style="font-size: 0.9rem;" id="conflictDetails"></div>
            
            <h5><i class="fas fa-clock mr-2"></i>Available Time Slots:</h5>
            <div class="suggested-times" id="suggestedTimes">
                <!-- Suggested times will be added here dynamically -->
            </div>
            
            <div class="conflict-modal-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="closeConflictModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="useSelectedTime()" id="useSelectedTimeBtn" disabled>Use Selected Time</button>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-modal-content">
            <h3><i class="fas fa-check-circle"></i> Confirm Schedule</h3>
            <p>Please review your schedule details before submitting:</p>
            
            <div class="schedule-details" id="scheduleDetails">
                <!-- Schedule details will be added here dynamically -->
            </div>
            
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="closeConfirmModal()">Back</button>
                <button type="button" class="btn btn-success" onclick="submitSchedule()">Confirm & Submit</button>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="YAWAYAWA.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="bootstrap.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    
    <script>
        // Get current date and time
        const today = new Date();
        const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        
        // Format date for date_schedule field
        document.getElementById('date_schedule').value = today.toISOString().slice(0, 10);
        
        // Initialize flatpickr for start date/time
        const startPicker = flatpickr("#start_date_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            defaultHour: today.getHours(),
            defaultMinute: Math.ceil(today.getMinutes() / 15) * 15, // Round to next 15-min increment
            minuteIncrement: 15,
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length) {
                    // Set end time to be 1 hour after start time by default
                    const endDate = new Date(selectedDates[0]);
                    endDate.setHours(endDate.getHours() + 1);
                    endPicker.setDate(endDate);
                    
                    // Update date_schedule to match the date part of start_date_time
                    document.getElementById('date_schedule').value = dateStr.split(' ')[0];
                    
                    // Set minDate for end picker
                    endPicker.set("minDate", selectedDates[0]);
                }
            }
        });
        
        // Initialize flatpickr for end date/time
        const endPicker = flatpickr("#end_date_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            defaultHour: today.getHours() + 1,
            defaultMinute: Math.ceil(today.getMinutes() / 15) * 15, // Round to next 15-min increment
            minuteIncrement: 15
        });
        
        // Set initial values
        const initialStartDate = new Date();
        initialStartDate.setMinutes(Math.ceil(initialStartDate.getMinutes() / 15) * 15); // Round to next 15-min
        startPicker.setDate(initialStartDate);
        
        const initialEndDate = new Date(initialStartDate);
        initialEndDate.setHours(initialEndDate.getHours() + 1);
        endPicker.setDate(initialEndDate);
        
        // Store all existing schedules
        let existingSchedules = [];
        
        // Fetch existing schedules using AJAX when the page loads
        $(document).ready(function() {
            $.ajax({
                url: 'get_schedules.php', // You'll need to create this endpoint
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    existingSchedules = response;
                    initializeCalendar(response);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching schedules:", error);
                    // Initialize with empty schedules if there's an error
                    initializeCalendar([]);
                }
            });
            
            // Submit button handler
            $("#submitScheduleBtn").click(function() {
                if (validateForm()) {
                    checkForConflicts();
                }
            });
        });
        
        // Form validation
        function validateForm() {
            const form = document.getElementById('scheduleForm');
            
            if (!form.checkValidity()) {
                // Trigger browser's native validation UI
                let tmpSubmit = document.createElement('button');
                form.appendChild(tmpSubmit);
                tmpSubmit.click();
                form.removeChild(tmpSubmit);
                return false;
            }
            
            const startTime = new Date(document.getElementById('start_date_time').value);
            const endTime = new Date(document.getElementById('end_date_time').value);
            
            if (startTime >= endTime) {
                alert("End time must be after start time.");
                return false;
            }
            
            return true;
        }
        
        // Function to check for existing schedule conflicts
        function checkForConflicts() {
            const startTime = document.getElementById('start_date_time').value;
            const endTime = document.getElementById('end_date_time').value;
            const scheduleDate = document.getElementById('date_schedule').value;
            
            $.ajax({
                url: 'check_conflicts.php', // You'll need to create this endpoint
                type: 'POST',
                data: {
                    start_date_time: startTime,
                    end_date_time: endTime,
                    date_schedule: scheduleDate
                },
                dataType: 'json',
                success: function(response) {
                    if (response.conflict) {
                        // Show conflict modal with details
                        showConflictModal(response.conflict, response.suggestions);
                    } else {
                        // No conflicts, show confirmation modal
                        showConfirmModal();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error checking for conflicts:", error);
                    // Fallback: Show confirmation modal anyway
                    showConfirmModal();
                }
            });
        }
        
        // Function to show conflict modal with suggested times
        function showConflictModal(conflict, suggestions) {
            document.getElementById('conflictDetails').innerHTML = 
                `<strong>${conflict.event_name}</strong> is already scheduled from 
                <strong>${conflict.start_time}</strong> to <strong>${conflict.end_time}</strong>.`;
                
            const suggestedTimesContainer = document.getElementById('suggestedTimes');
            suggestedTimesContainer.innerHTML = '';
            
            // If we have a special message from the server (like "day is fully booked"), show it
            if (conflict.message) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'alert alert-info mb-3';
                messageDiv.innerHTML = `<i class="fas fa-info-circle mr-2"></i>${conflict.message}`;
                suggestedTimesContainer.appendChild(messageDiv);
            }
            
            if (suggestions && suggestions.length > 0) {
                // Group suggestions by day for better organization
                const suggestionsByDay = {};
                
                suggestions.forEach(suggestion => {
                    const date = suggestion.start.split(' ')[0];
                    if (!suggestionsByDay[date]) {
                        suggestionsByDay[date] = [];
                    }
                    suggestionsByDay[date].push(suggestion);
                });
                
                // Display suggestions grouped by date
                for (const date in suggestionsByDay) {
                    const dateHeader = document.createElement('h6');
                    const formattedDate = new Date(date).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    dateHeader.innerHTML = `<i class="far fa-calendar-alt mr-2"></i>${formattedDate}`;
                    dateHeader.className = 'mt-3 mb-2';
                    suggestedTimesContainer.appendChild(dateHeader);
                    
                    suggestionsByDay[date].forEach((suggestion, index) => {
                        const div = document.createElement('div');
                        div.className = 'time-suggestion';
                        div.setAttribute('data-start', suggestion.start);
                        div.setAttribute('data-end', suggestion.end);
                        div.innerHTML = `
                            <span><i class="far fa-clock mr-2"></i>${suggestion.start_formatted.split(', ')[1]} - ${suggestion.end_formatted}</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectTimeSlot(this)">Select</button>
                        `;
                        suggestedTimesContainer.appendChild(div);
                    });
                }
            } else {
                suggestedTimesContainer.innerHTML = '<p class="text-muted">No alternative times available. Please try a different date.</p>';
            }
            
            document.getElementById('useSelectedTimeBtn').disabled = true;
            document.getElementById('conflictModal').style.display = 'flex';
        }
        
        // Function to select a suggested time slot
        function selectTimeSlot(button) {
            // Remove selection from all time suggestions
            document.querySelectorAll('.time-suggestion').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selection to clicked time suggestion
            const timeSlot = button.closest('.time-suggestion');
            timeSlot.classList.add('selected');
            
            // Enable the "Use Selected Time" button
            document.getElementById('useSelectedTimeBtn').disabled = false;
        }
        
        // Function to use the selected time slot
        function useSelectedTime() {
            const selected = document.querySelector('.time-suggestion.selected');
            if (selected) {
                const startTime = selected.getAttribute('data-start');
                const endTime = selected.getAttribute('data-end');
                
                // Update form fields with selected time
                startPicker.setDate(startTime);
                endPicker.setDate(endTime);
                
                // Close the conflict modal
                closeConflictModal();
                
                // Show confirmation modal
                showConfirmModal();
            }
        }
        
        // Function to close the conflict modal
        function closeConflictModal() {
            document.getElementById('conflictModal').style.display = 'none';
        }
        
        // Function to show confirmation modal
        function showConfirmModal() {
            const clientName = document.getElementById('client_name').value;
            const contactNumber = document.getElementById('contact_number').value;
            const location = document.getElementById('location').value;
            const eventName = document.getElementById('event_name').value;
            const startDateTime = document.getElementById('start_date_time').value;
            const endDateTime = document.getElementById('end_date_time').value;
            const dateSchedule = document.getElementById('date_schedule').value;
            
            // Format dates for display
            const startFormatted = new Date(startDateTime).toLocaleString();
            const endFormatted = new Date(endDateTime).toLocaleString();
            const dateScheduleFormatted = new Date(dateSchedule).toLocaleDateString();
            
            // Set details in confirmation modal
            document.getElementById('scheduleDetails').innerHTML = `
                <p><i class="fas fa-user"></i> <strong>Client:</strong> ${clientName}</p>
                <p><i class="fas fa-phone"></i> <strong>Contact:</strong> ${contactNumber}</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${location}</p>
                <p><i class="fas fa-calendar-alt"></i> <strong>Event:</strong> ${eventName}</p>
                <p><i class="fas fa-clock"></i> <strong>Start:</strong> ${startFormatted}</p>
                <p><i class="fas fa-clock"></i> <strong>End:</strong> ${endFormatted}</p>
                <p><i class="fas fa-calendar-check"></i> <strong>Date:</strong> ${dateScheduleFormatted}</p>
            `;
            
            document.getElementById('confirmModal').style.display = 'flex';
        }
        
        // Function to close the confirmation modal
        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
        
        // Function to submit the schedule after confirmation
        function submitSchedule() {
            document.getElementById('scheduleForm').submit();
        }
        
        // Initialize FullCalendar
        function initializeCalendar(events) {
            // Process events for the calendar
            const calendarEvents = events.map(event => {
                let color;
                if (event.status === 'pending') {
                    color = '#ffc107'; // warning/yellow for pending
                } else if (event.status === 'confirmed' || event.status === 'confirm') {
                    color = '#dc3545'; // danger/red for confirmed (busy)
                } else if (event.status === 'cancelled' || event.status === 'cancel') {
                    color = '#6c757d'; // secondary/gray for cancelled
                } else {
                    color = '#2779E2'; // accent color for others
                }
                
                return {
                    title: event.event_name,
                    start: event.start_date_time,
                    end: event.end_date_time,
                    backgroundColor: color,
                    borderColor: color,
                    extendedProps: {
                        client: event.client_name,
                        status: event.status,
                        id: event.id
                    }
                };
            });
            
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: calendarEvents,
                selectable: true,
                select: function(info) {
                    // When a date is selected in calendar view
                    const date = info.startStr.split('T')[0];
                    document.getElementById('date_schedule').value = date;
                    
                    // Create a date object from the selected date
                    const selectedDate = new Date(date);
                    selectedDate.setHours(9, 0, 0); // Default to 9:00 AM
                    
                    // Set the start time to 9:00 AM on the selected date
                    startPicker.setDate(selectedDate);
                    
                    // Set the end time to 10:00 AM on the selected date
                    const endDate = new Date(selectedDate);
                    endDate.setHours(10, 0, 0);
                    endPicker.setDate(endDate);
                    
                    // Switch to form tab
                    $('#form-tab').tab('show');
                },
                eventClick: function(info) {
                    // Show event details in a tooltip
                    const event = info.event;
                    const start = new Date(event.start).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const end = event.end ? new Date(event.end).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : "N/A";
                    
                    const tooltip = `
                        <div class="event-tooltip">
                            <strong>${event.title}</strong><br>
                            Client: ${event.extendedProps.client}<br>
                            Time: ${start} - ${end}<br>
                            Status: ${event.extendedProps.status}
                        </div>
                    `;
                    
                    $(info.el).tooltip({
                        title: tooltip,
                        html: true,
                        placement: 'top',
                        container: 'body'
                    }).tooltip('show');
                },
                dayCellDidMount: function(info) {
                    // Check if this date has events
                    const date = info.date.toISOString().split('T')[0];
                    let hasEvents = false;
                    let hasPending = false;
                    let hasConfirmed = false;
                    
                    // Get schedules for this date
                    const daySchedules = events.filter(event => {
                        const eventDate = new Date(event.start_date_time).toISOString().split('T')[0];
                        return eventDate === date;
                    });
                    
                    if (daySchedules.length > 0) {
                        hasEvents = true;
                        hasPending = daySchedules.some(event => event.status === 'pending');
                        hasConfirmed = daySchedules.some(event => event.status === 'confirmed' || event.status === 'confirm');
                    }
                    
                    // Add visual indicators based on schedule status
                    if (hasEvents) {
                        if (hasConfirmed) {
                            info.el.classList.add('fc-day-has-confirmed');
                            
                            // Add the indicator dot
                            const dot = document.createElement('div');
                            dot.className = 'event-indicator confirmed';
                            info.el.appendChild(dot);
                            
                            // Add tooltip showing how many events
                            $(info.el).tooltip({
                                title: `${daySchedules.filter(e => e.status === 'confirmed' || e.status === 'confirm').length} confirmed events`,
                                placement: 'top'
                            });
                        } else if (hasPending) {
                            info.el.classList.add('fc-day-has-pending');
                            
                            // Add the indicator dot
                            const dot = document.createElement('div');
                            dot.className = 'event-indicator pending';
                            info.el.appendChild(dot);
                            
                            // Add tooltip showing how many events
                            $(info.el).tooltip({
                                title: `${daySchedules.filter(e => e.status === 'pending').length} pending events`,
                                placement: 'top'
                            });
                        }
                    } else {
                        info.el.classList.add('fc-day-available');
                    }
                }
            });
            
            calendar.render();
        }
    </script>
</body>
</html>