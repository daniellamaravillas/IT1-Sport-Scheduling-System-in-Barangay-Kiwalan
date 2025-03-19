<?php
session_start();
include 'db.php';
include 'navigation.php';
// ...existing error reporting if needed...

// New search parameters
$searchName = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchDate = isset($_GET['searchDate']) ? $conn->real_escape_string($_GET['searchDate']) : '';

// Updated query to fetch only the required columns
$query = "SELECT c.clients_name, c.location, c.contact_number, 
                 s.start_date_time, s.end_date_time, 
                 DATE(s.start_date_time) AS date_schedule, 
                 us.updated_status
          FROM Schedule s
          JOIN Events e ON s.EventID = e.EventID
          JOIN Clients c ON e.ClientID = c.ClientID
          JOIN Updated_Status us ON s.StatusID = us.StatusID";

// Append search conditions if provided
$whereClauses = [];
if ($searchName !== '') {
    $whereClauses[] = "c.clients_name LIKE '%$searchName%'";
}
if ($searchDate !== '') {
    $whereClauses[] = "DATE(s.start_date_time) = '$searchDate'";
}
if (count($whereClauses) > 0) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}
$query .= " ORDER BY s.start_date_time DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>History</title>
    <!-- ...existing head content... -->
    <style>
        body {
            background-color: #ffffff;
            color: #000000;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            text-align: center; /* Center the text */
            padding-top: 50px; /* Ensure content is visible despite the top bar */
            padding-left: 250px; /* Ensure content is visible despite the sidebar */
        }
        .container {
            animation: fadeIn 1s ease-in-out;
            padding: 20px;
            margin: 0 auto;
            text-align: center;
            margin-top: 20px; /* Adjusted margin-top */
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
            body {
                padding-left: 0; /* Remove left padding on smaller screens */
            }
            .container {
                margin-top: 70px; /* Adjusted margin-top for smaller screens */
            }
        }
    </style>
</head>
<body>
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
                                if ($status === 'completed') {
                                    echo '<span style="background-color: green; color: white; padding: 5px 10px; border-radius: 5px;">Completed</span>';
                                } elseif ($status === 'cancel') {
                                    echo '<span style="background-color: red; color: white; padding: 5px 10px; border-radius: 5px;">Cancel</span>';
                                } else {
                                    echo htmlspecialchars($row['updated_status']);
                                }
                                ?>
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
</body>
</html>
