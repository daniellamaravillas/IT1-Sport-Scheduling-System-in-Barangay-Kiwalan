<?php
session_start();
include 'db.php';
include 'navigation.php';

$currentDateStr = date('Y-m-d H:i:s');

// Get search parameters
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchDate = isset($_GET['searchDate']) ? $conn->real_escape_string($_GET['searchDate']) : '';

// Base query
$query = "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name, c.contact_number, c.location
          FROM Schedule s
          JOIN Events e ON s.EventID = e.EventID
          JOIN Clients c ON e.ClientID = c.ClientID
          WHERE s.start_date_time >= '$currentDateStr'";

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
    <title>Schedule Table</title>
    <style>
        /* Global Styling */
        body {
            background-color: #ffffff;
            /* changed to white */
            color: #000000;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Container */
        .container {
            animation: fadeIn 1s ease-in-out;
            padding: 20px;
            margin: 0 auto;
            /* center the container */
            text-align: center;
            /* center text inside container */
        }

        /* Animation for a smooth fade-in effect */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Headings */
        h2 {
            text-align: center;
            color: #0a0a0a;
            font-size: 28px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* Table Styling */
        .table {
            width: 90%;
            margin: 20px auto;
            /* center the table */
            background: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .table:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .table thead {
            background-color: #a9a9a9;
            /* darker grey for header */
        }

        .table th {
            padding: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .table tbody tr:nth-child(even) {
            background-color: #d3d3d3;
        }

        .table tbody tr:hover {
            background-color: #bfbfbf;
            transition: 0.3s ease-in-out;
        }

        /* Buttons */
        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 14px;
            text-transform: uppercase;
            transition: all 0.3s ease-in-out;
        }

        .btn-primary {
            background-color: rgb(145, 184, 235);
            color: #101820;
            border: none;
        }

        .btn-primary:hover {
            background-color: rgba(238, 238, 238, 0.42);
            color: #ffffff;
        }

        .btn-danger {
            background-color: rgb(221, 84, 97);
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
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
            border-color: rgba(245, 240, 232, 0.03);
            outline: none;
            box-shadow: 0 0 5px rgba(58, 151, 238, 0.8);
        }

        /* Adjust search form alignment for horizontal layout */
        form.mb-4 {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
            margin-bottom: 20px;
        }

        form.mb-4 input.form-control {
            width: 100%;
            max-width: none;
        }

        form.mb-4 button.btn {
            height: 50px;
        }

        /* Responsive Styling */
        @media (max-width: 768px) {
            .table {
                font-size: 14px;
            }

            form.mb-4 {
                flex-direction: column;
                gap: 5px;
            }

            form.mb-4 .form-control,
            form.mb-4 .btn {
                width: 100%;
            }
        }
    </style>
    <!-- ...existing head content... -->
</head>

<body>
    <div class="container mt-4">
        <center>
            <h2 class="mb-4">List of Upcoming Schedules</h2>
        </center>
        <!-- Updated search form for horizontal alignment -->
        <form method="get" action="schedule.php" class="mb-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name or location..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <input type="date" name="searchDate" class="form-control" value="<?php echo htmlspecialchars($searchDate); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $startFormatted = date("l, F j, Y g:i A", strtotime($row['start_date_time']));
                            $endFormatted   = date("l, F j, Y g:i A", strtotime($row['end_date_time']));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['clients_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['Events_name']); ?></td>
                                <td><?php echo $startFormatted; ?></td>
                                <td><?php echo $endFormatted; ?></td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <a href="edit_schedule.php?ScheduleID=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-primary mr-2">
                                            <i class="fas fa-edit" style="color: green;"></i>
                                        </a>
                                        <a href="schedule_delete.php?ScheduleID=<?php echo $row['ScheduleID']; ?>" onclick="return confirm('Are you sure you want to delete this schedule?');" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No result can be found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Optional: Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>