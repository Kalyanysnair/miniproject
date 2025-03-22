<?php
session_start();
require 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if request parameters are set
if (!isset($_POST['request_id']) || !isset($_POST['booking_type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$request_id = (int)$_POST['request_id'];
$booking_type = $_POST['booking_type'];
$user_id = $_SESSION['user_id'];

try {
    // Determine which table to update based on booking type
    switch ($booking_type) {
        case 'emergency':
            $table = 'tbl_emergency';
            $id_field = 'request_id';
            break;
        case 'prebooking':
            $table = 'tbl_prebooking';
            $id_field = 'prebookingid';
            break;
        case 'palliative':
            $table = 'tbl_palliative';
            $id_field = 'palliativeid';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid booking type']);
            exit;
    }

    // First, verify that the booking belongs to the logged-in user
    $verify_query = "SELECT userid FROM $table WHERE $id_field = ? AND status = 'Pending'";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    // For emergency bookings, the userid might be NULL
    if ($booking_type === 'emergency' && $booking['userid'] === NULL) {
        // Allow cancellation for emergency bookings with NULL userid
    } elseif ($booking['userid'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to cancel this booking']);
        exit;
    }
    
    // Update the booking status to 'Cancelled'
    $update_query = "UPDATE $table SET status = 'Cancelled' WHERE $id_field = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $request_id);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 