<?php
session_start();
include 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "unauthorized";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['driver_id'])) {
    $driver_id = $_POST['driver_id'];
    
    // Get the user ID associated with this driver
    $stmt = $conn->prepare("SELECT userid FROM tbl_driver WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['userid'];
        
        // Update the user status to inactive
        $updateStmt = $conn->prepare("UPDATE tbl_user SET status = 'inactive' WHERE userid = ?");
        $updateStmt->bind_param("i", $user_id);
        
        if ($updateStmt->execute()) {
            echo "success";
        } else {
            echo "Error updating status: " . $conn->error;
        }
        
        $updateStmt->close();
    } else {
        echo "Driver not found";
    }
    
    $stmt->close();
} else {
    echo "Invalid request";
}

$conn->close();
?>