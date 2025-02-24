<?php
session_start();

require 'connect.php';

// Debug output
echo "<!-- Debug information:\n";
echo "Connected user ID: " . $_SESSION['user_id'] . "\n";
echo "-->\n";

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$userid = $_SESSION['user_id'];
$error_message = "";

try {
    // Debug query
    $test_query = "SELECT COUNT(*) as count FROM tbl_emergency WHERE userid = ?";
    $stmt = $conn->prepare($test_query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $count_result = $stmt->get_result()->fetch_assoc();
    
    echo "<!-- Total bookings found: " . $count_result['count'] . " -->\n";

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

    // Debug output for current booking
    echo "<!-- Current booking query executed. Found: " . ($current_booking ? "Yes" : "No") . " -->\n";
    if ($current_booking) {
        echo "<!-- Current booking status: " . $current_booking['status'] . " -->\n";
    }

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

    // Debug output for previous bookings
    echo "<!-- Previous bookings found: " . $previous_bookings->num_rows . " -->\n";

} catch (Exception $e) {
    $error_message = "An error occurred while fetching your bookings. Please try again later.";
    error_log("Database error: " . $e->getMessage());
    echo "<!-- Database error: " . $e->getMessage() . " -->\n";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }


        body {
            min-height: 100vh;
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            padding: 20px;
            position: relative;
        }

        

        /* Header space reservation */
        .header-space {
            height: 80px; /* Adjust based on your header height */
            margin-bottom: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .glass-card {
            background: rgba(232, 230, 230, 0.76);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .card-header h2 {
            color: var(--dark-color);
            font-size: 1.8rem;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-accepted {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-completed {
            background: rgba(0, 123, 255, 0.2);
            color: #004085;
            border: 1px solid rgba(0, 123, 255, 0.3);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.5);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .detail-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        th {
            background: rgba(0, 0, 0, 0.05);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            color: var(--secondary-color);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
        }

        @media (max-width: 768px) {
            .booking-details {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            .glass-card {
                padding: 15px;
            }
        }
    </style>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
    <div class="container">
    <?php include 'header.php';?>

<?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Current Booking Status -->
        <div class="glass-card">
            <div class="card-header">
                <h2>Current Booking Status</h2>
            </div>
            <?php if ($current_booking): ?>
                <div class="booking-details">
                    <div class="detail-item">
                        <div class="detail-label">Booking ID</div>
                        <div class="detail-value">#<?php echo htmlspecialchars($current_booking['request_id']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="status-badge status-<?php echo strtolower(htmlspecialchars($current_booking['status'])); ?>">
                            <?php echo htmlspecialchars($current_booking['status']); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Patient Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($current_booking['patient_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Phone</div>
                        <div class="detail-value"><?php echo htmlspecialchars($current_booking['contact_phone']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pickup Location</div>
                        <div class="detail-value"><?php echo htmlspecialchars($current_booking['pickup_location']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Ambulance Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($current_booking['ambulance_type']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Booked On</div>
                        <div class="detail-value"><?php echo date('d M Y, h:i A', strtotime($current_booking['created_at'])); ?></div>
                    </div>
                </div>
                <?php if ($current_booking['status'] == 'Accepted'): ?>
                    <button class="btn btn-primary" onclick="proceedToPayment(<?php echo (int)$current_booking['request_id']; ?>)">
                        Proceed to Payment
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <p>No active bookings found.</p>
            <?php endif; ?>
        </div>

        <!-- Previous Bookings -->
        <div class="glass-card">
            <div class="card-header">
                <h2>Previous Bookings</h2>
            </div>
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
            if (confirm('Do you want to proceed to payment?')) {
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