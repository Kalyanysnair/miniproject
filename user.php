<?php
session_start();
include 'connect.php';

// Ensure user is logged in and is a 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT username, phoneno FROM tbl_user WHERE userid = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$message = ""; // Message to display after form submission

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pickup_location = trim($_POST['Pickup_Location'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $service_type = trim($_POST['Service_Type'] ?? '');
    $service_time = trim($_POST['Service_Time'] ?? '');
    $destination = trim($_POST['Destination'] ?? '');
    $ambulance_type = trim($_POST['Ambulance_Type'] ?? '');
    $additional_requirements = trim($_POST['Additional_Requirements'] ?? '');
    $comments = trim($_POST['Comments'] ?? '');

    if (empty($pickup_location) || empty($service_type) || empty($service_time) || empty($destination) || empty($ambulance_type)) {
        $message = "<div class='alert alert-danger'>All required fields must be filled.</div>";
    } else {
        $comments = !empty($comments) ? $comments : NULL;
        $stmt = $conn->prepare("INSERT INTO tbl_prebooking 
            (userid, pickup_location,  service_type, service_time, destination, ambulance_type, additional_requirements, comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssssss", 
            $user_id, $pickup_location,  $service_type, $service_time, 
            $destination, $ambulance_type, $additional_requirements, $comments);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Request submitted successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
      
        $stmt->close();
    }
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftAid - User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        /* General Styling */
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100%;
        }
        
        /* Header Styling */
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

        .sidebar {
            width: 250px;
            /* background: rgba(0, 51, 102, 0.95); */
            color: white;
            padding: 20px;
            position: fixed;
            top: 70px;
            bottom: 0;
            left: 0;
        }

        .sidebar h2 {
            font-size: 18px;
            text-align: center;
            color:rgb(206, 129, 20);
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav li {
            margin: 15px 0;
        }

        .sidebar-nav li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .sidebar-nav li a i {
            margin-right: 10px;
        }

        /* Form Container Styling */
        .form-container {
            background: rgba(218, 214, 214, 0.46);
            border-radius: 10px;
            padding: 70px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            margin-top: 40px;
        }

        label {
            font-weight: 500;
        }

        .form-control {
            margin-bottom: 20px;
            border: 1px solid #ccc;
        }
        

        .btn-primary {
            background-color:rgb(52, 219, 113);
            border-color:rgb(55, 224, 17);
            color: white;
        }

        .btn-primary:hover {
            background-color:rgb(41, 185, 77);
        }
        .sidebar-nav li a.logout-btn {
    color: white;
    font-weight: bold;
    }

    .sidebar-nav li a.logout-btn:hover {
        color: darkred;
        text-decoration: underline;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px; /* Adjust spacing */
        white-space: nowrap; /* Prevents text from wrapping */
    }

    .user-info i {
        font-size: 30px;
    }

    .user-info h2 {
        font-size: 18px; /* Adjust size if needed */
        margin: 0; /* Remove default margin */
        font-weight: normal; /* Make it less bold for a cleaner look */
    }


    </style>
</head>
<body>
    <!-- Header -->
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
<div class="sidebar">
    <a href="user1.php">
<div class="user-info">
    <i class="fas fa-user-circle"></i>
    <h2><?php echo $_SESSION['username']; ?></h2>
</div>
</a>

    <ul class="sidebar-nav">
        <li><a href="user_profile.php"><i class="fas fa-user"></i>  Profile</a></li>
        <li><a href="prebooking_status.php"><i class="fas fa-list"></i> My Bookings</a></li>
        <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>


   
    <!-- Main Content -->
    <div class="main-content" style="margin-left: 270px;">
        <div class="container">
            <div class="form-container">
            <?php if (!empty($message)) echo $message; ?>
                <h2>Pre-Book Ambulance</h2>
                <form action=" " method="post">
                
                    <div class="row">
                        <div class="col-md-6">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control"value="<?php echo htmlspecialchars($user['username']); ?>" required> 
                            

                            <label for="Pickup_Location">Pickup Location</label>
                            <textarea name="Pickup_Location" id="Pickup_Location" class="form-control" rows="2" required></textarea>
                            

                            <label for="Service_Type">Service Type</label>
                            <select name="Service_Type" id="Service_Type" class="form-control" required>
                                <option value="">--Select Service Type--</option>
                                <option value="Hospital Transport">Hospital Transport</option>
                                <option value="Mortuary Transport">Mortuary Transport</option>
                            </select>

                            <label for="Service_Time">Service Date and Time</label>
<input type="datetime-local" name="Service_Time" id="Service_Time" class="form-control" required>
<small id="datetimeError" style="color: red; display: none;">Please select a future date and time.</small>
                           
                          

                        </div>

                        <div class="col-md-6">
                        <label for="Phone_Number">Phone Number</label>
                        <input type="tel" name="Phone_Number" id="Phone_Number" class="form-control" pattern="[0-9]{10}"  value="<?php echo htmlspecialchars($user['phoneno']); ?>" required>

                        <label for="Destination">Destination</label>
                        <textarea name="Destination" id="Destination" class="form-control" rows="2" required></textarea>

                            <label for="Ambulance_Type">Ambulance Type</label>
                            <select name="Ambulance_Type" id="Ambulance_Type" class="form-control" required>
                            <option value="">Select Ambulance Type</option>
                            <option value="Basic">Basic Ambulance Service</option>
                            <option value="Advanced">Advanced Life Support </option>
                            <option value="Neonatal">Critical Care Ambulance</option>
                            <option value="Neonatal">Neonatal Ambulance</option>
                            <option value="Bariatric">Bariatric Ambulance</option> 
                            <option value="Mortuary">Mortuary Ambulance</option> 
                            </select>

                            <label for="Additional_Requirements">Additional Requirements</label>
                    <select name="Additional_Requirements" id="Additional_Requirements" class="form-control">
                        <option value="">--Select Option--</option>
                        <option value="Wheelchair">Wheelchair</option>
                        <option value="Oxygen Cylinder">Oxygen Cylinder</option>
                        <option value="Stretcher">Stretcher</option>
                        <option value="Stretcher">No Additional Requirements</option>
                    </select>
                           
                        </div>
                    </div>
                    <label for="Comments">Comments</label>
                    <textarea name="Comments" id="Comments" class="form-control" rows="1"></textarea>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPosition(position) {
            document.getElementById("latitude").value = position.coords.latitude;
            document.getElementById("longitude").value = position.coords.longitude;
            document.getElementById("Pickup_Location").value = "Current Location Selected";
        }

        function showError(error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("User denied the request for Geolocation.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    alert("The request to get user location timed out.");
                    break;
                case error.UNKNOWN_ERROR:
                    alert("An unknown error occurred.");
                    break;
            }
        }

        function validateForm() {
            let phone = document.getElementById("Phone_Number").value;
            if (!/^\d{10}$/.test(phone)) {
                alert("Please enter a valid 10-digit phone number.");
                return false;
            }

            let dateTime = document.getElementById("Service_Time").value;
            let now = new Date();
            let selectedTime = new Date(dateTime);
            if (selectedTime < now) {
                alert("Service time cannot be in the past.");
                return false;
            }

            return true;
        }
            document.getElementById("Service_Time").addEventListener("change", function() {
            let inputDateTime = new Date(this.value);
            let currentDateTime = new Date();

            if (inputDateTime <= currentDateTime) {
                document.getElementById("datetimeError").style.display = "block";
                this.value = ""; // Reset input field
            } else {
                document.getElementById("datetimeError").style.display = "none";
            }
        });
    </script>
</body>
</html>