<?php
session_start();
include 'db.php';

$currentDateStr = date('Y-m-d H:i:s');

// Initialize search variables
$searchName = isset($_GET['search']) ? $_GET['search'] : '';
$searchDate = isset($_GET['searchDate']) ? $_GET['searchDate'] : '';

// Query to get completed schedules
$historyQuery = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, 
    e.Events_name, c.clients_name, c.contact_number, c.location, 
    u.username as created_by, us.updated_status
    FROM Schedule s
    JOIN Events e ON s.EventID = e.EventID
    JOIN Clients c ON e.ClientID = c.ClientID
    JOIN users u ON c.ID = u.ID
    JOIN Updated_Status us ON s.StatusID = us.StatusID
    WHERE (s.end_date_time < '$currentDateStr'
    OR us.updated_status = 'completed')";

// Add search conditions if parameters are provided
if (!empty($searchName)) {
    $searchName = $conn->real_escape_string($searchName);
    $historyQuery .= " AND c.clients_name LIKE '%$searchName%'";
}
if (!empty($searchDate)) {
    $searchDate = $conn->real_escape_string($searchDate);
    $historyQuery .= " AND DATE(s.start_date_time) = '$searchDate'";
}

$historyQuery .= " ORDER BY s.end_date_time DESC";

$result = $conn->query($historyQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Remove the padding-left from body since navigation handles spacing */
        body {
            background-color: #ffffff;
            color: #000000;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        /* Adjust container to work with navigation */
        .container {
            animation: fadeIn 1s ease-in-out;
            padding: 20px;
            margin-left: 250px; /* Match navigation width */
            margin-top: 20px;
            width: calc(100% - 250px); /* Adjust width accounting for nav */
        }

        .back-arrow {
            position: absolute;
            left: 20px;
            top: 20px;
            font-size: 18px;
            text-decoration: none;
            color: rgb(27, 107, 212);
        }
        .back-arrow:hover {
            color: rgb(0, 90, 180);
        }
        /* Search Form */
        .form-control {
            background: #1c1f26;
            color: #ffffff;
            border: 1px solid rgb(0, 110, 255);
            border-radius: 6px;
            padding: 8px 12px;
        }

        .form-control:focus {
            border-color:rgba(245, 240, 232, 0.03);
            outline: none;
            box-shadow: 0 0 5px rgba(58, 151, 238, 0.8);
        }

        form.mb-4 {
            margin-bottom: 20px;
        }
        form.mb-4 input.form-control {
            display: inline-block;
            width: 350px;
            margin-right: 10px;
            vertical-align: middle;
        }
        form.mb-4 button.btn {
            vertical-align: middle;
            padding: 12px 20px;
            font-size: 16px;   
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color:rgb(77, 146, 192);
            color: white;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .btn-primary {
            background-color:rgb(27, 107, 212); /* sky blue */
            border: none;
            padding: 12px 20px;
            font-size: 16px;
        }
        .btn-secondary {
            background-color: grey; /* changed background color to grey */
            border: none;
            color: white;
            padding: 12px 20px;
            font-size: 16px;    
        }
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                width: 100%;
            }
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
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
            background-color:rgb(62, 85, 212);
            color: white;
        }

        .status-cancel {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container mt-5">
        <h2>History</h2>
        <!-- Updated search form with Clear All button reintroduced -->
        <form method="get" action="history.php" class="mb-4">
            <input type="text" name="search" class="form-control" placeholder="Search Client Name..." value="<?php echo htmlspecialchars($searchName); ?>">
            <input type="date" name="searchDate" class="form-control" value="<?php echo htmlspecialchars($searchDate); ?>">
            <button type="submit" class="btn btn-primary"><strong>Search</strong></button>
            <a href="history.php" class="btn btn-secondary">Clear All</a>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Location</th>
                    <th>Contact Number</th>
                    <th>Start Date/Time</th>
                    <th>End Date/Time</th>
                    <th>Date Schedule</th>
                    <th>Updated Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($row['start_date_time'])); ?></td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($row['end_date_time'])); ?></td>
                            <td><?php echo htmlspecialchars($row['date_schedule']); ?></td>
                            <td>
                                <?php 
                                $status = strtolower($row['updated_status']);
                                $statusClass = '';
                                $currentTime = new DateTime();
                                $endTime = new DateTime($row['end_date_time']);
                                
                                // Check if schedule is past end time and was confirmed
                                if ($endTime < $currentTime && $status === 'confirm') {
                                    $status = 'completed';
                                    $statusClass = 'status-completed';
                                } else {
                                    switch ($status) {
                                        case 'completed':
                                            $statusClass = 'status-completed';
                                            break;
                                        case 'confirm':
                                            $statusClass = 'status-confirm';
                                            break;
                                        case 'cancel':
                                            $statusClass = 'status-cancel';
                                            break;
                                        default:
                                            $statusClass = '';
                                    }
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No schedule history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>