<?php
session_start();
require 'connect.php';
include 'header.php'; 

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    $_SESSION['error_message'] = "Please log in to proceed with payment.";
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$ambulance_type = "";
$amount = "";
$booking_type = "";

// Fetch booking details (latest booking from any table)
$query = "
    (SELECT 'Emergency' AS booking_type, request_id AS booking_id, ambulance_type, amount FROM tbl_emergency WHERE userid = ? ORDER BY request_id DESC LIMIT 1)
    UNION
    (SELECT 'Prebooking' AS booking_type, prebookingid AS booking_id, ambulance_type, amount FROM tbl_prebooking WHERE userid = ? ORDER BY prebookingid DESC LIMIT 1)
    UNION
    (SELECT 'Palliative' AS booking_type, palliativeid AS booking_id, ambulance_type, amount FROM tbl_palliative WHERE userid = ? ORDER BY palliativeid DESC LIMIT 1)
    ORDER BY booking_id DESC LIMIT 1;
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $userid, $userid, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $booking_type = $row['booking_type'];
    $ambulance_type = $row['ambulance_type'];
    $amount = $row['amount'];
} else {
    $_SESSION['error_message'] = "No active booking found.";
    header("Location: ambulance_booking.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('background.jpg');
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .payment-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
        }
        h2 {
            color: #d9534f;
        }
        .error {
            color: red;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        select, input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 15px;
            padding: 10px;
            width: 100%;
            background-color: #d9534f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2>Secure Payment</h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <p class="error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
        <?php endif; ?>

        <p><strong>Ambulance Type:</strong> <?php echo $ambulance_type; ?></p>
        <p><strong>Amount:</strong> $<?php echo number_format($amount, 2); ?></p>

        <form action="process_payment.php" method="POST">
            <input type="hidden" name="userid" value="<?php echo $userid; ?>">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">
            <input type="hidden" name="booking_type" value="<?php echo $booking_type; ?>">

            <label for="payment_method">Select Payment Method:</label>
            <select name="payment_method" required>
                <option value="">-- Choose Payment Method --</option>
                <option value="credit_card">Credit Card</option>
                <option value="debit_card">Debit Card</option>
                <option value="paypal">PayPal</option>
                <option value="upi">UPI</option>
                <option value="net_banking">Net Banking</option>
            </select>

            <button type="submit">Proceed to Payment</button>
        </form>
    </div>
</body>
</html>
