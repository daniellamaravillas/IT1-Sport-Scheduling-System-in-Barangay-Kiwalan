<?php

include 'db.php';

$pendingCount = 0;
if (isset($_SESSION['account_level']) && $_SESSION['account_level'] === 'admin') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Schedule s JOIN Updated_Status us ON s.StatusID = us.StatusID WHERE us.updated_status = 'pending'");
    $stmt->execute();
    $stmt->bind_result($pendingCount);
    $stmt->fetch();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Bar</title>
    <style>
        /* Navigation Bar Styles */
        .navbar {
            width: 100%;
            background: linear-gradient(90deg, #ff6600, #ff781f, #ff8b3d, #ff9d5c, #ffaf7a);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            border-radius: 0 0 15px 15px;
        }

        .navbar-container {
            display: flex;
            align-items: center;
            width: 95%;
            justify-content: space-between;
            padding: 0 20px;
        }

        .navbar-menu {
            list-style: none;
            display: flex;
            gap: 30px;
            padding: 0;
            margin: 0;
        }

        .navbar-menu li {
            display: inline;
        }

        .navbar a {
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease-in-out;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .navbar a:hover {
            color: #ff6600;
            transform: scale(1.1);
        }

        .navbar .navbar-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .navbar .navbar-logo:hover {
            color: #ffaf7a;
            transform: scale(1.1);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            transition: color 0.3s ease-in-out;
        }

        .dropbtn:hover {
            color: #ffaf7a;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background: rgba(255, 102, 0, 0.8);
            backdrop-filter: blur(10px);
            min-width: 160px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            overflow: hidden;
            right: 0;
            animation: fadeIn 0.3s ease-in-out;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: 0.3s;
        }

        .dropdown-content a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 10px;
            }

            .navbar-menu {
                flex-direction: column;
                gap: 15px;
                margin-top: 10px;
            }
        }
    </style>
    <!-- Add Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap CSS for alerts and badges -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-logo">Barangay Kiwalan Sport Scheduling</a>
            <ul class="navbar-menu">
                <li><a href="homepage.php">Home</a></li>
                <li>
                    <a href="pending.php">
                        <i class="fas fa-bell"></i> Notification
                        <?php if($pendingCount > 0){ ?>
                           <span class="badge badge-danger"><?php echo $pendingCount; ?></span>
                        <?php } ?>
                    </a>
                </li>
                <?php if(isset($_SESSION['account_level']) && $_SESSION['account_level'] === 'admin'){ ?>
                    <li><a href="schedule.php">View Schedule</a></li>
                <?php } ?>
            </ul>
            <div class="dropdown">
                <button class="dropbtn">&#9881;</button>
                <div class="dropdown-content">
                    <a href="log-out.php">Log-out</a>
                    <a href="profile.php">Profile</a>
                    <a href="#">Settings</a>
                </div>
            </div>
        </div>
    </nav>
    <?php 
    // Display alert message for accepted/declined notifications if set
    if(isset($_SESSION['alert_message'])) { ?>
        <div class="container mt-2">
            <div class="alert alert-info" role="alert">
                <?php echo $_SESSION['alert_message']; ?>
            </div>
        </div>
    <?php 
        unset($_SESSION['alert_message']);
    } 
    ?>
</body>
</html>