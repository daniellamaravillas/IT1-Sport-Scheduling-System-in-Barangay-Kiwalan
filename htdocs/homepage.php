<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'navigation.php';

$email = $_SESSION['email'];

// Secure query using prepared statements
$query = "SELECT username FROM users WHERE email = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Database error: " . $conn->error);
}

$username = $user ? htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') : "Guest";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Home</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="home.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>Welcome, <?php echo $username; ?>!</h2>
        </div>
        <div class="card-body">
            <h3>Barangay Kiwalan Sport Scheduling</h3>
            <p>Here you can find the latest schedules for sports events.</p>
            <p>Explore the different events available in our community.</p>
        </div>
        <div class="card-footer text-center">
            <button class="btn btn-primary w-100" onclick="window.location.href='calendar.php'">View Calendar</button>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
