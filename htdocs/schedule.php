<?php
session_start();
include 'db.php';

$currentDateStr = date('Y-m-d H:i:s');

// Update statuses based on time conditions
$updateStatusQuery = "UPDATE Schedule s 
    JOIN Updated_Status us ON s.StatusID = us.StatusID 
    SET s.StatusID = (
        CASE 
            WHEN s.end_date_time < '$currentDateStr' 
                THEN (SELECT StatusID FROM Updated_Status WHERE updated_status = 'completed')
            WHEN s.start_date_time <= '$currentDateStr' AND s.end_date_time > '$currentDateStr' 
                THEN (SELECT StatusID FROM Updated_Status WHERE updated_status = 'ongoing')
            WHEN s.start_date_time > '$currentDateStr' AND us.updated_status = 'confirm'
                THEN (SELECT StatusID FROM Updated_Status WHERE updated_status = 'upcoming')
            ELSE s.StatusID
        END
    )
    WHERE us.updated_status IN ('confirm', 'ongoing', 'upcoming')";
$conn->query($updateStatusQuery);

// Get search parameters
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchDate = isset($_GET['searchDate']) ? $conn->real_escape_string($_GET['searchDate']) : '';

// Update the base query to include status and only show non-completed schedules
$query = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, 
          COALESCE(s.date_schedule, s.start_date_time) as date_schedule, 
          e.Events_name, c.clients_name, c.contact_number, c.location, 
          u.username as created_by, us.updated_status
          FROM Schedule s
          JOIN Events e ON s.EventID = e.EventID
          JOIN Clients c ON e.ClientID = c.ClientID
          JOIN users u ON c.ID = u.ID
          JOIN Updated_Status us ON s.StatusID = us.StatusID
          WHERE s.end_date_time >= '$currentDateStr'
          OR us.updated_status != 'completed'";

// New GET endpoint for "fetch_all"
if (isset($_GET['fetch_all'])) {
    $fetchQuery = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, c.contact_number, c.location
                   FROM Schedule s
                   JOIN Events e ON s.EventID = e.EventID
                   JOIN Clients c ON e.ClientID = c.ClientID";
    $fetchResult = $conn->query($fetchQuery);
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Schedules</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th>Schedule ID</th>
                <th>Start Date/Time</th>
                <th>End Date/Time</th>
                <th>Event Name</th>
                <th>Client Name</th>
                <th>Contact Number</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>';
    if ($fetchResult && $fetchResult->num_rows > 0) {
        while ($row = $fetchResult->fetch_assoc()) {
            echo '<tr>
                <td>' . htmlspecialchars($row['ScheduleID']) . '</td>
                <td>' . htmlspecialchars($row['start_date_time']) . '</td>
                <td>' . htmlspecialchars($row['end_date_time']) . '</td>
                <td>' . htmlspecialchars($row['Events_name']) . '</td>
                <td>' . htmlspecialchars($row['clients_name']) . '</td>
                <td>' . htmlspecialchars($row['contact_number']) . '</td>
                <td>' . htmlspecialchars($row['location']) . '</td>
            </tr>';
        }
    } else {
         echo '<tr><td colspan="7">No records found.</td></tr>';
    }
    echo '</tbody>
    </table>
</body>
</html>';
    exit;
}

// Build WHERE clause if either search term or date is provided
$whereClauses = [];
if ($searchTerm !== '') {
    $whereClauses[] = "(c.clients_name LIKE '%$searchTerm%' OR c.location LIKE '%$searchTerm%' OR e.Events_name LIKE '%$searchTerm%')";
}
if ($searchDate !== '') {
    $whereClauses[] = "DATE(s.start_date_time) = '$searchDate'";
}
if (count($whereClauses) > 0) {
    $query .= " AND " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY s.start_date_time ASC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Table</title>
    <style>
        body {
            background-color: #fff;
            color: #000;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1;
            width: 100%;
            padding: 20px;
            margin: 0;
            box-sizing: border-box;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 0;
            width: 100%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Adjust spacing for the navigation */
        body > nav {
            margin: 0;
            padding: 0;
        }

        h2 {
            margin: 20px 0;
            padding: 0;
        }

        .table th {
            background: #000;
            color: #fff;
            padding: 12px;
            text-align: left;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        .table tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .form-control {
            padding: 8px;
            border: 1px solid #000;
            margin-right: 10px;
            width: 200px;
        }

        .btn {
            background: #000;
            color: #fff;
            padding: 8px 16px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #333;
        }

        .btn-danger {
            background: #ff0000;
        }

        .btn-danger:hover {
            background: #cc0000;
        }

        form.mb-4 {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .table {
                font-size: 14px;
            }
            
            form.mb-4 {
                flex-direction: column;
            }

            .form-control {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        /* Action Buttons Styling */
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            align-items: center;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 85px;
            font-size: 0.875rem;
        }

        .btn-edit {
            background-color: #fff;
            border: 1px solid #28a745;
            color: #28a745;
        }

        .btn-edit:hover {
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
        }

        .btn-delete {
            background-color: #fff;
            border: 1px solid #dc3545;
            color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #dc3545;
            color: #fff;
            text-decoration: none;
        }

        .action-icon {
            font-size: 14px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }

        .status-completed {
            background-color: #28a745;
            color: white;
        }

        .status-confirm {
            background-color: rgb(62, 85, 212);
            color: white;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>

<body><?php include 'navigation.php'; // Navigation now at the start of the body without leading space ?>
    <div class="container mt-4">
        <div style="text-align: left; margin-bottom: 20px;">
            <h2 class="mb-4">List of Upcoming Schedules</h2> <br>
        </div>
        <!-- Updated search form for horizontal alignment -->
        <form method="get" action="schedule.php" class="mb-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name or location..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <input type="date" name="searchDate" class="form-control" value="<?php echo htmlspecialchars($searchDate); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <br>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span>Total of Schedules Booked: <?php echo $result->num_rows; ?></span>
            <a href="history.php" class="btn btn-secondary">History</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Client Name</th>
                        <th>Contact Number</th>
                        <th>Location</th>
                        <th>Event</th>
                        <th>Start Date/Time</th>
                        <th>End Date/Time</th>
                        <th>Created By</th>
                        <th>Date Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $i = 1; // initialize schedule counter ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $startFormatted = date("l, F j, Y g:i A", strtotime($row['start_date_time']));
                            $endFormatted   = date("l, F j, Y g:i A", strtotime($row['end_date_time']));
                            $createdAt      = date("l, F j, Y g:i A", strtotime($row['date_schedule']));
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
                                <td><?php echo $startFormatted; ?></td>
                                <td><?php echo $endFormatted; ?></td>
                                <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                <td><?php echo $createdAt; ?></td>
                                <td>
                                    <?php 
                                    $status = strtolower($row['updated_status']);
                                    $statusClass = '';
                                    
                                    switch ($status) {
                                        case 'completed':
                                            $statusClass = 'status-completed';
                                            break;
                                        case 'confirm':
                                            $statusClass = 'status-confirm';
                                            break;
                                        case 'cancel':
                                            $statusClass = 'status-cancelled';
                                            break;
                                        default:
                                            $statusClass = '';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($row['updated_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_schedule.php?ScheduleID=<?php echo $row['ScheduleID']; ?>" 
                                           class="btn-action btn-edit" 
                                           title="Edit Schedule">
                                            <i class="fas fa-edit action-icon"></i>
                                            <span>Edit</span>
                                        </a>
                                        <a href="schedule_delete.php?ScheduleID=<?php echo $row['ScheduleID']; ?>" 
                                           class="btn-action btn-delete" 
                                           title="Delete Schedule">
                                            <i class="fas fa-trash action-icon"></i>
                                            <span>Delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No result can be found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Optional: Bootstrap JS and dependencies -->
    <script src="bushit.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>