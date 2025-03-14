<?php
session_start();

require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$userid = $_SESSION['user_id'];
$error_message = "";

// Add this after session_start()
$payment_success = false;
$payment_amount = 0;

if (isset($_SESSION['payment_success'])) {
    $payment_success = true;
    $payment_amount = $_SESSION['payment_amount'];
    unset($_SESSION['payment_success']);
    unset($_SESSION['payment_amount']);
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

    //Debug: Check the fetched name and phone number
    $patient_name = $user['username']; // Assuming 'username' is the user's name
    $contact_phone = $user['phoneno'];
    // echo "Debug: Fetched name: " . $patient_name . "<br>"; // Debug: Print the fetched name
    // echo "Debug: Fetched phone number: " . $contact_phone . "<br>"; // Debug: Print the fetched phone number

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

    // Debug: Check if data is fetched
    if ($emergency_bookings->num_rows === 0) {
        // $error_message = "No emergency bookings found for the user.";
        // echo "Debug: No rows found for name: " . $patient_name . " and phone: " . $contact_phone . "<br>"; // Debug: Print if no rows are found
    } else {
        // echo "Debug: Rows found: " . $emergency_bookings->num_rows . "<br>"; // Debug: Print the number of rows found
    }

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

        /* .sidebar {
            width: 250px;
            background: rgba(206, 205, 205, 0.8);
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
            margin-top: 80;
            left: 0;
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
        } */

        .container {
            /* margin-left: 250px; */
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
        }

        .btn:hover {
            background-color: #3CB371;
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

        .status-accepted {
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    
    <!-- <div class="sidebar">
<div class="user-info"><a href="user1.php">
    <i class="fas fa-user-circle"></i>
    <?php echo $_SESSION['username']; ?></a>
</div>

    <ul class="sidebar-nav">
        
        <li><a href="user_profile.php"><i class="fas fa-user"></i>  Profile</a></li>
        <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div> -->

    <!-- Main Content -->
    <div class="container">
        <?php include 'header.php'; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($payment_success): ?>
            <div class="alert alert-success" role="alert">
                Payment of ₹<?php echo number_format($payment_amount, 2); ?> was successful! Your booking status has been updated.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['payment']) && $_GET['payment'] === 'failed'): ?>
            <div class="alert alert-danger" role="alert">
                Payment failed. Please try again or contact support.
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
                            
                            <!-- <th>Patient Name</th>
                            <th>Contact</th> -->
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
                                
                                <!-- <td><?php echo htmlspecialchars($booking['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['contact_phone']); ?></td> -->
                                <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                                <td><?php echo htmlspecialchars($booking['ambulance_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'Completed'): ?>
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
                            <!-- <th>Booking ID</th> -->
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
                                <!-- <td>#<?php echo htmlspecialchars($booking['prebookingid']); ?></td> -->
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
                                    <?php if ($booking['status'] == 'Completed'): ?>
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
                            <!-- <th>Booking ID</th> -->
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
                                <!-- <td>#<?php echo htmlspecialchars($booking['palliativeid']); ?></td> -->
                                <td><?php echo htmlspecialchars($booking['address']); ?></td>
                                <td><?php echo htmlspecialchars($booking['medical_condition']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                    
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'Completed'): ?>
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

    <script>
        function proceedToPayment(requestId, bookingType) {
            console.log('Request ID:', requestId, 'Booking Type:', bookingType); // Add debug logging
            if (confirm('Do you want to proceed to payment for this completed service?')) {
                let url = 'payment.php?request_id=' + requestId + '&booking_type=' + bookingType;
                console.log('Redirecting to:', url); // Add debug logging
                window.location.href = url;
            }
        }

        // Refresh the page every 30 seconds to update status
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>