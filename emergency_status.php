<?php
session_start();

require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$userid = $_SESSION['user_id'];
$error_message = "";

try {
    // Fetch current booking
    $current_booking_query = "
        SELECT 
            request_id,
            userid,
            pickup_location,
            contact_phone,
            status,
            created_at,
            ambulance_type,
            patient_name
        FROM tbl_emergency 
        WHERE userid = ? 
        AND status NOT IN ('Completed', 'Cancelled') 
        ORDER BY created_at DESC 
        LIMIT 1";
        
    $stmt = $conn->prepare($current_booking_query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_booking = $result->fetch_assoc();

    // Fetch previous bookings
    $previous_bookings_query = "
        SELECT 
            request_id,
            userid,
            pickup_location,
            contact_phone,
            status,
            created_at,
            ambulance_type,
            patient_name
        FROM tbl_emergency 
        WHERE userid = ? 
        AND status IN ('Completed', 'Cancelled') 
        ORDER BY created_at DESC";
        
    $stmt = $conn->prepare($previous_bookings_query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $previous_bookings = $stmt->get_result();

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
    <title>Emergency Booking Status</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
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
        
        .error-message {
            background-color: #F8D7DA;
            color: #721C24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .booking-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Current Booking Status -->
        <div class="card">
            <h2>Current Booking Status</h2>
            <?php if ($current_booking): ?>
                <div class="booking-details">
                    <div class="detail-item">
                        <div class="detail-label">Booking ID</div>
                        <div>#<?php echo htmlspecialchars($current_booking['request_id']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="status-badge status-<?php echo strtolower(htmlspecialchars($current_booking['status'])); ?>">
                            <?php echo htmlspecialchars($current_booking['status']); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Patient Name</div>
                        <div><?php echo htmlspecialchars($current_booking['patient_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Phone</div>
                        <div><?php echo htmlspecialchars($current_booking['contact_phone']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pickup Location</div>
                        <div><?php echo htmlspecialchars($current_booking['pickup_location']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Ambulance Type</div>
                        <div><?php echo htmlspecialchars($current_booking['ambulance_type']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Booked On</div>
                        <div><?php echo date('d M Y, h:i A', strtotime($current_booking['created_at'])); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p>No active bookings found.</p>
            <?php endif; ?>
        </div>

        <!-- Previous Bookings -->
        <div class="card">
            <h2>Previous Bookings</h2>
            <?php if ($previous_bookings && $previous_bookings->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Patient Name</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $previous_bookings->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($booking['request_id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['contact_phone']); ?></td>
                                <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <?php if ($booking['status'] == 'Completed'): ?>
                                        <button class="btn" onclick="proceedToPayment(<?php echo (int)$booking['request_id']; ?>)">
                                            Pay Now
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No previous bookings found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function proceedToPayment(requestId) {
            if (confirm('Do you want to proceed to payment for this completed service?')) {
                window.location.href = 'payment.php?request_id=' + requestId;
            }
        }

        // Refresh the page every 30 seconds to update status
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>