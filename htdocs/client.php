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
    <title>Clients List</title>
    <link rel="stylesheet" type="text/css" href="home.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Clients List</h2>
    <div class="button-container">
    <a href="schedule.php"><button>List Of Schedule</button></a>
    <a href="client.php"><button>Clients List</button></a>
</div>
    <table>
        <tr>
            <th>Name</th>
            <th>Contact Number</th>
            <th>Location</th>
            <th>Action</th>
        </tr>
        <?php while($client = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($client['clients_name']) ?></td>
            <td><?= htmlspecialchars($client['contact_number']) ?></td>
            <td><?= htmlspecialchars($client['location']) ?></td>
            <td>
                <a href="edit_client.php?id=<?= $client['ClientID'] ?>">Edit</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $conn->close(); ?>
</body>
</html>