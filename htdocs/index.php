<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    if ($stmt = $conn->prepare("SELECT * FROM users WHERE email = ?")) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variable to indicate the user is logged in
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username']; // Optional: Store additional user info
            header("Location: homepage.php"); // Redirect to homepage
            exit();
        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    } else {
        $error = "Database query failed.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg" style="max-width: 400px; width: 100%; background-color: white;">
        <div class="card-header text-center" style="background-color: white; border-bottom: none;">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgJbMfeLLbE_Wh3cK3RK8s0a-P9hvTwYfHpw&s" 
                 alt="Barangay Kiwalan Logo" 
                 class="img-fluid rounded-circle mb-3" 
                 style="max-width: 100px; margin-top: 20px;">
            <h2 class="text-dark">Login</h2> <!-- Changed text color to black -->
        </div>
        <div class="card-body">
            <?php if (isset($error)) : ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>