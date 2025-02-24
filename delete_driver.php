<?php
session_start();
include 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo 'unauthorized';
    exit();
}

// Handle driver deletion (status update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $driverId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($driverId) {
        $sql = "UPDATE tbl_user SET status = 'inactive' WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $driverId);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'failed';
        }
        $stmt->close();
    } else {
        echo 'invalid_id';
    }
} else {
    echo 'no_id_provided';
}

$conn->close();
?>