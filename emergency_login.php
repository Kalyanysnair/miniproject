<?php
session_start();
include 'connect.php';

// Debugging function to log data to a file
function debug_to_file($data, $title = 'Debug Log') {
    $log_file = 'debug_log.txt';
    $log_entry = "=== " . $title . " === " . date('Y-m-d H:i:s') . " ===\n";
    
    if (is_array($data) || is_object($data)) {
        $log_entry .= print_r($data, true);
    } else {
        $log_entry .= $data;
    }
    
    $log_entry .= "\n\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = 'emergency_form.php'; // Set redirect after login
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userData = [];
$userid = null;

try {
    // Fetch the user's ID from tbl_user
    $userQuery = "SELECT userid, phoneno FROM tbl_user WHERE username = ?";
    $stmt = $conn->prepare($userQuery);
    
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            $userid = $userData['userid']; // Store userid for later use
        }
        $stmt->close();
    }
} catch (Exception $e) {
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - Error fetching user data: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name = !empty($_POST['patient_name']) ? trim($_POST['patient_name']) : null;
    $pickup_location = !empty($_POST['pickup_location']) ? trim($_POST['pickup_location']) : null;
    $contact_phone = !empty($_POST['contact_phone']) ? trim($_POST['contact_phone']) : null;
    $ambulance_type = !empty($_POST['ambulance_type']) ? trim($_POST['ambulance_type']) : null;

    if (empty($patient_name) || empty($pickup_location) || empty($contact_phone) || empty($ambulance_type)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        $status = 'Pending';

        try {
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            // Insert into tbl_emergency using userid instead of username
            $sql = "INSERT INTO tbl_emergency (userid, patient_name, pickup_location, contact_phone, ambulance_type, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isssss", $userid, $patient_name, $pickup_location, $contact_phone, $ambulance_type, $status);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Emergency request submitted successfully!";
                } else {
                    throw new Exception("Error: " . $stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception("SQL Prepare Error: " . $conn->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Booking</title>

    <!-- Google Maps API Key (Replace with your own API key) -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDo-B6eOHsgGobdykDX7jkMBl8NcEuwZ_k&libraries=places&callback=initMap" async defer></script>
    
    <!-- Stylesheets -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        #map {
            height: 300px; /* Ensure the map has a height */
            width: 100%;
        }
        .dropdown-options {
            display: none;
        }
        .btn-warning {
            background: rgb(208, 13, 13);
            color: #fff;
        }
        .btn-warning:hover {
            background: rgb(243, 119, 18);
            opacity: 0.9;
            transform: scale(1.05);
        }
        .btn-custom {
            width: 100%;
            font-size: 18px;
            padding: 15px;
            margin-top: 15px;
            border-radius: 12px;
            font-weight: bold;
            text-transform: uppercase;
            transition: 0.3s ease-in-out;
        }
       
        .validation-error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }

        input:invalid, select:invalid {
            border-color: red;
        }

        input:focus:invalid, select:focus:invalid {
            box-shadow: 0 0 3px red;
        }

        .success-validation {
            border-color: green !important;
        }

        .emergency-alert {
            background-color: rgba(255, 0, 0, 0.1);
            border-left: 4px solid red;
            padding: 15px;
            margin-bottom: 20px;
        }

        /* Add loading indicator styles */
        .loading {
            position: relative;
        }

        .loading:after {
            content: "";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.2);
            border-top-color: #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }

        .form-container {
            background-color: rgba(151, 147, 147, 0.5); /* Background color for the box */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
            padding: 20px; /* Inner padding */
            margin: auto; /* Center align */
            max-width: 800px; /* Limit width of the form */
        }
        .form-container h2 {
            text-align: center; /* Center the heading */
        }  
        .btn-warning {
            background:rgb(208, 13, 13);
            color: #fff;
        }

        .btn-warning:hover {
            background:rgb(243, 119, 18);
            opacity: 0.9;
            transform: scale(1.05);
        }
            
        .dropdown-options {
            display: none;
        }

        .header,
        .btn-getstarted {
            background-color:rgb(72, 78, 72); /* Green matching header */
            color: white;
        }

        .btn-primary {
            background-color:rgb(72, 194, 76); /* Green for buttons */
            border: none;
        }

        .btn-primary:hover {
            background-color: #45a049;
        }
            
        ::placeholder {
            color: green; /* Change placeholder color to green */
            opacity: 1; /* Ensure the placeholder is fully visible */
        }
        
        /* For input fields specifically */
        input::placeholder {
            color: green;
        }

        /* Optional: For select and other form elements, if needed */
        select::placeholder {
            color: green;
        }
        .form-container {
            background-color:rgba(151, 147, 147, 0.5); /* Background color for the box */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
            padding: 20px; /* Inner padding */
            margin: auto; /* Center align */
            max-width: 800px; /* Limit width of the form */
        }
        .form-container h2 {
            text-align: center; /* Center the heading */
        }
        .sidebar {
        width: 250px;
        background: rgba(166, 164, 164, 0.8);
        color: white;
        padding: 20px;
        height: 100vh;
        position: fixed;
        margin-top: 80px; /* Add 'px' here */
        left: 0;
        z-index: 1000; /* Add z-index to ensure it appears above other content */
    }


        .sidebar h2 {
            color: #2E8B57;
            margin-bottom: 20px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }

        .sidebar ul li a:hover {
            color: #2E8B57;
        } 
    </style>
</head>
<body>
   <?php include 'header.php'; ?>

  
    <div class="sidebar">
<div class="user-info"><a href="user1.php">
    <i class="fas fa-user-circle"></i>
    <?php echo $_SESSION['username']; ?></a>
</div>

    <ul class="sidebar-nav">
        
        <li><a href="user_profile.php"><i class="fas fa-user"></i>  Profile</a></li>
        <li><a href="emergency_status.php"><i class="fas fa-list"></i> My Bookings</a></li>
        <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div> 
    <section id="hero" class="hero section dark-background">
        <div id="hero-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-item active">
                <img src="assets/assets/img/template/Groovin/hero-carousel/road.jpg" alt="" class="hero-image">
                <div class="carousel-container">
                    <div class="container">
                        <!-- Emergency Booking Form -->
                        <div class="container mt-5">
                            <div class="form-container">
                                <h2 style="color:red">Emergency Booking</h2><br>
                                <form method="post" class="php-email-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="row gy-4">
                                         <!-- Display Success or Error Message -->
                                    <?php if (isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                                    <?php elseif (isset($_SESSION['error'])): ?>
                                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                                    <?php endif; ?>
                                                                    <!-- Patient/Booker Name (Pre-filled) -->
                                        <div class="col-md-6">
                                            <b>Patient's/Booker Name</b>
                                            <input type="text" name="patient_name" class="form-control" 
                                                value="<?php echo isset($userData['name']) ? htmlspecialchars($userData['name']) : ''; ?>" 
                                                placeholder="Name of Patient/Booker" required>
                                        </div>
                                        
                                        <!-- Location Input Method Selection -->
                                        <div class="col-md-6">
                                            <label for="location-method"><b>Select Location Input Method</b></label>
                                            <select id="location-method" class="form-control" onchange="showLocationOptions()" required>
                                                <option value="">Choose an option</option>
                                                <option value="current">Share Current Location</option>
                                                <option value="map">Use Google Maps</option>
                                            </select>
                                        </div>

                                        <!-- Current Location -->
                                        <div id="current-location" class="dropdown-options col-md-12">
                                            <button type="button" class="btn btn-primary mt-2" onclick="getCurrentLocation()">Use Current Location</button>
                                            <input type="text" id="current-location-input" class="form-control mt-2" readonly>
                                        </div>

                                        <!-- Google Maps Input -->
                                        <div id="map-location" class="dropdown-options col-md-12">
                                            <input type="text" id="map-input" class="form-control mt-2" placeholder="Search Location">
                                            <div id="map" class="mt-2"></div>
                                        </div>

                                        <!-- Hidden Field to Store Pickup Location (Sent to DB) -->
                                        <input type="hidden" id="pickup_location" name="pickup_location">

                                        <!-- Type of Ambulance -->
                                        <div class="col-md-6">
                                            <b>Ambulance Type</b>
                                            <select name="ambulance_type" class="form-control" required>
                                                <option value="">Select Ambulance Type</option>
                                                <option value="Basic">Basic Ambulance Service</option>
                                                <option value="Advanced">Advanced Life Support </option>
                                                <option value="Critical">Critical Care Ambulance</option>
                                                <option value="Neonatal">Neonatal Ambulance</option>
                                                <option value="Bariatric">Bariatric Ambulance</option> 
                                            </select>
                                        </div>

                                        <!-- Phone Number (Pre-filled) -->
                                        <div class="col-md-6">
                                            <b>Phone Number</b>
                                            <input type="tel" name="contact_phone" class="form-control" 
                                                value="<?php echo isset($userData['phone']) ? htmlspecialchars($userData['phone']) : ''; ?>" 
                                                placeholder="Phone Number" required>
                                        </div>
                                        
                                        <!-- Date (Auto-filled with Current Date) -->
                                        <div class="col-md-12">
                                            <b>Date</b>
                                            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-primary">Book Now</button>
                                            <!-- <a href="tel:+1234567890" class="btn btn-warning btn-custom">📞 Call Ambulance</a> -->
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Form validation logic
        document.addEventListener('DOMContentLoaded', function() {
            // Get form elements
            const form = document.querySelector('.php-email-form');
            const patientNameInput = document.querySelector('input[name="patient_name"]');
            const phoneInput = document.querySelector('input[name="contact_phone"]');
            const ambulanceTypeSelect = document.querySelector('select[name="ambulance_type"]');
            const locationMethodSelect = document.getElementById('location-method');
            
            // Error message display function
            function showError(element, message) {
                // Remove any existing error message
                const existingError = element.parentElement.querySelector('.validation-error');
                if (existingError) existingError.remove();
                
                // Create and add new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'validation-error';
                errorDiv.style.color = 'red';
                errorDiv.style.fontSize = '14px';
                errorDiv.style.marginTop = '5px';
                errorDiv.innerText = message;
                
                element.parentElement.appendChild(errorDiv);
                element.style.borderColor = 'red';
            }
            
            // Clear error display function
            function clearError(element) {
                const existingError = element.parentElement.querySelector('.validation-error');
                if (existingError) existingError.remove();
                element.style.borderColor = '';
            }
            
            // Validate patient name
            patientNameInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    showError(this, 'Patient/Booker name is required');
                } else if (! /^[A-Za-z\s]{3,50}$/.test(this.value)) {
                    showError(this, 'Name should contain only letters and spaces');
                } else {
                    clearError(this);
                }
            });
            
            // Validate phone number
            phoneInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    showError(this, 'Phone number is required');
                } else if (! /^[6-9]\d{9}$/.test(this.value)) {
                    showError(this, 'Phone number must be 10 digits');
                } else {
                    clearError(this);
                }
            });
            
            // Validate ambulance type selection
            ambulanceTypeSelect.addEventListener('change', function() {
                if (this.value === '') {
                    showError(this, 'Please select an ambulance type');
                } else {
                    clearError(this);
                }
            });
            
            // Validate location method
            locationMethodSelect.addEventListener('change', function() {
                if (this.value === '') {
                    showError(this, 'Please select a location input method');
                } else {
                    clearError(this);
                }
            });
            
            // Form submission validation
            form.addEventListener('submit', function(event) {
                let hasErrors = false;
                
                // Check patient name
                if (patientNameInput.value.trim() === '') {
                    showError(patientNameInput, 'Patient/Booker name is required');
                    hasErrors = true;
                }
                
                // Check phone number
                if (phoneInput.value.trim() === '') {
                    showError(phoneInput, 'Phone number is required');
                    hasErrors = true;
                } else if (! /^[6-9]\d{9}$/.test(phoneInput.value)) {
                    showError(phoneInput, 'Phone number must be 10 digits');
                    hasErrors = true;
                }
                
                // Check ambulance type
                if (ambulanceTypeSelect.value === '') {
                    showError(ambulanceTypeSelect, 'Please select an ambulance type');
                    hasErrors = true;
                }
                
                // Check if location is provided
                const pickupLocation = document.getElementById('pickup_location').value;
                if (!pickupLocation) {
                    showError(locationMethodSelect, 'Please select and confirm a pickup location');
                    hasErrors = true;
                }
                
                // Prevent form submission if there are errors
                if (hasErrors) {
                    event.preventDefault();
                }
            });
        });

        // Google Maps and location handling
        let map, marker, autocomplete;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 9.5280, lng: 76.8227 }, // Default center (adjustable)
                zoom: 12
            });

            const input = document.getElementById("map-input");
            autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.setFields(["geometry", "formatted_address"]);

            // Add marker when a place is selected
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    alert("No details available for input: '" + place.name + "'");
                    return;
                }

                // Get latitude and longitude from selected place
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                const formattedAddress = place.formatted_address;

                // Update form inputs
                document.getElementById("pickup_location").value = `${lat}, ${lng}`;
                document.getElementById("map-input").value = formattedAddress; // Show formatted address in input field

                // Update map
                map.setCenter(place.geometry.location);
                if (marker) marker.setMap(null); // Remove existing marker
                marker = new google.maps.Marker({
                    position: place.geometry.location,
                    map: map
                });

                console.log("Selected Location: ", formattedAddress, lat, lng);
            });

            // Add click event listener to the map
            map.addListener("click", (event) => {
                const lat = event.latLng.lat();
                const lng = event.latLng.lng();

                // Update form inputs
                document.getElementById("pickup_location").value = `${lat}, ${lng}`;
                document.getElementById("map-input").value = `Lat: ${lat}, Lng: ${lng}`;

                // Update map
                if (marker) marker.setMap(null); // Remove existing marker
                marker = new google.maps.Marker({
                    position: event.latLng,
                    map: map
                });

                console.log("Clicked Location: ", lat, lng);
            });
        }

        function getCurrentLocation() { 
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const latLng = new google.maps.LatLng(lat, lng);

                        // Store in hidden field for form submission
                        document.getElementById("pickup_location").value = `${lat}, ${lng}`;
                        document.getElementById("current-location-input").value = `Lat: ${lat}, Lng: ${lng}`;

                        // Update map if visible
                        if (map) {
                            map.setCenter(latLng);
                            if (marker) marker.setMap(null); // Remove existing marker
                            marker = new google.maps.Marker({
                                position: latLng,
                                map: map
                            });
                        }

                        console.log("Current Location: ", lat, lng);
                    },
                    (error) => {
                        alert("Error getting current location: " + error.message);
                        console.error("Geolocation error:", error);
                    }
                );
            } else {
                alert("Geolocation is not supported by your browser.");
            }
        }

        function showLocationOptions() {
            const method = document.getElementById("location-method").value;
            document.querySelectorAll(".dropdown-options").forEach(option => option.style.display = "none");

            if (method === "current") {
                document.getElementById("current-location").style.display = "block";
                getCurrentLocation(); // Automatically get current location when this option is selected
            }
            if (method === "map") {
                document.getElementById("map-location").style.display = "block";
                // Make sure map is visible before trying to initialize
                setTimeout(() => {
                    google.maps.event.trigger(map, 'resize');
                }, 100);
            }
        }
    </script>
</body>
</html>