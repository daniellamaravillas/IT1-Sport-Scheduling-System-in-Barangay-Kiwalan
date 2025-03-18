<?php

include 'db.php';

$pendingCount = 0;
$dueTomorrowCount = 0; // Add variable to count schedules due tomorrow
$username = '';
if (isset($_SESSION['account_level']) && $_SESSION['account_level'] === 'admin') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Schedule s JOIN Updated_Status us ON s.StatusID = us.StatusID WHERE us.updated_status = 'pending'");
    $stmt->execute();
    $stmt->bind_result($pendingCount);
    $stmt->fetch();
    $stmt->close();

    // Query to count schedules due tomorrow
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Schedule WHERE DATE(start_date_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
    $stmt->execute();
    $stmt->bind_result($dueTomorrowCount);
    $stmt->fetch();
    $stmt->close();
}

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Check for today's schedules
$todaySql = "SELECT COUNT(*) as count FROM Schedule WHERE DATE(start_date_time) = '$today'";
$todayResult = $conn->query($todaySql);
$todayRow = $todayResult->fetch_assoc();
$todayCount = $todayRow['count'];

// Check for tomorrow's schedules
$tomorrowSql = "SELECT COUNT(*) as count FROM Schedule WHERE DATE(start_date_time) = '$tomorrow'";
$tomorrowResult = $conn->query($tomorrowSql);
$tomorrowRow = $tomorrowResult->fetch_assoc();
$tomorrowCount = $tomorrowRow['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Sidebar Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: #121212; /* Almost Black – Deep Dark Mode Feel */
        }

        .sidebar {
            width: 250px;
            background: #1E293B; /* Charcoal Gray – Subtle & Modern */
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
            transition: width 0.3s, transform 0.3s, opacity 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            overflow: hidden;
            transform: translateX(-100%);
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.5);
            opacity: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
            text-decoration: none;
            transition: background 0.3s, padding-left 0.3s;
            margin-bottom: 12px; /* Add margin between items */
        }

        .sidebar a i {
            margin-right: 10px;
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
        }

        .sidebar a:hover {
            background-color:rgb(47, 121, 231); /* Slightly Darker Orange for Interaction Feedback */
            padding-left: 30px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 0;
            transition: margin-left 0.3s, filter 0.3s;
            color: #EAEAEA; /* Light Gray – Readable on Dark */
        }

        .toggle-btn {
            display: none;
            padding: 10px;
            background-color: rgba(51, 51, 51, 0.5); /* Use rgba for transparency */
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
            border: none;
            cursor: pointer;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
        }

        .sidebar .nav-items {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .sidebar .nav-items a {
            width: 100%;
            text-align: center;
        }

        .sidebar .logout {
            margin-bottom: 20px;
        }

        .profile {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
            text-decoration: none;
            transition: background 0.3s, padding-left 0.3s;
        }

        .profile i {
            margin-right: 10px;
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 60px 0;
        }

        .logo {
            height: 120px; /* Adjust the height as needed */
            width: 120px; /* Adjust the width as needed */
            border-radius: 10px; /* Make it a rounded square */
        }

        .logout-confirmation {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #1E293B; /* Charcoal Gray – Subtle & Modern */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            z-index: 1002;
            text-align: center; /* Center-align the content */
            color: #CDD6F4; /* Soft White-Blue – Comfort on Eyes */
        }

        .logout-confirmation button {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-confirmation .btn-yes {
            background-color: #E87722; /* Bold Orange – Stands Out for Important Actions */
            color: white;
        }

        .logout-confirmation .btn-no {
            background-color: #64748B; /* Muted Blue-Gray – For Inactive Elements */
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
                opacity: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-btn {
                display: block;
            }
        }
    </style>
</head>
<body>
    <button class="toggle-btn" onclick="toggleSidebar()">☰ Menu</button>
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgJbMfeLLbE_Wh3cK3RK8s0a-P9hvTwYfHpw&s" alt="Logo" class="logo">
        </div>
        <div class="nav-items">
            <a href="homepage.php"><i class="fas fa-home"></i> Home</a>
            <a href="notification.php"><i class="fas fa-bell"></i> Notification
                <?php if($pendingCount > 0){ ?>
                   <span class="badge badge-danger"><?php echo $pendingCount; ?></span>
                <?php } ?>
                <?php if($dueTomorrowCount > 0){ ?>
                   <span class="badge badge-warning"><?php echo $dueTomorrowCount; ?> due tomorrow</span>
                <?php } ?>
            </a>
            <a href="schedule.php"><i class="fas fa-calendar-alt"></i> List Of the Schedule</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
        </div>
        <div class="profile">
            <i class="fas fa-user"></i>
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
        <div class="logout">
            <a href="javascript:void(0);" onclick="showLogoutConfirmation()"><i class="fas fa-sign-out-alt"></i>Log Out</a>
        </div>
    </div>

    <div class="logout-confirmation" id="logoutConfirmation">
        <p>Are you sure you want to log out?</p>
        <button class="btn-yes" onclick="confirmLogout()">Yes</button>
        <button class="btn-no" onclick="hideLogoutConfirmation()">No</button>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById("sidebar");
            var mainContent = document.querySelector(".main-content");
            if (sidebar.style.transform === "translateX(0px)") {
                sidebar.style.transform = "translateX(-100%)";
                sidebar.style.opacity = "0";
                mainContent.style.marginLeft = "0";
            } else {
                sidebar.style.transform = "translateX(0px)";
                sidebar.style.opacity = "1";
                mainContent.style.marginLeft = "250px";
            }
        }

        document.addEventListener('mousemove', function(e) {
            var sidebar = document.getElementById("sidebar");
            var mainContent = document.querySelector(".main-content");
            if (e.clientX < 50) {
                sidebar.style.transform = "translateX(0px)";
                sidebar.style.opacity = "1";
                mainContent.style.marginLeft = "250px";
                mainContent.style.filter = "blur(5px)";
            } else if (e.clientX > 250) {
                sidebar.style.transform = "translateX(-100%)";
                sidebar.style.opacity = "0";
                mainContent.style.marginLeft = "0";
                mainContent.style.filter = "none";
            }
        });

        function showLogoutConfirmation() {
            event.preventDefault(); // Prevent the default link behavior
            document.getElementById("logoutConfirmation").style.display = "block";
        }

        function hideLogoutConfirmation() {
            document.getElementById("logoutConfirmation").style.display = "none";
        }

        function confirmLogout() {
            window.location.href = "log-out.php";
        }
    </script>
</body>
</html>