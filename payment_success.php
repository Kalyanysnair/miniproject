<?php
// payment_success.php
session_start();
require 'connect.php';

// Debug logging
error_log("Payment Success: Received parameters - " . print_r($_GET, true));

// Clear any existing payment status messages
unset($_SESSION['payment_failed']);

if (!isset($_SESSION['user_id']) || !isset($_GET['payment_id']) || !isset($_GET['booking_id']) || !isset($_GET['booking_type'])) {
    error_log("Payment Success: Missing required parameters");
    die("Invalid request - Missing parameters");
}

$razorpay_payment_id = $_GET['payment_id'];
$request_id = $_GET['booking_id'];
$request_type = $_GET['booking_type'];
$userid = $_SESSION['user_id'];

try {
    // Begin transaction
    $conn->begin_transaction();

    // First check if payment already exists
    $check_payment = "SELECT payment_id, amount FROM tbl_payments 
                     WHERE request_type = ? AND request_id = ? AND userid = ?";
    $stmt = $conn->prepare($check_payment);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("sii", $request_type, $request_id, $userid);
    $stmt->execute();
    $payment_result = $stmt->get_result();
    
    if ($payment_result->num_rows > 0) {
        // Payment already recorded
        $payment_data = $payment_result->fetch_assoc();
        $_SESSION['payment_success'] = true;
        $_SESSION['payment_amount'] = $payment_data['amount'];
        header("Location: status.php?payment=success");
        exit();
    }

    // Get booking details and amount
    switch ($request_type) {
        case 'emergency':
            $query = "SELECT ambulance_type, amount FROM tbl_emergency 
                     WHERE request_id = ? AND (userid = ? OR userid IS NULL) AND status = 'Completed'";
            $table = 'tbl_emergency';
            $id_field = 'request_id';
            break;
        case 'prebooking':
            $query = "SELECT ambulance_type, amount FROM tbl_prebooking 
                     WHERE prebookingid = ? AND userid = ? AND status = 'Completed'";
            $table = 'tbl_prebooking';
            $id_field = 'prebookingid';
            break;
        case 'palliative':
            $query = "SELECT ambulance_type, amount FROM tbl_palliative 
                     WHERE palliativeid = ? AND userid = ? AND status = 'Completed'";
            $table = 'tbl_palliative';
            $id_field = 'palliativeid';
            break;
        default:
            throw new Exception("Invalid booking type");
    }

    // Get booking details
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("ii", $request_id, $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();

    if (!$booking) {
        throw new Exception("Booking not found or not in Completed status");
    }

    // Use amount from database if available, otherwise calculate
    $amount = $booking['amount'];
    if (!$amount) {
        switch ($booking['ambulance_type']) {
            case 'Basic':
                $amount = 1500;
                break;
            case 'Advanced':
                $amount = 2500;
                break;
            case 'Palliative Care':
            case 'palliative':
                $amount = 3000;
                break;
            default:
                $amount = 2000;
        }
    }

    // Update booking status and payment status
    $update_query = "UPDATE $table 
                    SET payment_status = 'Paid',
                        amount = ? 
                    WHERE $id_field = ? AND (userid = ? OR userid IS NULL)";
    $stmt = $conn->prepare($update_query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("dii", $amount, $request_id, $userid);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to update booking status");
    }

    // Insert payment record
    $insert_query = "INSERT INTO tbl_payments (razorpay_payment_id, request_type, request_id, userid, amount, payment_status) 
                     VALUES (?, ?, ?, ?, ?, 'completed')";
    $stmt = $conn->prepare($insert_query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("ssidi", $razorpay_payment_id, $request_type, $request_id, $userid, $amount);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to record payment");
    }

    // Commit transaction
    $conn->commit();

    // Set success message
    $_SESSION['payment_success'] = true;
    $_SESSION['payment_amount'] = $amount;
    
    // Redirect to status page without query parameters
    header("Location: status.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("Payment recording failed: " . $e->getMessage());
    $_SESSION['payment_failed'] = true;
    header("Location: status.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/road.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            width: 90%;
            max-width: 450px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            padding: 25px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        
        p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully.</p>
        <p>Thank you for using our service.</p>
        <a href="status.php" class="button">View Booking Status</a>
    </div>
</body>
</html>