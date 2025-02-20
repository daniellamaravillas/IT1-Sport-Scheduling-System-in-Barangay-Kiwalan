<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['email'] = $user['email'];
        $_SESSION['account_level'] = $user['account_level'];
        header("Location: homepage.php"); // Redirect to homepage
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    
    
</head>
<body>
<div class="container">
    <div class="card-header text-center">
        <center><h2>Login</h2></center>
    </div>
    <div class="card-body">
        <?php if (isset($error)) echo "<div class='alert alert-danger' style='color: red;'>$error</div><br>"; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form> <br>
        <p class="text-center">Don't have an account?</p> 
        <div class="text-center mt-3">
            <a href="register.php" class="btn btn-secondary w-100">Register</a>
        </div>
    </div>
</div>
</body>
</html>