<?php
session_start();

require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please log in again.");
}

$userid = $_SESSION['user_id'];
$error_message = "";

try {
    // Fetch palliative bookings for the logged-in user
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
    <title>Palliative Requests</title>
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
            margin-top: 80px;
            padding: 0;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: rgba(206, 205, 205, 0.8);
            color: white;
            padding: 20px;
            height: calc(100vh - 80px); /* Full height minus header height */
            position: fixed;
            top: 80px; /* Same as header height */
            left: 0;
            overflow-y: auto; /* Add scrollbar if content overflows */
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
        }

        .container {
            margin-left: 250px;
            padding: 20px;
            flex: 1;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 120px); /* Full height minus header and padding */
            overflow-y: auto; /* Add scrollbar if content overflows */
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
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-info"><a href="user1.php">
            <i class="fas fa-user-circle"></i>
            <?php echo $_SESSION['username']; ?></a>
        </div>

        <ul class="sidebar-nav">
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="my_bookings.php"><i class="fas fa-list"></i> My Bookings</a></li>
            <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php include 'header.php'; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Palliative Requests -->
        <div class="card">
            <h2>Palliative Requests</h2>
            <?php if ($palliative && $palliative->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
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
                                <td>#<?php echo htmlspecialchars($booking['palliativeid']); ?></td>
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
                                        <button class="btn" onclick="proceedToPayment(<?php echo (int)$booking['palliativeid']; ?>)">
                                            Pay Now
                                        </button>
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
        function proceedToPayment(palliativeId) {
            if (confirm('Do you want to proceed to payment for this completed service?')) {
                window.location.href = 'payment.php?palliative_id=' + palliativeId;
            }
        }

        // Refresh the page every 30 seconds to update status
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>