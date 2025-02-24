<?php
session_start();
require_once 'connect.php'; // Ensure this file contains your database connection setup

// Check if the logged-in user is a driver or an admin trying to view a driver's profile
$userid = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0; // Logged-in user ID
//$role = isset($_SESSION['role']) ? $_SESSION['role'] : ''; // User role

// Fetch driver_id from the URL if provided (admin use case)
$driver_userid = isset($_GET['userid']) ? intval($_GET['userid']) : $userid;

// Validate user access
// if ($role !== 'admin' && $driver_userid !== $userid) {
//     echo "<p>Unauthorized access.</p>";
//     exit;
// }

// Fetch driver details by joining tbl_user and tbl_driver
$query = "SELECT 
            u.username, u.email, u.phoneno, u.status, 
            d.lisenceno, d.service_area, d.vehicle_no, d.ambulance_type, d.created_at
          FROM tbl_user u
          JOIN tbl_driver d ON u.userid = d.userid
          WHERE u.userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $driver_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $driver = $result->fetch_assoc();
} else {
    echo "<p>No driver found with the given user ID.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        p {
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h1>Driver Profile</h1>
        <p><span class="label">Username:</span> <?php echo htmlspecialchars($driver['username']); ?></p>
        <p><span class="label">Email:</span> <?php echo htmlspecialchars($driver['email']); ?></p>
        <p><span class="label">Phone Number:</span> <?php echo htmlspecialchars($driver['phoneno']); ?></p>
        <p><span class="label">Status:</span> <?php echo htmlspecialchars($driver['status']); ?></p>
        <p><span class="label">License Number:</span> <?php echo htmlspecialchars($driver['lisenceno']); ?></p>
        <p><span class="label">Service Area:</span> <?php echo htmlspecialchars($driver['service_area']); ?></p>
        <p><span class="label">Vehicle Number:</span> <?php echo htmlspecialchars($driver['vehicle_no']); ?></p>
        <p><span class="label">Ambulance Type:</span> <?php echo htmlspecialchars($driver['ambulance_type']); ?></p>
        <p><span class="label">Created At:</span> <?php echo htmlspecialchars($driver['created_at']); ?></p>
    </div>
</body>
</html>
