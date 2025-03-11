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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Bar</title>
    <style>
        /* Sidebar Styles */
        .sidebar {
            height: 100%;
            width: 0; /* Set initial width to 0 */
            position: fixed;
            z-index: 1001;
            top: 0;
            left: 0;
            background-color: #111;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }

        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 1.1rem;
            color: white;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            color: #4A7C59;
        }

        .sidebar .closebtn {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 2rem;
            margin-left: 50px;
        }

        .openbtn {
            display: none; /* Hide the open button */
        }

        /* Modal Styles */
        #logoutModal {
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5);
        }
        #logoutModal > div {
            position: relative; 
            margin: 10% auto; 
            padding: 20px; 
            width: 300px; 
            background: white; 
            border-radius: 5px; 
            text-align: center;
        }

        /* Hover area to trigger sidebar */
        #hoverArea {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 20px;
            z-index: 1000;
        }

        /* Adjust main content margin */
        .main-content {
            transition: margin-left 0.5s;
            padding: 16px;
            margin-left: 0; /* Ensure container is visible */
        }

        .container {
            transition: margin-left 0.5s, width 0.5s;
            margin-left: 0; /* Ensure container is visible */
            width: 100%; /* Default width */
        }

        .container.shrink {
            margin-left: 250px; /* Adjust margin when sidebar is open */
            width: calc(100% - 250px); /* Adjust width when sidebar is open */
        }

        .dropbtn {
            width: 100%;
            text-align: left;
            background: rgba(0, 0, 0, 0.3); /* Semi-transparent background */
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
            padding: 12px 20px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .dropbtn:hover {
            background: rgba(0, 0, 0, 0.5);
        }
        .dropdown {
            position: relative;
            width: 100%;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1c1f26;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            z-index: 999;
        }
        .dropdown-content a {
            color: #fff;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            transition: background 0.3s ease;
            font-size: 1rem;
        }
        .dropdown-content a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .dropdown:hover .dropdown-content {
            display: flex;
            flex-direction: column;
        }
    </style>
    <!-- Add Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap CSS for alerts and badges -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>

    <div id="hoverArea"></div> <!-- Hover area to trigger sidebar -->

    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?></a>
        <a href="homepage.php"><i class="fas fa-home"></i> Home</a>
        <a href="notification.php">
            <i class="fas fa-bell"></i> Notification
            <?php if($pendingCount > 0){ ?>
               <span class="badge badge-danger"><?php echo $pendingCount; ?></span>
            <?php } ?>
            <?php if($dueTomorrowCount > 0){ ?>
               <span class="badge badge-warning"><?php echo $dueTomorrowCount; ?> due tomorrow</span>
            <?php } ?>
        </a>
        
        <!-- Settings Dropdown -->
        <div class="dropdown">
            <button class="dropbtn">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Settings</span>
            </button>
            <div class="dropdown-content">
                <a href="history.php">
                    <i class="fas fa-history"></i>
                    History
                </a>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i>
                    Register
                </a>
            </div>
        </div>

        <a href="schedule.php"><i class="fas fa-calendar-alt"></i>List Of  the Schedule</a>
        <a href="logout.php" id="logoutTrigger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div id="main" class="main-content"> <!-- Adjust main content margin -->
        <button class="openbtn" onclick="openNav()">&#9776; Open Sidebar</button>
        <div class="container">
            <form>
                <!-- Your form content here -->
            </form>
        </div>
    </div>
    
    <!-- Updated Logout Confirmation Modal with medium-sized buttons -->
    <div id="logoutModal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 2000;">
      <div style="position: relative; margin: 15% auto; padding: 30px; width: 300px; background: white; border-radius: 5px; text-align: center; font-size: 0.9rem;">
        <p>Are you sure you want to log out?</p>
        <div>
          <button id="confirmLogout" class="btn btn-primary">Yes</button>
          <button id="cancelLogout" class="btn btn-secondary">No</button>
        </div>
      </div>
    </div>
    
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
    
    <script>
        document.getElementById("hoverArea").addEventListener("mouseenter", function() {
            document.getElementById("mySidebar").style.width = "250px";
            document.getElementById("main").style.marginLeft = "250px";
            document.querySelector(".container").classList.add("shrink");
        });

        function closeNav() {
            document.getElementById("mySidebar").style.width = "0";
            document.getElementById("main").style.marginLeft= "0";
            document.querySelector(".container").classList.remove("shrink");
        }

        document.getElementById("logoutTrigger").addEventListener("click", function(e){
            e.preventDefault();
            document.getElementById("logoutModal").style.display = "block";
        });
        document.getElementById("cancelLogout").addEventListener("click", function(){
            document.getElementById("logoutModal").style.display = "none";
        });
        document.getElementById("confirmLogout").addEventListener("click", function(){
            window.location.href = "logout.php";
        });
    </script>
</body>
</html>