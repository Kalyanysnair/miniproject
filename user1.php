<?php
session_start();
include 'connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Services Dashboard</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            line-height: 1.6;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: rgba(218, 214, 214, 0.46);
            color: brown;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
        }

        /* .user-info {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        } */
        /* Updated Sidebar Styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color:  rgba(218, 214, 214, 0.46);
            color: #333;
            padding: 25px 20px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .user-info {
            padding: 20px 0;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            margin-top: 60px; /* Space for the header */
        }

        .user-info h2 {
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 10px;
        }

        .sidebar-nav li a {
            display: block;
            padding: 12px 15px;
            color: #444;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            background-color:  rgba(218, 214, 214, 0.46);
        }

        .sidebar-nav li a:hover {
            background-color: rgba(8, 218, 43, 0.1);
            color: rgb(8, 218, 43);
            transform: translateX(5px);
        }

        .sidebar-nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .logout-btn {
            color: #dc3545 !important;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .logout-btn:hover {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
        }

        /* Fix for the main content */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            background-color: transparent;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                background-color: rgba(255, 255, 255, 0.98);
            }
            
            .user-info {
                margin-top: 20px;
            }
        }
        

        .welcome-text {
            font-size: 0.9rem;
            color: #ecf0f1;
            margin-bottom: 5px;
        }

        .username {
            font-size: 1.2rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 15px;
        }

        .sidebar-menu {
            margin-top: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: background-color 0.3s;
        }

        .menu-item:hover {
            background-color:rgb(8, 218, 43);
        }

        .menu-item.active {
            background-color:rgb(57, 219, 52);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .hero-header {
            height: 300px;
            background: transparent;
            position: relative;
            transition: transform 0.3s ease;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(206, 206, 206, 0.5);
        }

        .header-content {
            position: relative;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 0 20px;
        }

        .header-content h1 {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: -60px;
        }

        .service-box {
            background:  rgba(238, 236, 236, 0.77);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .service-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, #007bff, #00ff88);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .service-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .service-box:hover::before {
            transform: scaleX(1);
        }

        .service-box .icon {
            font-size: 40px;
            margin-bottom: 20px;
            display: inline-block;
            padding: 20px;
            border-radius: 50%;
            background-color: #f8f9fa;
        }

        .service-box h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .service-box p {
            color: #666;
            margin-bottom: 20px;
        }

        .learn-more {
            color:rgb(8, 79, 17);
            font-weight: bold;
            display: inline-block;
            padding: 8px 20px;
            border: 2px solid rgb(11, 138, 22);
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .service-box:hover .learn-more {
            background-color:rgb(26, 199, 55);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #2c3e50;
                padding: 10px;
                border-radius: 5px;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
        <a href="index.html" class="logo d-flex align-items-center me-auto">
            <img src="assets/img/SWIFTAID2.png" alt="SWIFTAID Logo" style="height: 70px; margin-right: 10px;">
            <h1 class="sitename">SWIFTAID</h1>
        </a>
        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="index.html#hero">Home</a></li>
                <li><a href="index.html#about">About</a></li>
                <li><a href="index.html#services">Services</a></li>
                <li><a href="index.html#ambulanceservice">Ambulance Services</a></li>
                <li><a href="index.html#contact">Contact</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            </ul>
        </nav>
        <a class="btn-getstarted" href="emergency.php">Emergency Booking</a>
    </div>
</header>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="user-info">
            <div class="user-avatar">👤</div>
           
        <h2><i class="fas fa-user"></i> Welcome, <?php echo $_SESSION['username']; ?></h2>
        </div>
        <nav class="sidebar-menu">
        <ul class="sidebar-nav">
        <li><a href="dashboard.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="my_bookings.php"><i class="fas fa-list"></i> My Bookings</a></li>
        <li><a href="feedback.php"><i class="fas fa-th-list"></i> Feedback</a></li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
            
            <!-- <a href="#" class="menu-item" onclick="logout()">Logout</a> -->
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="hero-header">
            <div class="header-content">
                <h1>SwiftAid - User Dashboard</h1>
            </div>
        </header>

        <main class="container">
            <div class="services-grid">
                <!-- Emergency Care Box -->
                 <a href="emergency_status.php">
                <div class="service-box" onclick="navigateTo('emergency')">
                    <div class="icon">🚑</div>
                    <h2>Urgent Care Services</h2>
                    <p>24/7 emergency medical assistance with priority response.</p>
                    <div class="learn-more">Emergency Services→</div>
                </div>
               
                </a>
                <!-- Pre-booking Box -->
                 <a href="user.php">
                <div class="service-box" onclick="navigateTo('prebooking')">
                    <div class="icon">📅</div>
                    <h2>Pre-booking Services</h2>
                    <p>Schedule your appointments in advance for routine checkups.</p>
                    <div class="learn-more">Prebook Now →</div>
                </div>
                </a>
                <!-- Palliative Care Box -->
                 <a href="palliative.php">
                <div class="service-box" onclick="navigateTo('palliative')">
                    <div class="icon">💝</div>
                    <h2>Palliative Care</h2>
                    <p>Specialized care focusing on relief from serious illness.</p>
                    <div class="learn-more">Palliative Care →</div>
                 </div>
                </a>
            </div>
        </main>
    </div>

    <script>
        function navigateTo(page) {
            document.body.style.opacity = '0.5';
            setTimeout(() => {
                alert(`Navigating to ${page} page...`);
                document.body.style.opacity = '1';
            }, 300);
        }

        function logout() {
            if(confirm('Are you sure you want to logout?')) {
                alert('Logging out...');
                // Add your logout logic here
            }
        }

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scroll effect
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.hero-header');
                if (window.scrollY > 50) {
                    header.style.transform = 'translateY(-10px)';
                } else {
                    header.style.transform = 'translateY(0)';
                }
            });

            // Handle mobile menu
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>