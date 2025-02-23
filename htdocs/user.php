
<?php
include 'db.php';
include 'navigation.php';
// ...existing code...
$sql = "SELECT ClientID, clients_name, contact_number, location FROM Clients";
$result = $conn->query($sql);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        <p>Role: <?php echo htmlspecialchars($user['role']); ?></p>
        
        <h3>Your Scheduled Games</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sport</th>
                    <th>Venue</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $schedules->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['sport']); ?></td>
                        <td><?php echo htmlspecialchars($row['venue']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>