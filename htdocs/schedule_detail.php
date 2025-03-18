<?php
session_start();
include 'db.php'; // ...existing DB connection code...
// include 'navigation.php';

$date = isset($_GET['date']) ? $_GET['date'] : '';
if (!$date) {
    die("Invalid date.");
}
$displayDate = date("l, F j, Y", strtotime($date));

// Check if the schedule time is full
$timeCheckSql = "SELECT COUNT(*) as count FROM Schedule WHERE DATE(start_date_time) = '$date' AND 
                 (TIME(start_date_time) BETWEEN '08:00:00' AND '19:00:00' OR TIME(end_date_time) BETWEEN '08:00:00' AND '19:00:00')";
$timeCheckResult = $conn->query($timeCheckSql);
$timeCheckRow = $timeCheckResult->fetch_assoc();
$isFull = $timeCheckRow['count'] >= 11; // Assuming 11 slots from 8 AM to 7 PM

// Updated query to join the Status and select its value
$sql = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.date_schedule, e.Events_name, c.clients_name, c.contact_number, c.location, us.updated_status
        FROM Schedule s
        JOIN Events e ON s.EventID = e.EventID
        JOIN Clients c ON e.ClientID = c.ClientID
        JOIN Updated_Status us ON s.StatusID = us.StatusID
        WHERE DATE(s.start_date_time) = '$date'
        ORDER BY s.start_date_time ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Schedules for <?php echo $displayDate; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            transition: all 0.3s ease;
        }

        body.faded {
            opacity: 0.7;
            background-color: #eaeaea;
        }

        .container {
            max-width: 95%;
            padding: 20px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
            margin-bottom: 20px;
        }

        h2 {
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        h2 a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        h2 a:hover {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: var(--danger-color);
            font-size: 1.5rem;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .back-btn:hover {
            transform: scale(1.2);
            color: #c0392b;
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 1rem;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        table th {
            background-color: var(--secondary-color);
            color: white;
            position: sticky;
            top: 0;
            padding: 10px;
            text-align: center;
            font-weight: 500;
        }

        table td {
            padding: 12px 10px;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }

        .table-warning {
            background-color: rgba(243, 156, 18, 0.1) !important;
            border-left: 4px solid var(--warning-color);
            transition: all 0.3s ease;
        }

        .table-warning:hover {
            background-color: rgba(243, 156, 18, 0.2) !important;
        }

        .btn {
            border-radius: 4px;
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .status {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-confirmed {
            color: #2980b9;
            background-color: rgba(52, 152, 219, 0.1);
        }

        .status-cancelled {
            color: #c0392b;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .status-pending {
            color: #7f8c8d;
            background-color: rgba(127, 140, 141, 0.1);
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: var(--secondary-color);
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            font-size: 1.2rem;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .container {
                max-width: 100%;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            table {
                font-size: 0.9rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 576px) {
            .action-buttons {
                display: flex;
                flex-direction: column;
            }

            .action-buttons .btn {
                margin-bottom: 5px;
            }

            table th,
            table td {
                padding: 8px 5px;
            }
        }
    </style>
    <!-- ...existing head content... -->
</head>

<body class="<?php echo $isFull ? 'faded' : ''; ?>">
    <div class="container mt-4">
        <h2 class="mb-4">Schedules for <a href="schedule_details.php?date=<?php echo $date; ?>"><?php echo $displayDate; ?></a></h2>
        <!-- Updated back button with transparent background, no border and red '✖' -->
        <div class="d-flex justify-content-end mb-3">
            <a href="homepage.php" class="btn" style="background: transparent; border: none; color: red;">✖</a>
        </div>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Contact Number</th>
                            <th>Location</th>
                            <th>Event</th>
                            <th>Start Date/Time</th>
                            <th>End Date/Time</th>
                            <th>Date Schedule</th>
                            <th>Status</th>
                            <th>Actions</th> <!-- Added actions column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $highlighted = []; // Track first schedule per client
                        while ($row = $result->fetch_assoc()):
                            $startFormatted = date("l, F j, Y g:i A", strtotime($row['start_date_time']));
                            $endFormatted   = date("l, F j, Y g:i A", strtotime($row['end_date_time']));
                            $dateScheduleFormatted = date("l, F j, Y", strtotime($row['date_schedule']));
                            $client = $row['clients_name'];
                            $highlightClass = !isset($highlighted[$client]) ? 'table-warning' : '';
                            $highlighted[$client] = true;
                        ?>
                            <tr class="<?php echo $highlightClass; ?>">
                                <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
                                <td><?php echo $startFormatted; ?></td>
                                <td><?php echo $endFormatted; ?></td>
                                <td><?php echo $dateScheduleFormatted; ?></td>
                                <td>
                                    <?php
                                    $status = $row['updated_status'];
                                    if ($status === 'Confirmed') {
                                        echo "<span style='color: blue;'>" . htmlspecialchars($status) . "</span>";
                                    } elseif (in_array(strtolower($status), ['cancel', 'cancelled'])) {
                                        echo "<span style='color: red;'>" . htmlspecialchars($status) . "</span>";
                                    } else {
                                        echo htmlspecialchars($status);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" onclick="window.location.href='edit_schedule.php?id=<?php echo $row['ScheduleID']; ?>'">
                                        Edit
                                    </button>
                                    &nbsp;
                                    <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Are you sure you want to delete this schedule?')) { window.location.href='delete_schedule.php?id=<?php echo $row['ScheduleID']; ?>'; }">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">No schedule for this day</p>
        <?php endif; ?>
        <div class="text-center mt-3">
        </div>
    </div>
    <!-- ...existing JS code... -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>