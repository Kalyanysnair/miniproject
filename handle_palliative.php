<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'connect.php';

// Ensure user is logged in and is a driver
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "driver") {
    header('Location: login.php');
    exit();
}

$request_data = null;
$error_message = '';
$success_message = '';

// Check if form is submitted with correct parameters
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["request_id"]) && isset($_POST["request_type"]) && $_POST["request_type"] === "palliative") {
    $palliative_id = filter_var($_POST["request_id"], FILTER_VALIDATE_INT);
    $driver_id = $_SESSION["user_id"]; // Get the driver's user ID from session

    if ($palliative_id === false || $palliative_id === 0) {
        $error_message = "Invalid request ID.";
    } else {
        try {
            $mysqli->begin_transaction();

            // First check if the request is still pending
            $check_stmt = $mysqli->prepare("SELECT status FROM tbl_palliative WHERE palliativeid = ?");
            if ($check_stmt === false) {
                throw new Exception("Failed to prepare check statement: " . $mysqli->error);
            }

            $check_stmt->bind_param("i", $palliative_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $current_status = $check_result->fetch_assoc();

            if ($current_status && $current_status['status'] === 'Pending') {
                // Update request status and set driver_id
                $update_stmt = $mysqli->prepare("
                    UPDATE tbl_palliative 
                    SET status = 'Approved', driver_id = ?
                    WHERE palliativeid = ? AND status = 'Pending'
                ");
                
                if ($update_stmt === false) {
                    throw new Exception("Failed to prepare update statement: " . $mysqli->error);
                }

                $update_stmt->bind_param("ii", $driver_id, $palliative_id);
                $update_stmt->execute();

                if ($update_stmt->affected_rows > 0) {
                    // Fetch request details for email
                    $fetch_stmt = $mysqli->prepare("
                        SELECT 
                            u.username AS patient_name, 
                            u.phoneno AS contact_phone, 
                            p.address AS pickup_location,
                            p.created_at, 
                            u.email 
                        FROM tbl_palliative p 
                        LEFT JOIN tbl_user u ON p.userid = u.userid 
                        WHERE p.palliativeid = ?
                    ");
                    
                    if ($fetch_stmt === false) {
                        throw new Exception("Failed to prepare fetch statement: " . $mysqli->error);
                    }

                    $fetch_stmt->bind_param("i", $palliative_id);
                    $fetch_stmt->execute();
                    $result = $fetch_stmt->get_result();

                    if ($result->num_rows > 0) {
                        $request_data = $result->fetch_assoc();

                        // Send Email Notification
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'kalyanys2004@gmail.com';
                            $mail->Password = 'ooqs zxti mult tlcb';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            $mail->setFrom('kalyanys2004@gmail.com', 'SWIFTAID');
                            $mail->addAddress($request_data['email'], $request_data['patient_name']);

                            $mail->isHTML(true);
                            $mail->Subject = "Palliative Request Accepted - SWIFTAID";
                            $mail->Body = "
                                <html>
                                <body style='font-family: Arial, sans-serif;'>
                                    <h2>Palliative Request Confirmation</h2>
                                    <p>Hello {$request_data['patient_name']},</p>
                                    <p>Your palliative care request has been accepted.</p>
                                    <p><strong>Pickup Location:</strong> {$request_data['pickup_location']}</p>
                                    <p>A driver will assist you shortly.</p>
                                    <br>
                                    <p>Best Regards,<br>SWIFTAID Team</p>
                                </body>
                                </html>
                            ";

                            $mail->send();
                            $success_message = "Request accepted successfully. Confirmation email sent to patient.";
                        } catch (Exception $e) {
                            error_log("Email Error: " . $mail->ErrorInfo);
                            $success_message = "Request accepted successfully, but email notification failed.";
                        }
                    }
                } else {
                    $error_message = "Failed to update request status. It may have already been accepted.";
                }
            } else {
                $error_message = "This request is no longer available or has already been accepted.";
            }

            $mysqli->commit();
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Error in handle_palliative.php: " . $e->getMessage());
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
} else {
    $error_message = "Invalid request. Please try again.";
}
?>

<!-- Rest of your HTML code remains the same -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palliative Request Details - SWIFTAID</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            padding-top: 80px; /* Add padding for fixed header */
        }
        .container-box {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            margin: 20px auto;
        }
        .details-table {
            width: 100%;
            margin-top: 20px;
        }
        .details-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }
            .container-box {
                width: 95%;
                padding: 15px;
                margin: 10px;
            }
        }
        .dashboard-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .dashboard-btn:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }
        .btn-container {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
        <a href="index.html" class="logo d-flex align-items-center me-auto">
            <img src="assets/img/SWIFTAID2.png" alt="SWIFTAID Logo" style="height: 70px; margin-right: 10px;">
            <h1 class="sitename">SWIFTAID</h1>
        </a>
        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="index.html#hero">Home</a></li>
                <li><a href="index.html#about">About</a></li>
                <li><a href="index.html#services">Services</a></li>
                <li><a href="index.html#ambulanceservice">Ambulance Services</a></li>
                <li><a href="index.html#contact">Contact</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            </ul>
        </nav>
        <a class="btn-getstarted" href="emergency.php">Emergency Booking</a>
    </div>
</header>

    <div class="container-box">
        <h2 class="text-center mb-4">Palliative Request Details</h2>
        
        <?php if (isset($error_message) && $error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message) && $success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($request_data) && $request_data): ?>
            <table class="details-table">
                <tr>
                    <td><strong>Patient Name:</strong></td>
                    <td><?php echo htmlspecialchars($request_data['patient_name'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Contact Phone:</strong></td>
                    <td><?php echo htmlspecialchars($request_data['contact_phone'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Pickup Location:</strong></td>
                    <td><?php echo htmlspecialchars($request_data['pickup_location'] ?? '-'); ?></td>
                </tr>
                <tr>
                        <td><strong>Request Time<strong></td>
                        <td><?php echo htmlspecialchars($request_data['created_at'] ?? '-'); ?></td>
                    </tr>
            </table>
        <?php else: ?>
            <p class="text-center">No request details available.</p>
        <?php endif; ?>
        <div class="btn-container">
            <a href="driver.php" class="dashboard-btn">Return to Dashboard</a>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile navigation toggle
        const mobileNavShow = document.querySelector('.mobile-nav-show');
        const mobileNavHide = document.querySelector('.mobile-nav-hide');
        const navbar = document.querySelector('#navbar');

        if (mobileNavShow && mobileNavHide && navbar) {
            mobileNavShow.addEventListener('click', function() {
                navbar.classList.add('navbar-mobile');
                mobileNavShow.classList.add('d-none');
                mobileNavHide.classList.remove('d-none');
            });

            mobileNavHide.addEventListener('click', function() {
                navbar.classList.remove('navbar-mobile');
                mobileNavShow.classList.remove('d-none');
                mobileNavHide.classList.add('d-none');
            });
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== "#" && href.startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    });
    </script>
</body>
</html>