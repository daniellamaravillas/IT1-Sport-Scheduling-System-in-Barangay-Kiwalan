<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'navigation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $clients_name = $_POST['clients_name'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $location = $_POST['location'] ?? '';
    
    if ($clients_name && $contact_number && $location){
        $stmt = $conn->prepare("INSERT INTO Clients (clients_name, contact_number, location) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $clients_name, $contact_number, $location);
            if ($stmt->execute()){
                $message = "Client inserted successfully.";
                echo '<script>window.location.href="add_event.php";</script>';
            } else {
                $message = "Error inserting client: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        $message = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Insert Client</title>
    <link rel="stylesheet" href="home.css">

</head>
<body>

    <div class="container">
        <h2>Insert Client</h2>
        <?php if(isset($message)) echo "<p>$message</p>"; ?>
        <form method="POST" action="insert_client.php">
            <label for="clients_name" class="form-label">Client Name:</label>
            <input type="text" name="clients_name" id="clients_name" class="form-control" required>
            
            <label for="contact_number" class="form-label">Contact Number:</label>
            <input type="text" name="contact_number" id="contact_number" class="form-control" required>
            
            <label for="location" class="form-label">Location:</label>
            <input type="text" name="location" id="location" class="form-control" required>
            
            <button type="submit" class="btn-primary">Insert Client</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>