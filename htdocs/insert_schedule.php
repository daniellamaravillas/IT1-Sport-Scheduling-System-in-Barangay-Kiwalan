<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'navigation.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the event id directly from the select option.
    $eventID = $_POST['event'] ?? 0;
    $start_date_time = $_POST['start_date_time'] ?? '';
    $end_date_time = $_POST['end_date_time'] ?? '';
    $statusID = $_POST['status'] ?? 0;
    $ClientID = $_POST['client'] ?? 0;

    $insert_sql = "INSERT INTO Schedule (EventID, start_date_time, end_date_time, StatusID, ClientID) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("issss", $eventID, $start_date_time, $end_date_time, $statusID, $ClientID);
    if ($insert_stmt->execute()) {
        echo "<script>window.location.href='schedule.php';</script>";
        exit();
    } else {
        echo "Error: " . $insert_stmt->error;
    }
}

// Database connection settings (reuse same values as in add_event.php)
$host = '127.0.0.1';
$db   = 'mariadb';
$user = 'mariadb';
$pass = 'mariadb';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit("Database connection failed: " . $e->getMessage());
}

// Query events for the select dropdown
$eventStmt = $pdo->query("SELECT EventID, Events_Name FROM Events ORDER BY Events_Name ASC");
$availableEvents = $eventStmt->fetchAll();

// Query events from the Schedule table
$stmt = $pdo->query("SELECT * FROM Schedule ORDER BY start_date_time ASC");
$events = $stmt->fetchAll();

// Query events from the Updated_Status table
$statusStmt = $pdo->query("SELECT * FROM Updated_Status ORDER BY updated_status");
$availableStatuses = $statusStmt->fetchAll();

// Query clients for the select dropdown
$clientStmt = $pdo->query("SELECT ClientID, clients_name FROM Clients ORDER BY clients_name ASC");
$availableClients = $clientStmt->fetchAll();

?>

<!-- Create Schedule Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Schedule</title>
    <link rel="stylesheet" href="home.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header text-center">
            <h2>Create Schedule</h2>
        </div>
        <div class="card-body">
            <form action="create_schedule.php" method="POST">
                <!-- Replaced two text inputs with a select dropdown showing EventID and Event Name -->
                <div class="mb-3">
                    <label for="event" class="form-label">Event</label>
                    <select name="event" class="form-control" required>
                        <option value="">Select an event</option>
                        <?php foreach ($availableEvents as $evt): ?>
                            <option value="<?= $evt['EventID'] ?>">
                                <?= $evt['EventID'] ?> - <?= $evt['Events_Name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="start_date_time" class="form-label">Start Date and Time</label>
                    <input type="datetime-local" name="start_date_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="end_date_time" class="form-label">End Date and Time</label>
                    <input type="datetime-local" name="end_date_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="">Select status</option>
                        <?php foreach ($availableStatuses as $status): ?>
                            <option value="<?= $status['StatusID'] ?>">
                                <?= $status['updated_status'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="client" class="form-label">Client</label>
                    <select name="client" class="form-control" required>
                        <option value="">Select a client</option>
                        <?php foreach ($availableClients as $client): ?>
                            <option value="<?= $client['ClientID'] ?>">
                                <?= $client['clients_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Replace the single submit button with two side-by-side buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Create Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>