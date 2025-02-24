<?php
    session_start();
    if (!isset($_SESSION['username']) || $_SESSION['role'] !== "driver") {
        header("Location: login.php");
        exit();
    }
    
    include 'connect.php';
    
    
    // Step 1: Get the logged-in user's `userid` from `tbl_user`
    $driver_username = $_SESSION['username'];
    $user_query = "SELECT userid FROM tbl_user WHERE username = ?";
    $stmt = $mysqli->prepare($user_query);
    
    if (!$stmt) {
        die("User Query Preparation Failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $driver_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        die("User not found in tbl_user.");
    }
    
    $userid = $user['userid'];
    
    // Step 2: Get the ambulance type from `tbl_driver` using `userid`
    $ambulance_query = "SELECT ambulance_type FROM tbl_driver WHERE userid = ?";
    $stmt = $mysqli->prepare($ambulance_query);

    if (!$stmt) {
        die("Driver Query Preparation Failed: " . $mysqli->error);
    }

    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $driver = $result->fetch_assoc(); // This is where you're fetching the driver

    // // Debugging: Check if the driver data is fetched properly
    // var_dump($driver);

    if (!$driver) {
        die("Driver data not found for user ID $userid.");
    }

    $ambulance_type = $driver['ambulance_type'] ?? ''; // Default to empty if not found


    $emergency_query = "SELECT * FROM tbl_emergency WHERE status = 'Pending'";
    $emergency_result = $mysqli->query($emergency_query);
    // Fetch  Prebooking requests for ALL drivers (except palliative-only drivers)
    if ($ambulance_type !== "Palliative") {
       
    
        $prebooking_query = "SELECT p.*, u.username as user_name, u.phoneno
                             FROM tbl_prebooking p 
                             LEFT JOIN tbl_user u ON p.userid = u.userid 
                             WHERE p.status = 'Pending'";
        $prebooking_result = $mysqli->query($prebooking_query);
    }
    
    // Fetch Palliative requests for **ONLY palliative drivers**
    if ($ambulance_type === "Palliative") {
        $palliative_query = "SELECT p.*, u.username as user_name, u.phoneno 
                             FROM tbl_palliative p 
                             LEFT JOIN tbl_user u ON p.userid = u.userid 
                             WHERE p.status = 'Pending'";
        $palliative_result = $mysqli->query($palliative_query);
    }
    
    // Debugging: Check for query errors (only if queries were executed)
    if (isset($emergency_result) && !$emergency_result) {
        echo "Emergency Query Error: " . $mysqli->error;
    }
    if (isset($prebooking_result) && !$prebooking_result) {
        echo "Prebooking Query Error: " . $mysqli->error;
    }
    if ($ambulance_type === "palliative" && !$palliative_result) {
        echo "Palliative Query Error: " . $mysqli->error;
    }
    

  
// Function to send email
function sendConfirmationEmail($userEmail, $userName, $requestType, $requestId) {
    $to = $userEmail;
    $subject = "SWIFTAID - Request Accepted";
    $message = "Dear $userName,\n\n";
    $message .= "Your $requestType request (ID: $requestId) has been accepted by one of our drivers.\n";
    $message .= "We will arrive at your location as specified in your request.\n\n";
    $message .= "Thank you for choosing SWIFTAID.\n";
    $headers = "From: swiftaid@gmail.com";

    return mail($to, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - SWIFTAID</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 90px;
            --primary-color:rgb(5, 30, 16);
            --secondary-color:rgb(40, 186, 18);
        }
        #header {
            background: rgba(34, 39, 34, 0.9);
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            /* background-color: #f6f9ff; */
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
        }

        
        

        .sitename {
            color: var(--primary-color);
            font-size: 24px;
        }

        .navmenu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px;
        }

        .navmenu a {
            color:rgb(155, 156, 157);
            text-decoration: none;
            font-weight: 500;
        }

        .btn-getstarted {
            background: var(--primary-color);
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: var(--header-height);
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: rgba(218, 214, 214, 0.46);
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px 0;
        }

        .sidebar-nav {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .sidebar-nav li {
            padding: 10px 20px;
        }

        .sidebar-nav a {
            color: #012970;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-nav a:hover {
            color: var(--primary-color);
        }
        /* Main Content Area */
        .main {
            margin-left: var(--sidebar-width);
            padding: 20px; /* Reduced from 20px to make content more compact */
            margin-top: var(--header-height);
        }
        .dashboard-card {
            background: rgba(246, 236, 236, 0.46);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .status-pending {
            color: #ffa500;
            font-weight: bold;
        }
        .status-active {
            color: #00a65a;
            font-weight: bold;
        }
        .request-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            justify-content: center;
            margin: 10px auto;
            max-width: 1200px;
        }

        .request-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #eee;
            margin: 0 auto;
            width: 100%;
            max-width: 350px;
        }

        .design {
            background-color: rgba(243, 34, 34, 0.87);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }

        .design:hover {
            background-color: darkred;
        }
      

    </style>
</head>
<body>
   
 <!-- Your existing header code -->
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
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard_driver.php">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="driver_profile.php">
                    <i class="bi bi-person"></i>
                    
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="driverPreviousJob.php">
                    <i class="bi bi-clock-history"></i>
                    <span>Previous Jobs</span>
                </a>
            </li>
            <li>
                <a href="admin_review.php">
                    <i class="bi bi-clock-history"></i>
                    <span>feedback</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>


    <main class="main">
        <div class="dashboard-card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        </div>

        <div class="dashboard-card">
    <h3>Emergency Requests</h3>
    <div class="request-grid">
        <?php if (isset($emergency_result) && $emergency_result && $emergency_result->num_rows > 0): ?>
            <?php while ($request = $emergency_result->fetch_assoc()): ?>
                <div class="request-card emergency">
                    <h4>Emergency Request : <?php echo htmlspecialchars($request['request_id']); ?></h4>
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($request['patient_name']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($request['pickup_location']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['contact_phone']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($request['ambulance_type']); ?></p>
                    
                    <form action="handle_request.php" method="POST" class="accept-form">
                        <input type="hidden" name="request_type" value="emergency">
                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                        <button type="submit" class="design">Accept Request</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-requests">No emergency requests at the moment.</p>
        <?php endif; ?>
    </div>
</div>
        
        <?php if ($ambulance_type !== "Palliative") : ?>
        <div class="dashboard-card">
    <h3>Prebooking Requests</h3>
    <div class="request-grid">
        <?php if ($prebooking_result && $prebooking_result->num_rows > 0) : ?>
            <?php while ($request = $prebooking_result->fetch_assoc()) : ?>
                <div class="request-card prebooking">
                    <h4>Prebooking Request <?php echo htmlspecialchars($request['prebookingid']); ?></h4>
                    <p><strong>User:</strong> <?php echo htmlspecialchars($request['user_name'] ?? 'Unknown User'); ?></p>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($request['pickup_location']); ?></p>
                    <p><strong>To:</strong> <?php echo htmlspecialchars($request['destination']); ?></p>
                    <p><strong>Service Time:</strong> <?php echo htmlspecialchars($request['service_time']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($request['ambulance_type']); ?></p>
                    <p><strong>Additional:</strong> <?php echo htmlspecialchars($request['additional_requirements']); ?></p>
                    <p><strong>Phone No:</strong> <?php echo htmlspecialchars($request['phoneno']); ?></p>
                    <form action="handle_request.php" method="POST" class="accept-form">
                        <input type="hidden" name="request_type" value="prebooking">
                        <input type="hidden" name="request_id" value="<?php echo $request['prebookingid']; ?>">
                        <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($request['email'] ?? ''); ?>">
                        <button type="submit" class="design">Accept Request</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <p class="no-requests">No prebooking requests at the moment.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php if ($ambulance_type === "Palliative") : ?>
<div class="dashboard-card">
    <h3>Palliative Care Requests</h3>
    <div class="request-grid">
        <?php if ($palliative_result && $palliative_result->num_rows > 0): ?>
            <?php while ($request = $palliative_result->fetch_assoc()): ?>
                <div class="request-card emergency">
                    <h4>Palliative Request : <?php echo htmlspecialchars($request['palliativeid']); ?></h4>
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($request['user_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($request['address']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($request['phoneno']); ?></p>
                    <p><strong>Additional Requirements:</strong> <?php echo htmlspecialchars($request['additional_requirements']); ?></p>
                    <p><strong>Medical Condition:</strong> <?php echo htmlspecialchars($request['medical_condition']); ?></p>
                    
                    <form action="handle_palliative.php" method="POST" class="accept-form">
                        <input type="hidden" name="request_type" value="palliative">
                        <input type="hidden" name="request_id" value="<?php echo $request['palliativeid']; ?>">
                        <button type="submit" class="design">Accept Request</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-requests">No Palliative Care requests at the moment.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>




    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
    $(document).ready(function() {
        $(".accept-form").on("submit", function(e) {
            e.preventDefault();
            
            if (!confirm("Are you sure you want to accept this request?")) {
                return;
            }

            let form = $(this);
            let requestType = form.find("input[name='request_type']").val();
            let requestId = form.find("input[name='request_id']").val();
            let userEmail = form.find("input[name='user_email']").val();

            $.ajax({
                url: "handle_request.php",
                type: "POST",
                data: form.serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert("Request accepted successfully!");
                        form.find("button").text("Accepted").prop("disabled", true);
                        // Remove or fade out the card after acceptance
                        form.closest(".request-card").fadeOut(500);
                    } else {
                        alert(response.message || "An error occurred.");
                    }
                },
                error: function() {
                    alert("An error occurred while processing the request.");
                }
            });
        });
    });
    </script>
</body>
</html>


