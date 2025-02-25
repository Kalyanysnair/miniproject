<?php
session_start();
include 'connect.php';
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure only admin can add drivers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $license_no = trim($_POST['license_number']);
    $service_area = trim($_POST['service_area']);
    $dname = trim($_POST['dname']);
    $vehicle_no = trim($_POST['vehicle_no']);
    $ambulance_type = trim($_POST['ambulance_type']);
    
    if (empty($contact) || empty($email) || empty($license_no) || empty($service_area) || empty($dname) || empty($vehicle_no) || empty($ambulance_type)) {
        $error = "All fields are required.";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Auto-generate a password
            $auto_password = bin2hex(random_bytes(4)); // Generates an 8-character random password
            $hashed_password = password_hash($auto_password, PASSWORD_DEFAULT);

            // Insert into tbl_user
            $stmt = $conn->prepare("INSERT INTO tbl_user (username, password, email, phoneno, role, status, created_at) VALUES (?, ?, ?, ?, 'driver', 'active', NOW())");
            $stmt->bind_param("ssss", $dname, $hashed_password, $email, $contact);

            if (!$stmt->execute()) {
                throw new Exception("Error adding driver to tbl_user: " . $stmt->error);
            }

            // Get the last inserted userid
            $userid = $conn->insert_id;

            // Insert into tbl_driver
            $stmt = $conn->prepare("INSERT INTO tbl_driver (userid, lisenceno, service_area, vehicle_no, ambulance_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $userid, $license_no, $service_area, $vehicle_no, $ambulance_type);

            if (!$stmt->execute()) {
                throw new Exception("Error adding driver to tbl_driver: " . $stmt->error);
            }

            // Send email to the driver
            try {
                $mail = new PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username = 'kalyanys2004@gmail.com'; // Your Gmail ID
                $mail->Password = 'ooqs zxti mult tlcb'; // Use App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
            
                // Recipients
                $mail->setFrom('your-email@gmail.com', 'SWIFTAID');
                $mail->addAddress($email, $dname);
            
                // Content
                $mail->isHTML(true);
                $mail->Subject = "Your SWIFTAID Account Credentials";
                
                // Email body
                $htmlBody = "
                <html>
                <body>
                    <h2>Welcome to SWIFTAID!</h2>
                    <p>Your login credentials are:</p>
                    <p><strong>Username:</strong> {$username}</p>
                    <p><strong>Password:</strong> {$auto_password}</p>
                    <p style='color: red;'><strong>Important:</strong> Please change your password after logging in.</p>
                    <p>Thank you for joining our team!</p>
                </body>
                </html>";
                
                $mail->Body    = $htmlBody;
                $mail->AltBody = "Welcome to SWIFTAID!\n\nUsername: {$email}\nPassword: {$auto_password}\n\nPlease change your password after logging in.";
            
                $mail->send();
                $success = "Driver added successfully! An email with login credentials has been sent.";
            } catch (Exception $e) {
                throw new Exception("Failed to send email: " . $mail->ErrorInfo);
            }

            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$conn->close();
?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Ambulance Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: url('assets/assets/img/template/Groovin/hero-carousel/road.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .navbar {
            background: rgb(53, 56, 58);
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }
        .container {
            max-width: 720px;
            background: rgba(187, 185, 185, 0.85);
            padding: 40px;
            margin: 40px auto;
            border-radius: 10px;
            box-shadow: 2px 2px 15px rgba(0, 0, 0, 0.2);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: rgba(25, 195, 31, 0.85);
        }
       
        input, select {
            width: 90%;
            padding: 12px;
            padding-left: 40px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .input-icon {
            position: absolute;
            top: 38px;
            left: 10px;
            font-size: 18px;
            color: gray;
        }
        button {
            width: 100%;
            padding: 12px;
            background: rgb(33, 204, 70);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: rgb(34, 170, 86);
        }
        .success {
            color: green;
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
       

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;

        }
        
        .form-row {
            display: flex;
            gap: 30px;
            flex-wrap: nowrap; 
        }
        .form-row .form-group {
            flex: 1;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        
    </style>
</head>
<body>

<header style="background-color: #343a40; padding: 10px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; width: 98%; padding: 0 20px;">
        <!-- Navbar Title -->
        <div class="navbar" style="color: white; font-size: 18px;">
            Admin Panel - Add Ambulance Driver
        </div>

        <!-- Back Button -->
        <a href="admin.php" style="text-decoration: none; color: white; background-color:rgb(33, 208, 92); padding: 10px 20px; border-radius: 5px; font-size: 16px;">
            Back
        </a>
    </div>
</header>


<div class="container">
    <h2>Add Ambulance Driver</h2>
    
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" action="">
        
    <style>
       
       
    </style>


<div class="form-container">
    <form method="POST" action="">
        
        <div class="form-row">
            <div class="form-group">
                <label>Driver Name</label>
                <input type="text" name="dname" required>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>License Number</label>
                <input type="text" name="license_number" required>
            </div>
        </div>

        <div class="form-row">
        <div class="form-group">
                <label>Service Area</label>
                <input type="text" name="service_area" required>
            </div>
            <div class="form-group">
                <label>Ambulance Vehicle Number</label>
                <input type="text" name="vehicle_no" required>
            </div>
        </div>

        <div class="form-row">
            
            <div class="form-group">
                <label>Ambulance Type</label>
                <select name="ambulance_type" required>
                            <option value="">Select Ambulance Type</option>
                            <option value="Basic">Basic Ambulance Service</option>
                            <option value="Advanced">Advanced Life Support </option>
                            <option value="Neonatal">Critical Care Ambulance</option>
                            <option value="Neonatal">Neonatal Ambulance</option>
                            <option value="Bariatric">Bariatric Ambulance</option> 
                            <option value="Palliative">Palliative Care</option>
                            <option value="Mortuary">Mortuary Ambulance</option> 
                </select>
            </div>
            <div class="form-group">
                <label>Availability Status</label>
                <select name="status" required>
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Role</label>
            <input type="text" name="role" value="Driver" readonly>
        </div>

        <button type="submit"><i class="fa fa-plus"></i> Add Driver</button>
    </form>
</div>

</body>
</html>


