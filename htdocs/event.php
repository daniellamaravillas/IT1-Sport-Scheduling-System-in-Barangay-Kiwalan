<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
// This file assumes $events is available from create_schedule.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="mb-3">
        <a href="add_event.php" class="btn btn-primary">Add Event</a>
    </div>
<ul>
<?php foreach ($events as $event): ?>
  <li>
    <?php echo htmlspecialchars($event['name']); ?>:
    <?php echo htmlspecialchars($event['start_date_time']); ?> -
    <?php echo htmlspecialchars($event['end_date_time']); ?>
  </li>
<?php endforeach; ?>
</ul>
<?php
$sql = "SELECT * FROM Events";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Event ID</th><th>Event Name</th></tr></thead><tbody>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['EventID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Events_name']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<div class='alert alert-warning'>No events found.</div>";
}

$conn->close();
?>
</div>
</body>
</html>