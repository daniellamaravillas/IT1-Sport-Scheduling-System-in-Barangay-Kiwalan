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
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        /* Google Font */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        /* Global Styles */
        body {
            background: url('https://i.pinimg.com/736x/39/4a/85/394a8514c21be4c0fc80e3d2a9879019.jpg') no-repeat center center fixed;
            background-size: cover;
            color: white;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            /* Added animated stars effect */
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/stardust.png') repeat;
            opacity: 0.3;
            animation: moveStars 50s linear infinite;
            z-index: -1;
        }

        @keyframes moveStars {
            from { background-position: 0 0; }
            to { background-position: -10000px 5000px; }
        }

        /* Semi-transparent Login Card */
        .login-box {
            background: rgba(26, 26, 26, 0.8);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            width: 350px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.8s ease-in-out;
        }
        /* Logo styling */
        .login-box .logo {
            margin-bottom: 20px;
        }
        .login-box .logo img {
            width: 80px;
            height: auto;
            border-radius: 12px; // changed from 50% (circle) to 12px for rounded square effect
            border: 2px solid rgba(255, 255, 255, 0.6);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Headings */
        h2 {
            margin-bottom: 20px;
            font-size: 26px;
            font-weight: 600;
        }

        /* Input Fields */
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            background: #2a2a2a;
            color: white;
            font-size: 14px;
            outline: none;
            transition: 0.3s ease;
        }

        input::placeholder {
            color: #b5b5b5;
        }

        input:focus {
            background: #333;
        }

        /* Button */
        .btn {
            width: 100%;
            padding: 12px;
            background:rgb(28, 97, 201); /* Red-Orange Button */
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s ease-in-out;
            margin-top: 20px;
        }

        .btn:hover {
            background:rgb(31, 50, 138);
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgJbMfeLLbE_Wh3cK3RK8s0a-P9hvTwYfHpw&s" alt="Logo">
        </div>
        <h2>Login</h2>
        <?php if (isset($error)) echo "<div class='alert alert-danger' style='color: red;'>$error</div><br>"; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label"></label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"></label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>