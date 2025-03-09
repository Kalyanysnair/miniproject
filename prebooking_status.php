<?php
session_start();

require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$userid = $_SESSION['user_id'];
$error_message = "";

try {
    // Fetch prebookings for the logged-in user
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
    <title>Prebooking Requests</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin-top: 50px;
            padding: 0;
            display: flex;
        }

        /* .sidebar {
            width: 250px;
            background: rgba(206, 205, 205, 0.8);
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
            margin-top: 80px;
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
            display: flex;
            align-items: center;
        }

        .sidebar ul li a:hover {
            color: #2E8B57;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 18px;
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
            padding-top:40px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #2E8B57;
            border-bottom: 2px solid #2E8B57;
            padding-bottom: 10px;
            margin-bottom: 20px;
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
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .container {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
    <!-- Sidebar -->
    <!-- <div class="sidebar">
        <div class="user-info"><a href="user1.php">
            <i class="fas fa-user-circle"></i>
            <?php echo $_SESSION['username']; ?></a>
        </div>

        <ul class="sidebar-nav">
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            
            <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div> -->

    <!-- Main Content -->
    <div class="container">
       

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Prebookings -->
        <div class="card">
            <h2>Prebooking Requests</h2>
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
                                        <button class="btn" onclick="proceedToPayment(<?php echo (int)$booking['prebookingid']; ?>)">
                                            Pay Now
                                        </button>
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
    </div>

    <script>
        function proceedToPayment(prebookingId) {
            if (confirm('Do you want to proceed to payment for this completed service?')) {
                window.location.href = 'payment.php?prebooking_id=' + prebookingId;
            }
        }

        // Refresh the page every 30 seconds to update status
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>