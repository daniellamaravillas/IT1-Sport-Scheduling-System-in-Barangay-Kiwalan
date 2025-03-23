<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Check if ScheduleID is provided
if (!isset($_GET['ScheduleID'])) {
    header("Location: schedule.php");
    exit();
}

$scheduleID = $conn->real_escape_string($_GET['ScheduleID']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDateTime = $_POST['start_date_time'];
    $endDateTime = $_POST['end_date_time'];
    $status = $_POST['status'];

    // Validate dates
    if (strtotime($endDateTime) <= strtotime($startDateTime)) {
        $error = "End date must be after start date";
    } else {
        // First get the StatusID
        $statusQuery = "SELECT StatusID FROM Updated_Status WHERE updated_status = ? LIMIT 1";
        $statusStmt = $conn->prepare($statusQuery);
        
        if ($statusStmt) {
            $statusStmt->bind_param("s", $status);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();
            
            if ($statusRow = $statusResult->fetch_assoc()) {
                $statusID = $statusRow['StatusID'];
                
                // Update schedule with the valid StatusID
                $updateQuery = "UPDATE Schedule 
                              SET start_date_time = ?,
                                  end_date_time = ?,
                                  StatusID = ?
                              WHERE ScheduleID = ?";
                
                $stmt = $conn->prepare($updateQuery);
                if ($stmt) {
                    $stmt->bind_param("ssii", $startDateTime, $endDateTime, $statusID, $scheduleID);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_msg'] = "Schedule successfully updated to " . ucfirst($status);
                        header("Location: schedule.php");
                        exit();
                    } else {
                        $error = "Error updating schedule";
                    }
                    $stmt->close();
                }
            } else {
                $error = "Invalid status";
            }
            $statusStmt->close();
        }
    }
}

// Fetch current schedule data
$query = "SELECT s.*, e.Events_name, c.clients_name, us.updated_status 
          FROM Schedule s
          JOIN Events e ON s.EventID = e.EventID
          JOIN Clients c ON e.ClientID = c.ClientID
          JOIN Updated_Status us ON s.StatusID = us.StatusID
          WHERE s.ScheduleID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    header("Location: schedule.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .edit-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .status-select {
            font-weight: 500;
        }
        .status-confirm {
            color: rgb(62, 85, 212);
        }
        .status-cancel {
            color: #dc3545;
        }
        .status-select.status-confirm {
            background-color: rgba(62, 85, 212, 0.1);
            border-color: rgb(62, 85, 212);
            color: rgb(62, 85, 212);
        }

        .status-select.status-cancel {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #dc3545;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <div class="edit-form">
            <h2 class="mb-4">Edit Schedule</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Client Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($schedule['clients_name']); ?>" disabled>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Event</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($schedule['Events_name']); ?>" disabled>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Start Date/Time</label>
                    <input type="datetime-local" name="start_date_time" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['start_date_time'])); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">End Date/Time</label>
                    <input type="datetime-local" name="end_date_time" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['end_date_time'])); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control status-select" required>
                        <option value="confirm" class="status-confirm" 
                            <?php echo ($schedule['updated_status'] == 'confirm') ? 'selected' : ''; ?>>
                            Confirm
                        </option>
                        <option value="cancel" class="status-cancel"
                            <?php echo ($schedule['updated_status'] == 'cancel') ? 'selected' : ''; ?>>
                            Cancel
                        </option>
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                    <a href="schedule.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector('select[name="status"]').addEventListener('change', function() {
        this.className = 'form-control status-select ' + 
            (this.value === 'confirm' ? 'status-confirm' : 'status-cancel');
    });
    </script>
</body>
</html>