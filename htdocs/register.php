<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use include_once to prevent duplicate inclusion
include_once('db.php');
include_once('navigation.php'); // Include the navigation bar only once

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];
    $username = htmlspecialchars($_POST['username']);

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (strlen($password) >= 8) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (email, password, username) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $hashedPassword, $username);
            $stmt->execute();
            $success = "Registration successful!";
        }
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
        }

        .is-valid {
            border-color: #28a745 !important;
        }

        .form-container {
            min-height: calc(100vh - 56px); /* Subtract the height of the navbar */
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once('navigation.php'); ?> <!-- Include navigation bar -->
    <div class="container d-flex justify-content-center align-items-center form-container">
        <div class="card shadow-lg" style="max-width: 500px; width: 100%;">
            <div class="card-header text-center bg-primary text-white">
                <h2>Register</h2>
            </div>
            <div class="card-body">
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger text-center">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($success)) : ?>
                    <div class="alert alert-success text-center">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="" id="registerForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password:</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" minlength="8" required>
                        <small class="text-muted">Password must be at least 8 characters long.</small>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username:</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>