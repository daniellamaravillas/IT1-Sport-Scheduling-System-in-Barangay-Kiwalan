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
    <style>
        /* General Styling */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(to right, #FFEDD5, #FFE4C4);
    color: #5A3E36;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Container */
.container {
    max-width: 500px;
    padding: 20px;
}

/* Card Styling */
.card {
    background: white;
    border-radius: 15px;
    box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: 0.3s;
}

.card:hover {
    transform: scale(1.02);
    box-shadow: 0px 15px 30px rgba(0, 0, 0, 0.15);
}

/* Card Header */
.card-header {
    background: #E76F51;
    color: white;
    padding: 20px;
    font-size: 22px;
    font-weight: bold;
    text-align: center;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
}

/* Card Body */
.card-body {
    padding: 25px;
    text-align: center;
}

.card-body h3 {
    font-size: 24px;
    color: #E76F51;
    margin-bottom: 10px;
}

.card-body p {
    font-size: 16px;
    color: #6D4C41;
}

/* Card Footer */
.card-footer {
    background: #FFE4C4;
    padding: 15px;
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
}

/* Button Styling */
.btn-primary {
    background: linear-gradient(to right, #FF8A5B, #E76F51);
    border: none;
    padding: 12px 20px;
    font-size: 18px;
    font-weight: bold;
    color: white;
    border-radius: 10px;
    transition: 0.3s;
}

.btn-primary:hover {
    background: linear-gradient(to right, #E76F51, #D45D41);
    box-shadow: 0px 5px 15px rgba(231, 111, 81, 0.5);
    transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 600px) {
    .container {
        max-width: 90%;
    }
    
    .card-header {
        font-size: 20px;
    }
    
    .btn-primary {
        font-size: 16px;
        padding: 10px;
    }
}

    </style>
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
