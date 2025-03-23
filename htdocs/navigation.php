<?php
// Retrieve the username from the session
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Add this at the top after session_start():
include_once 'includes/notification_count.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #sidebar {
            width: 230px;
            height: 100vh;
            background-color: #000; /* Set sidebar background to black */
            color: #fff; /* Set text color to white */
            transition: transform 0.3s ease-in-out, background-color 0.3s ease;
        }
        #sidebar.hidden {
            transform: translateX(-100%);
        }
        #sidebar .nav-link {
            color: #fff; /* Set link color to white */
            transition: color 0.3s ease, background-color 0.3s ease;
            display: flex;
            align-items: center; /* Align icon and text */
            gap: 10px; /* Add space between icon and text */
            margin-bottom: 12px; /* Add spacing between navigation items */
        }
        #sidebar .nav-link:hover {
            color: #fff; /* Keep text color white on hover */
            background-color: #0d6efd; /* Add blue hover background effect */
        }
        .top-menu {
            z-index: 1030;
            background-color: #000; /* Set top bar background to black */
            color: #fff; /* Set text color to white */
            padding: .7rem !important; /* Add padding */
            display: flex;
            justify-content: space-between; /* Space out elements in the top bar */
        }
        #toggleSidebar i {
            color: #fff; /* Ensure the menu icon is white */
            font-size: 1.5rem; /* Resize the menu icon */
        }
        .top-menu .fw-bold {
            color: #fff; /* Ensure "Barangay Kiwalan" text is white */
            font-family: 'Georgia', serif; /* Add a beautiful font */
            font-size: 1.1rem; /* Resize the text */
            font-weight: bold; /* Make the text bold */
        }
        .top-menu img {
            height: 40px; /* Set a fixed height for the logo */
            margin-right: 10px; /* Add spacing between the logo and text */
        }
        #sidebar img {
            width: 150px; /* Set a fixed width for the logo */
            height: 150px; /* Set a fixed height for the logo */
            margin-bottom: 20px; /* Add spacing below the logo */
            border-radius: 50%; /* Make the logo circular */
            object-fit: cover; /* Ensure the image fits within the circle */
        }
        .profile-icon {
            color: #fff; /* Set profile icon color to white */
            font-size: 1.7rem; /* Resize the profile icon */
            cursor: pointer; /* Add pointer cursor for interactivity */
            transition: transform 0.3s ease; /* Add smooth hover effect */
        }
        .profile-icon:hover {
            transform: scale(1.1); /* Slightly enlarge on hover */
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 10px; /* Add spacing between the profile icon and username */
            color: #fff; /* Set text color to white */
            font-size: 1rem; /* Adjust font size for the username */
            position: relative; /* Position for dropdown */
            cursor: pointer; /* Add pointer cursor for interactivity */
        }
        .dropdown-menu {
            position: absolute;
            top: 100%; /* Position below the profile section */
            right: 0; /* Align to the right */
            background-color: #000 !important; /* Force background color to black */
            color: #fff !important; /* Force text color to white */
            border-radius: 5px; /* Add rounded corners */
            border: 1px solid #333; /* Add a subtle border for better visibility */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5); /* Add shadow for depth */
            display: none; /* Initially hidden */
            z-index: 1050; /* Ensure it appears above other elements */
            min-width: 200px; /* Set a minimum width for the dropdown */
            overflow: hidden; /* Prevent content overflow */
        }
        .dropdown-menu.show {
            display: block !important; /* Ensure dropdown is visible */
        }
        .dropdown-menu a {
            color: #fff !important; /* Force link color to white */
            text-decoration: none; /* Remove underline */
            padding: 10px 15px; /* Add padding for clickable area */
            display: block; /* Ensure links take up full width */
            font-size: 1rem; /* Adjust font size for readability */
            transition: background-color 0.3s ease; /* Smooth hover effect */
        }
        .dropdown-menu a:hover {
            background-color: #444 !important; /* Add dark gray hover effect */
            color: #fff !important; /* Keep text color white on hover */
        }
        .badge.rounded-pill {
            position: relative;
            top: -8px;
            margin-left: 5px;
            font-size: 0.75em;
        }
        .nav-link:hover .badge {
            background-color: #bb2d3b !important; /* darker red on hover */
        }
    </style>
</head>
<body>
    <!-- Top Menu -->
    <div class="border-bottom p-2 top-menu">
        <div class="d-flex align-items-center">
            <button class="btn me-2" id="toggleSidebar">
                <i class="bi bi-list"></i> <!-- Bootstrap icon for menu -->
            </button>
            <span class="fw-bold">Sports Kiwalan</span>
        </div>
        <div class="profile-section">
            <i class="bi bi-person-circle profile-icon" id="profileDropdownToggle"></i> <!-- Profile icon -->
            <span><?php echo $username; ?></span> <!-- Display the username beside the profile icon -->
            <div class="dropdown-menu" id="profileDropdown">
                <a href="register.php" class="dropdown-item">
                    <i class="bi bi-person-plus"></i> Register
                </a>
            
                <a href="log-out.php" class="dropdown-item">
                    <i class="bi bi-box-arrow-right"></i> Log-out
                </a>
            </div>
        </div>
    </div>

    <div class="d-flex">
        <!-- Sidebar Navigation -->
        <nav id="sidebar" class="border-end">
            <div class="p-3 text-center">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgJbMfeLLbE_Wh3cK3RK8s0a-P9hvTwYfHpw&s" alt="Logo">
                <ul class="nav flex-column mt-4">
                    <li class="nav-item mb-3"> <!-- Add margin between items -->
                        <a class="nav-link active" aria-current="page" href="homepage.php">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="nav-item mb-3"> <!-- Add margin between items -->
                        <a class="nav-link" href="calendar.php">
                            <i class="bi bi-calendar"></i> Calendar
                        </a>
                    </li>
                    <li class="nav-item mb-3">
                        <a class="nav-link" href="notification.php">
                            <i class="bi bi-bell"></i> Notification
                            <?php 
                            $notifCount = getNotificationCount($conn);
                            if ($notifCount > 0): 
                            ?>
                                <span class="badge rounded-pill bg-danger"><?php echo $notifCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item mb-3"> <!-- Add margin between items -->
                        <a class="nav-link" href="schedule.php">
                            <i class="bi bi-list-task"></i>Schedules
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('hidden');
        });

        const profileDropdownToggle = document.getElementById('profileDropdownToggle');
        const profileDropdown = document.getElementById('profileDropdown');

        profileDropdownToggle.addEventListener('click', () => {
            profileDropdown.classList.toggle('show'); // Toggle dropdown visibility
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!profileDropdownToggle.contains(event.target) && !profileDropdown.contains(event.target)) {
                profileDropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>