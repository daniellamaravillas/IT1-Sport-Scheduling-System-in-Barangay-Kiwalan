<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include ('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $username = htmlspecialchars($_POST['username']);  // renamed variable to match table column
    $account_level = $_POST['account_level'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, password, username, account_level) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $password, $username, $account_level);
        $stmt->execute();
        $success = "Registration successful!";
    }

    if (isset($error)) {
        echo "<script>alert('$error');</script>";
    }
    if (isset($success)) {
        echo "<script>
            alert('$success');
            window.location='index.php';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="card-header text-center">
        <center><h2>Register</h2></center>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="registerForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="account_level" class="form-label">Account Level:</label>
                <select id="account_level" name="account_level" class="form-control" required>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Register</button>
            <p class="text-center">Already have an account?</p>
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-secondary w-100">Login</a>
            </div> 
        </form>
    </div>
</div>
</body>
</html>