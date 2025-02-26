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
        /* Navigation Bar Styles */
.navbar {
    width: 100%;
    background: linear-gradient(90deg, #B8E2B0, #A2D9A4, #8FCF98);
    backdrop-filter: blur(10px);
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease-in-out;
    z-index: 1000;
    border-radius: 0 0 15px 15px;
}

/* Navbar container */
.navbar-container {
    display: flex;
    align-items: center;
    width: 95%;
    justify-content: space-between;
    padding: 0 20px;
}

/* Navigation links */
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

/* Hover effects */
.navbar a:hover {
    color: #4A7C59;
    transform: scale(1.1);
}

/* Logo */
.navbar .navbar-logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
    transition: 0.3s;
}

.navbar .navbar-logo:hover {
    color: #6BA674;
    transform: scale(1.1);
}

/* Dropdown menu */
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
    color: #6BA674;
}

/* Dropdown content */
.dropdown-content {
    display: none;
    position: absolute;
    background: rgba(184, 226, 176, 0.9);
    backdrop-filter: blur(10px);
    min-width: 160px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
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
    background: rgba(255, 255, 255, 0.2);
}

/* Dropdown hover display */
.dropdown:hover .dropdown-content {
    display: block;
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive styles */
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

    </style>
    <!-- Add Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap CSS for alerts and badges -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-container">
            <!-- Removed link from logo -->
            <div class="navbar-logo">
                <img src="https://scontent.fcgy1-2.fna.fbcdn.net/v/t39.30808-6/344355081_529397526067763_4993170846417519597_n.jpg?_nc_cat=108&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeH1v4nAB0rHVfdSk5-IZmbiVoUdO3jjhPBWhR07eOOE8NcClpnazWElzwPK0UomoTAH79X5LxRNeMdbbTD6yUAp&_nc_ohc=_bJZQeWPhrcQ7kNvgH5Thgv&_nc_oc=Adg1GpVSE-MfMobCgag-kZz73AXy0IjP1Bxbi_Sb_U2DBKdP3Z43LjMRBD4FOLvq6s4&_nc_zt=23&_nc_ht=scontent.fcgy1-2.fna&_nc_gid=Admj0s7pKr0H2CgQonaBDHv&oh=00_AYD9hTAwQDZBivUzZXkv7zeH4oeRUW_y05kMLP3tGzj1Mw&oe=67C4CD75" alt="Barangay Kiwalan Logo" style="height: 75px; width: 75px; border-radius: 50%; object-fit: cover;">
            </div>
            <ul class="navbar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li>
                    <a href="pending.php">
                        <i class="fas fa-bell"></i> Notification
                        <?php if($pendingCount > 0){ ?>
                           <span class="badge badge-danger"><?php echo $pendingCount; ?></span>
                        <?php } ?>
                    </a>
                </li>
                <?php if(isset($_SESSION['account_level']) && $_SESSION['account_level'] === 'admin'){ ?>
                    <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> List Of Schedules</a></li>
                <?php } ?>
            </ul>
            <div class="dropdown">
                <button class="dropbtn">&#9881;</button>
                <div class="dropdown-content">
                    <!-- Replace logout link with modal trigger -->
                    <a href="#" id="logoutTrigger">Log-out</a>
                    <a href="#">History</a>
                </div>
            </div>
        </div>
    </nav>
    
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
        document.getElementById("logoutTrigger").addEventListener("click", function(e){
            e.preventDefault();
            document.getElementById("logoutModal").style.display = "block";
        });
        document.getElementById("cancelLogout").addEventListener("click", function(){
            document.getElementById("logoutModal").style.display = "none";
        });
        document.getElementById("confirmLogout").addEventListener("click", function(){
            window.location.href = "log-out.php";
        });
    </script>
</body>
</html>