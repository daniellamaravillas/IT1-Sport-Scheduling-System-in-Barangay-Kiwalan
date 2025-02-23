<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include 'navigation.php';
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
    // Handle database connection error
    exit("Database connection failed: " . $e->getMessage());
}

// Fetch clients from the database
$clients = [];
try {
    $stmt = $pdo->query("SELECT ClientID, clients_name FROM Clients");
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle query error
    exit("Failed to fetch clients: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = $_POST['eventName'] ?? '';
    $clientID = $_POST['clientID'] ?? '';
    
    // Fix the SQL statement to include the event name column
    $stmt = $pdo->prepare("INSERT INTO Events (Events_name, clientID) VALUES (?, ?)");
    $stmt->execute([$eventName, $clientID]);
    
    header("Location: insert_schedule.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Event</title>
  <link rel="stylesheet" href="home.css">
</head>
<body>
  <h1>Add New Event</h1>
  <form method="post" action="add_event.php">
    <div>
      <label for="eventName">Event Name:</label>
      <input type="text" id="eventName" name="eventName" required>
    </div>
    <div>
      <label for="clientID">Client Name:</label>
      <select id="clientID" name="clientID" required>
        <?php foreach ($clients as $client): ?>
          <option value="<?= htmlspecialchars($client['ClientID']) ?>"><?= htmlspecialchars($client['clients_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit">Create Event</button>
  </form>
</body>
</html>