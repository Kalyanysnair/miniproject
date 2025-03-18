<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details

// Fetch user details
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT username, phoneno FROM tbl_user WHERE username = ?");

// Add error checking
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = $user['username']; // using username instead of name
    $user_phone = $user['phoneno']; // using phoneno instead of phone
} else {
    $user_name = "";
    $user_phone = "";
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name = !empty($_POST['patient_name']) ? trim($_POST['patient_name']) : null;
    $pickup_location = !empty($_POST['pickup_location']) ? trim($_POST['pickup_location']) : null;
    $contact_phone = !empty($_POST['contact_phone']) ? trim($_POST['contact_phone']) : null;
    $ambulance_type = !empty($_POST['ambulance_type']) ? trim($_POST['ambulance_type']) : null;
    // We'll still collect the booking_date but won't insert it to the database
    $booking_date = !empty($_POST['booking_date']) ? trim($_POST['booking_date']) : null;

    if (empty($patient_name) || empty($pickup_location) || empty($contact_phone) || empty($ambulance_type)) {
        die("All fields are required.");
    }

    // Use the original query that matches your table structure
  // Use the query that matches your table structure
$stmt = $conn->prepare("INSERT INTO tbl_emergency (patient_name, pickup_location, contact_phone, ambulance_type, created_at, userid) VALUES (?, ?, ?, ?, NOW(), ?)");

if ($stmt) {
    // Get the user's ID
    $id_stmt = $conn->prepare("SELECT userid FROM tbl_user WHERE username = ?");
    $id_stmt->bind_param("s", $username);
    $id_stmt->execute();
    $id_result = $id_stmt->get_result();
    
    if ($id_result->num_rows > 0) {
        $user_row = $id_result->fetch_assoc();
        $userid = $user_row['userid'];
        
        $stmt->bind_param("ssssi", $patient_name, $pickup_location, $contact_phone, $ambulance_type, $userid);
        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['message'] = "Emergency booking successful!";
        } else {
            die("Database error: " . $stmt->error);
        }
    } else {
        die("User not found.");
    }
} else {
    die("Database error: " . $conn->error);
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Booking</title>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- Bootstrap -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-y: auto;
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
        }
        #map {
            height: 300px;
            width: 100%;
            margin-bottom: 15px;
            display: none; /* Initially hidden */
        }
        .form-container {
            background-color: rgba(229, 229, 229, 0.72);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 40px;
            margin-top: 90px; /* Add space at the top to push the container downwards */
            margin-left: auto; /* Center horizontally */
            margin-right: auto; /* Center horizontally */
            max-width: 800px;
        }
        .validation-message {
            font-size: 0.85em;
            color: #dc3545;
            display: none;
        }
        .input-invalid {
            border: 1px solid #dc3545;
        }
        .input-valid {
            border: 1px solid #28a745;
        }
        .success-message {
            background-color: rgba(40, 167, 69, 0.8);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            display: none;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            position: fixed;
            top: 60px; /* Adjust based on your header height */
            left: 0;
            height: 100%;
            background-color: rgba(194, 195, 194, 0.43);
            padding-top: 20px;
            z-index: 1000;
        }
        
        .user-info {
            color: white;
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .user-info a {
            color: white;
            text-decoration: none;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        
        .sidebar-nav li a {
            display: block;
            padding: 10px 15px;
            color: #f8f9fa;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .sidebar-nav li a:hover {
            background-color: #495057;
        }
        
        .logout-btn {
            color: #ff6b6b !important;
        }
        
        /* Adjust content area to make room for sidebar */
        .content-area {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <!-- Sidebar -->
    <div class="sidebar">
    <div class="user-info"><a href="user1.php">
    <i class="fas fa-user-circle"></i>
    <?php echo $_SESSION['username']; ?></a>
</div>
        <ul class="sidebar-nav">
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="status.php"><i class="fas fa-list"></i> My Bookings</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="container mt-5">
            <div class="form-container">
                <!-- Success Message Display -->
                <div id="successMessage" class="success-message">
                    Emergency booking successful!
                </div>
                
                <h1 style="color:red; text-align:center"><b>Emergency Booking</b></h1>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="emergencyForm">
                    <!-- First Row -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Patient's/Booker Name</label>
                            <input type="text" name="patient_name" id="patient_name" class="form-control" value="<?php echo $user_name; ?>" required>
                            <div id="patient_name_validation" class="validation-message">Please enter a valid name (minimum 2 characters)</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Pickup Location</label>
                            <input type="text" id="pickup_location" name="pickup_location" class="form-control" required readonly>
                            <div id="pickup_location_validation" class="validation-message">Pickup location is required</div>
                        </div>
                    </div>

                    <!-- Location Options -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location_option" id="current_location" value="current" checked>
                                <label class="form-check-label" for="current_location">
                                    Use Current Location
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location_option" id="choose_map" value="map">
                                <label class="form-check-label" for="choose_map">
                                    Choose from Map
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Map Section (initially hidden) -->
                    <div id="map"></div>


                    <!-- Second Row -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Ambulance Type</label>
                            <select name="ambulance_type" id="ambulance_type" class="form-control" required>
                                <option value="">Select ambulance type</option>
                                <option value="Basic">Basic Ambulance Service</option>
                                <option value="Advanced">Advanced Life Support</option>
                                <option value="Critical">Critical Care Ambulance</option>
                                <option value="Neonatal">Neonatal Ambulance</option>
                                <option value="Bariatric">Bariatric Ambulance</option>
                            </select>
                            <div id="ambulance_type_validation" class="validation-message">Please select an ambulance type</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone Number</label>
                            <input type="tel" name="contact_phone" id="contact_phone" class="form-control" value="<?php echo $user_phone; ?>" required>
                            <div id="contact_phone_validation" class="validation-message">Please enter a valid 10-digit phone number</div>
                        </div>
                    </div>
                     
                    <!-- Date Field (Full Width) -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label>Date</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required>
                            <div id="booking_date_validation" class="validation-message">Please select a valid date</div>
                        </div>
                    </div>
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-danger mt-3" id="submitBtn">Book Now</button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Map Integration and Validation -->
    <script>
        // Initialize map but don't show it yet
        var map = L.map('map').setView([10.8505, 76.2711], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        var marker;

        // Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('booking_date').value = today;
            
            // Initially try to get current location since that's the default option
            if (document.getElementById('current_location').checked) {
                getCurrentLocation();
            }
            
            // Set up form validation
            setupValidation();
            
            // Check for success message in URL params (for AJAX form submission)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'true') {
                showSuccessMessage();
            }
            
            // Check for PHP session success message
            <?php if(isset($_SESSION['message'])): ?>
                showSuccessMessage();
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
        });

        // Show success message function
        function showSuccessMessage() {
            const successMsg = document.getElementById('successMessage');
            successMsg.style.display = 'block';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 5000);
        }

        // Location option change handler
        document.querySelectorAll('input[name="location_option"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'current') {
                    document.getElementById('map').style.display = 'none';
                    getCurrentLocation();
                } else if (this.value === 'map') {
                    document.getElementById('map').style.display = 'block';
                    // Need to invalidate size after showing the map
                    setTimeout(function() {
                        map.invalidateSize();
                    }, 100);
                }
            });
        });

        // Function to get the place name based on coordinates
        function getPlaceName(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('pickup_location').value = data.display_name;
                    validateField('pickup_location');
                    
                    // If map option was chosen, hide the map after selection
                    if (document.getElementById('choose_map').checked) {
                        document.getElementById('map').style.display = 'none';
                    }
                })
                .catch(error => console.error("Error fetching location:", error));
        }

        // Click on map to set the location
        map.on('click', function(e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            getPlaceName(e.latlng.lat, e.latlng.lng);
        });

        // Get current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        let lat = position.coords.latitude;
                        let lng = position.coords.longitude;
                        map.setView([lat, lng], 13);
                        if (marker) map.removeLayer(marker);
                        marker = L.marker([lat, lng]).addTo(map);
                        getPlaceName(lat, lng);
                    },
                    (error) => {
                        alert("Error getting location: " + error.message);
                    }
                );
            } else {
                alert("Geolocation not supported.");
            }
        }

        // Live validation setup
        function setupValidation() {
            // Patient Name validation
            document.getElementById('patient_name').addEventListener('input', function() {
                validateField('patient_name');
            });

            // Phone validation
            document.getElementById('contact_phone').addEventListener('input', function() {
                validateField('contact_phone');
            });

            // Ambulance type validation
            document.getElementById('ambulance_type').addEventListener('change', function() {
                validateField('ambulance_type');
            });
            
            // Date validation
            document.getElementById('booking_date').addEventListener('change', function() {
                validateField('booking_date');
            });

            // Form submission
            document.getElementById('emergencyForm').addEventListener('submit', function(event) {
                let isValid = true;
                
                // Validate all fields
                ['patient_name', 'pickup_location', 'contact_phone', 'ambulance_type', 'booking_date'].forEach(function(field) {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    event.preventDefault();
                    return false;
                }
                
                return true;
            });
        }

        // Field validation function
        function validateField(fieldId) {
            const field = document.getElementById(fieldId);
            const validationMsg = document.getElementById(`${fieldId}_validation`);
            let isValid = true;
            
            // Reset classes
            field.classList.remove('input-valid', 'input-invalid');
            if (validationMsg) validationMsg.style.display = 'none';
            
            switch(fieldId) {
                case 'patient_name':
                    if (field.value.trim().length < 2) {
                        isValid = false;
                    }
                    break;
                    
                case 'pickup_location':
                    if (field.value.trim() === '') {
                        isValid = false;
                    }
                    break;
                    
                case 'contact_phone':
                    const phoneRegex = /^\d{10}$/;
                    if (!phoneRegex.test(field.value.trim())) {
                        isValid = false;
                    }
                    break;
                    
                case 'ambulance_type':
                    if (field.value === '') {
                        isValid = false;
                    }
                    break;
                    
                case 'booking_date':
                    const selectedDate = new Date(field.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (isNaN(selectedDate.getTime()) || selectedDate < today) {
                        isValid = false;
                    }
                    break;
            }
            
            if (isValid) {
                field.classList.add('input-valid');
            } else {
                field.classList.add('input-invalid');
                if (validationMsg) validationMsg.style.display = 'block';
            }
            
            return isValid;
        }
    </script>
</body>
</html>