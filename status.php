<?php
session_start();

require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$userid = $_SESSION['user_id'];
$payment_success = false;
$payment_amount = 0;
$payment_failed = false;
$error_message = "";

// Handle cancel request
if (isset($_POST['cancel_request'])) {
    $request_id = $_POST['request_id'];
    $booking_type = $_POST['booking_type'];
    
    try {
        // Determine which table to update based on booking type
        switch ($booking_type) {
            case 'emergency':
                $query = "UPDATE tbl_emergency SET status = 'Cancelled' WHERE request_id = ? AND userid = ?";
                break;
            case 'prebooking':
                $query = "UPDATE tbl_prebooking SET status = 'Cancelled' WHERE prebookingid = ? AND userid = ?";
                break;
            case 'palliative':
                $query = "UPDATE tbl_palliative SET status = 'Cancelled' WHERE palliativeid = ? AND userid = ?";
                break;
            default:
                throw new Exception("Invalid booking type");
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $request_id, $userid);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['cancel_success'] = true;
        } else {
            $_SESSION['cancel_failed'] = true;
        }
    } catch (Exception $e) {
        $_SESSION['cancel_failed'] = true;
        error_log("Cancellation error: " . $e->getMessage());
    }
    
    // Redirect to prevent form resubmission
    header("Location: status.php");
    exit();
}

if (isset($_SESSION['payment_success']) && $_SESSION['payment_success'] === true) {
    $payment_success = true;
    $payment_amount = isset($_SESSION['payment_amount']) ? $_SESSION['payment_amount'] : 0;
    unset($_SESSION['payment_success']);
    unset($_SESSION['payment_amount']);
}

if (isset($_SESSION['payment_failed']) && $_SESSION['payment_failed'] === true) {
    $payment_failed = true;
    unset($_SESSION['payment_failed']);
}

// Handle cancel success/failure messages
$cancel_success = false;
$cancel_failed = false;

if (isset($_SESSION['cancel_success']) && $_SESSION['cancel_success'] === true) {
    $cancel_success = true;
    unset($_SESSION['cancel_success']);
}

if (isset($_SESSION['cancel_failed']) && $_SESSION['cancel_failed'] === true) {
    $cancel_failed = true;
    unset($_SESSION['cancel_failed']);
}

try {
    // Get all paid bookings
    $paid_bookings_query = "SELECT request_id, request_type, amount FROM tbl_payments 
                           WHERE userid = ? AND payment_status = 'completed'";
    $stmt = $conn->prepare($paid_bookings_query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $paid_result = $stmt->get_result();
    
    // Create arrays to store paid bookings and amounts
    $paid_bookings = [];
    $paid_amounts = [];
    while($row = $paid_result->fetch_assoc()) {
        $paid_bookings[$row['request_type'] . '_' . $row['request_id']] = true;
        $paid_amounts[$row['request_type'] . '_' . $row['request_id']] = $row['amount'];
    }

    // Step 1: Fetch the user's name and phone number from the user table
    $user_query = "SELECT username, phoneno FROM tbl_user WHERE userid = ?";
    $stmt = $conn->prepare($user_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found.");
    }

    $patient_name = $user['username']; // Assuming 'username' is the user's name
    $contact_phone = $user['phoneno'];

    // Step 2: Fetch emergency bookings using the name and phone number
    $emergency_query = "
        SELECT 
            request_id,
            userid,
            pickup_location,
            contact_phone,
            status,
            payment_status,
            created_at,
            ambulance_type,
            patient_name
        FROM tbl_emergency 
        WHERE userid = ? OR userid IS NULL
        ORDER BY created_at DESC";
        
    $stmt = $conn->prepare($emergency_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $emergency_bookings = $stmt->get_result();

    // Add this before the emergency table HTML
    error_log("Debug Emergency Bookings: User ID = " . $userid);
    while ($debug_booking = $emergency_bookings->fetch_assoc()) {
        error_log("Booking ID: " . $debug_booking['request_id'] . 
                  ", Status: " . $debug_booking['status'] . 
                  ", UserID: " . $debug_booking['userid']);
    }
    // Reset the result pointer
    mysqli_data_seek($emergency_bookings, 0);

    // Fetch prebookings
    $prebookings_query = "
        SELECT 
            prebookingid,
            userid,
            pickup_location,
            destination,
            service_type,
            service_time,
            ambulance_type,
            status,
            created_at
        FROM tbl_prebooking 
        WHERE userid = ? 
        ORDER BY created_at DESC";
        
    $stmt = $conn->prepare($prebookings_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $prebookings = $stmt->get_result();

    // Fetch palliative bookings
    $palliative_query = "
        SELECT 
            palliativeid,
            userid,
            address,
            medical_condition,
            status,
            created_at
        FROM tbl_palliative 
        WHERE userid = ? 
        ORDER BY created_at DESC";
        
    $stmt = $conn->prepare($palliative_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $palliative = $stmt->get_result();

} catch (Exception $e) {
    $error_message = "An error occurred while fetching your bookings. Please try again later.";
    error_log("Database error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <!-- Firebase -->
    <script src="https://www.gstatic.com/firebasejs/9.6.11/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.11/firebase-database-compat.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin-top: 80px;
            padding: 0px;
            display: flex;
        }

        .container {
            padding: 20px;
            flex: 1;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #2E8B57;
            border-bottom: 2px solid #2E8B57;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .detail-label {
            font-weight: bold;
            color: #2E8B57;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f0f0f0;
            color: #2E8B57;
        }

        .btn {
            padding: 8px 16px;
            background-color: #2E8B57;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn:hover {
            background-color: #3CB371;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0069d9;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-accepted, .status-approved {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-completed {
            background-color: #D1ECF1;
            color: #0C5460;
        }

        .status-cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }

        .status-paid {
            background-color: #28a745;
            color: white;
        }

        .error-message {
            background-color: #F8D7DA;
            color: #721C24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .container {
                margin-left: 0;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }
        }

        .btn-success.disabled {
            background-color: #28a745;
            cursor: default;
            opacity: 0.65;
        }

        .btn-success.disabled:hover {
            background-color: #28a745;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Map container styles */
        #map-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        #map-content {
            position: relative;
            width: 90%;
            height: 80%;
            margin: 5% auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        #close-map {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1001;
            background: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <?php if ($payment_success): ?>
            <div class="alert alert-success" role="alert">
                Payment of ₹<?php echo number_format($payment_amount, 2); ?> was successful! Your booking status has been updated.
            </div>
        <?php endif; ?>

        <?php if ($payment_failed): ?>
            <div class="alert alert-danger" role="alert">
                Payment failed. Please try again or contact support.
            </div>
        <?php endif; ?>

        <?php if ($cancel_success): ?>
            <div class="alert alert-success" role="alert">
                Your request has been successfully cancelled.
            </div>
        <?php endif; ?>

        <?php if ($cancel_failed): ?>
            <div class="alert alert-danger" role="alert">
                Failed to cancel your request. Please try again or contact support.
            </div>
        <?php endif; ?>

        <?php if ($error_message && !$payment_success): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Emergency Requests -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Emergency Requests</h2>
                <a href="user1.php" class="btn" style="margin-right: 10px;">Back</a>
            </div>
            <?php if ($emergency_bookings && $emergency_bookings->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Ambulance Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $emergency_bookings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                                <td><?php echo htmlspecialchars($booking['ambulance_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'Pending'): ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$booking['request_id']; ?>">
                                            <input type="hidden" name="booking_type" value="emergency">
                                            <button type="submit" name="cancel_request" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php elseif ($booking['status'] == 'Accepted' || $booking['status'] == 'Approved'): ?>
                                        <!-- <button class="btn btn-primary" onclick="trackAmbulance('emergency', <?php echo (int)$booking['request_id']; ?>)">
                                            Track
                                        </button> -->
                                    <?php elseif ($booking['status'] == 'Completed'): ?>
                                        <?php if (isset($paid_bookings['emergency_' . $booking['request_id']])): ?>
                                            <span class="status-badge status-paid">
                                                Paid (₹<?php echo number_format($paid_amounts['emergency_' . $booking['request_id']], 2); ?>)
                                            </span>
                                        <?php else: ?>
                                            <button class="btn" onclick="proceedToPayment(<?php echo (int)$booking['request_id']; ?>, 'emergency')" 
                                                    data-booking-id="<?php echo (int)$booking['request_id']; ?>" 
                                                    data-booking-type="emergency">
                                                Pay Now
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No emergency requests found.</p>
            <?php endif; ?>
        </div>

        <!-- Prebookings -->
        <div class="card">
            <h2>Prebookings</h2>
            <?php if ($prebookings && $prebookings->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pickup Location</th>
                            <th>Destination</th>
                            <th>Service Type</th>
                            <th>Service Time</th>
                            <th>Ambulance Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $prebookings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                                <td><?php echo htmlspecialchars($booking['destination']); ?></td>
                                <td><?php echo htmlspecialchars($booking['service_type']); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['service_time'])); ?></td>
                                <td><?php echo htmlspecialchars($booking['ambulance_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'Pending'): ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$booking['prebookingid']; ?>">
                                            <input type="hidden" name="booking_type" value="prebooking">
                                            <button type="submit" name="cancel_request" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php elseif ($booking['status'] == 'Accepted' || $booking['status'] == 'Approved'): ?>
                                        <!-- <button class="btn btn-primary" onclick="trackAmbulance('prebooking', <?php echo (int)$booking['prebookingid']; ?>)">
                                            Track
                                        </button> -->
                                    <?php elseif ($booking['status'] == 'Completed'): ?>
                                        <?php if (isset($paid_bookings['prebooking_' . $booking['prebookingid']])): ?>
                                            <span class="status-badge status-paid">
                                                Paid (₹<?php echo number_format($paid_amounts['prebooking_' . $booking['prebookingid']], 2); ?>)
                                            </span>
                                        <?php else: ?>
                                            <button class="btn" onclick="proceedToPayment(<?php echo (int)$booking['prebookingid']; ?>, 'prebooking')">
                                                Pay Now
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No prebookings found.</p>
            <?php endif; ?>
        </div>

        <!-- Palliative Bookings -->
        <div class="card">
            <h2>Palliative Bookings</h2>
            <?php if ($palliative && $palliative->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Address</th>
                            <th>Medical Condition</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $palliative->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['address']); ?></td>
                                <td><?php echo htmlspecialchars($booking['medical_condition']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'Pending'): ?>
                                        <form method="post" action="" style="display: inline-block;">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$booking['palliativeid']; ?>">
                                            <input type="hidden" name="booking_type" value="palliative">
                                            <button type="submit" name="cancel_request" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php elseif ($booking['status'] == 'Accepted' || $booking['status'] == 'Approved'): ?>
                                        <!-- <button class="btn btn-primary" onclick="trackAmbulance('palliative', <?php echo (int)$booking['palliativeid']; ?>)">
                                            Track
                                        </button> -->
                                    <?php elseif ($booking['status'] == 'Completed'): ?>
                                        <?php if (isset($paid_bookings['palliative_' . $booking['palliativeid']])): ?>
                                            <span class="status-badge status-paid">
                                                Paid (₹<?php echo number_format($paid_amounts['palliative_' . $booking['palliativeid']], 2); ?>)
                                            </span>
                                        <?php else: ?>
                                            <button class="btn" onclick="proceedToPayment(<?php echo (int)$booking['palliativeid']; ?>, 'palliative')">
                                                Pay Now
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No palliative bookings found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map Modal for Tracking -->
    <div id="map-container">
        <div id="map-content">
            <button id="close-map">&times;</button>
            <div id="estimated-time" style="position: absolute; top: 10px; left: 10px; z-index: 1000; background: white; padding: 10px; border-radius: 5px;"></div>
            <div id="map"></div>
        </div>
    </div>

    <script>
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyDYYyUORNusV_DVD3senGL0dkEtDpUhjvs",
            authDomain: "swiftaidtracking.firebaseapp.com",
            databaseURL: "https://swiftaidtracking-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "swiftaidtracking",
            storageBucket: "swiftaidtracking.firebasestorage.app",
            messagingSenderId: "304104708742",
            appId: "1:304104708742:web:d1e1d833e0286661080f07"
        };

        // Initialize Firebase
        if (typeof firebase !== 'undefined') {
            firebase.initializeApp(firebaseConfig);
        }

        function proceedToPayment(requestId, bookingType) {
            console.log('Request ID:', requestId, 'Booking Type:', bookingType); // Add debug logging
            if (confirm('Do you want to proceed to payment for this completed service?')) {
                let url = 'payment.php?request_id=' + requestId + '&booking_type=' + bookingType;
                console.log('Redirecting to:', url); // Add debug logging
                window.location.href = url;
            }
        }

        // Initialize map and markers as global variables
        let map = null;
        let driverMarker = null;
        let userMarker = null;
        let trackingRef = null;

        function trackAmbulance(bookingType, requestId) {
            // Show map container
            document.getElementById('map-container').style.display = 'flex';
            
            // Initialize map if not already done
            if (!map) {
                try {
                    map = L.map('map').setView([10.1632, 76.6413], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                    console.log('Map initialized successfully');
                } catch (error) {
                    console.error('Error initializing map:', error);
                }
            }

            // Create or update markers
            if (!driverMarker) {
                // Use a default marker with custom popup for driver
                driverMarker = L.marker([0, 0])
                    .bindPopup('Ambulance Location')
                    .addTo(map);
            }

            if (!userMarker) {
                // Use a default marker with custom popup for user
                userMarker = L.marker([0, 0])
                    .bindPopup('Your Location')
                    .addTo(map);
            }

            // Get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    
                    userMarker.setLatLng([userLat, userLng]).update();
                    map.setView([userLat, userLng], 13);
                    userMarker.openPopup();
                }, error => {
                    console.error("Error getting location:", error);
                    alert("Could not get your location. Please enable location services.");
                });
            }

            // Stop previous tracking if any
            if (trackingRef) {
                trackingRef.off();
            }

            // Start real-time tracking of driver
            trackingRef = firebase.database().ref(${bookingType}_${requestId});
            console.log('Listening for updates at:', ${bookingType}_${requestId});

            trackingRef.on('value', (snapshot) => {
                console.log('Received Firebase update:', snapshot.val());
                const data = snapshot.val();
                if (data && data.latitude && data.longitude) {
                    console.log('Driver location:', data.latitude, data.longitude);
                    const driverLat = data.latitude;
                    const driverLng = data.longitude;
                    
                    driverMarker.setLatLng([driverLat, driverLng]).update();
                    driverMarker.openPopup();

                    // Fit both markers in view
                    if (userMarker.getLatLng().lat !== 0) {
                        const bounds = L.latLngBounds([
                            [driverLat, driverLng],
                            [userMarker.getLatLng().lat, userMarker.getLatLng().lng]
                        ]);
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }

                    // Update estimated time
                    updateEstimatedTime(driverLat, driverLng, userMarker.getLatLng().lat, userMarker.getLatLng().lng);
                } else {
                    console.log('No driver location data available yet');
                }
            }, error => {
                console.error('Firebase error:', error);
            });
        }

        // Function to calculate and display estimated arrival time
        function updateEstimatedTime(driverLat, driverLng, userLat, userLng) {
            const directionsService = new google.maps.DirectionsService();
            
            const request = {
                origin: new google.maps.LatLng(driverLat, driverLng),
                destination: new google.maps.LatLng(userLat, userLng),
                travelMode: 'DRIVING'
            };

            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    const duration = result.routes[0].legs[0].duration.text;
                    document.getElementById('estimated-time').textContent = Estimated arrival time: ${duration};
                }
            });
        }

        // Close map and stop tracking
        document.getElementById('close-map').addEventListener('click', () => {
            document.getElementById('map-container').style.display = 'none';
            if (trackingRef) {
                trackingRef.off();
            }
        });
    </script>
</body>
</html>