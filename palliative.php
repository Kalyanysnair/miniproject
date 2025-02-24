<?php
session_start();
include 'connect.php';

// Create table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS tbl_palliative (
    palliativeid INT AUTO_INCREMENT PRIMARY KEY,
    userid INT(6) UNSIGNED NOT NULL,
    comments TEXT,
    additional_requirements TEXT,
    address TEXT NOT NULL,
    medical_condition TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userid) REFERENCES tbl_user(userid),
    ambulance_type ENUM('palliative') NOT NULL DEFAULT 'palliative',
    INDEX idx_status (status),
    INDEX idx_userid (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
#setting of ambulance type is missiing
if (!$conn->query($create_table_query)) {
    die("Error creating table: " . $conn->error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$query = "SELECT userid, username, phoneno, email FROM tbl_user WHERE userid = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Error preparing query: " . $conn->error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comments = $_POST['comments'];
    $requirements = $_POST['requirements'];
    $address = $_POST['address'];
    $medical_condition = $_POST['medical_condition'];
    
    $insert_query = "INSERT INTO tbl_palliative (
        userid, comments, additional_requirements, address, 
        medical_condition, status
    ) VALUES (?, ?, ?, ?, ?, 'Pending')";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issss", 
        $user_id, $comments, $requirements, $address, 
        $medical_condition
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Palliative care request submitted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error submitting request. Please try again.";
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();

    // Refresh page to display message
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palliative Care Request - SwiftAid</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body {
            background-image: url('assets/assets/img//template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding-top: 80px;
        }

        .form-container {
            background-color: rgba(184, 180, 180, 0.46);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(5, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 800px;
        }

        .alert {
            margin-bottom: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px; /* Adjust spacing */
        }
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
        }
        .form-group {
            margin-bottom: 1rem;
        }


        .btn-back {
            background-color:rgb(50, 117, 69);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-back:hover {
            background-color:rgb(0, 179, 66);
        }
        .btn-submit {
        background-color:rgb(14, 192, 41); /* Breen color */
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        background-color: #588F63; /* Slightly darker breen color on hover */
        transform: translateY(-2px);
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

<div class="container">
    <div class="form-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        <h1 style="color:brown">Palliative Care Request</h1>
        <form method="POST" action="">
    <div class="row">
        <div class="col-md-6 form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-control readonly-field" 
                   value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control readonly-field" 
                   value="<?php echo htmlspecialchars($user_data['phoneno'] ?? ''); ?>" readonly>
        </div>
    </div>

    <div class="row">
    <div class="col-md-6 form-group">
            <label class="form-label">Additional Requirements</label>
            <textarea name="requirements" class="form-control" 
                      placeholder="Any special equipment, dietary requirements, or other specific needs..."></textarea>
        </div>
        <div class="col-md-6 form-group">
            <label class="form-label">Medical Condition</label>
            <textarea name="medical_condition" class="form-control" required 
                      placeholder="Please describe the medical condition and any specific symptoms..."></textarea>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label class="form-label">Additional Comments</label>
            <textarea name="comments" class="form-control" 
                      placeholder="Any specific requirements or concerns..."></textarea>
        </div>
        
        <div class="col-md-6 form-group">
            <label class="form-label">Complete Address</label>
            <textarea name="address" class="form-control" required
            placeholder="Please enter your complete address..."></textarea>
            
        </div>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-submit">Submit Request</button>
    </div>
</form>

        <div class="text-center mt-3">
            <a href="javascript:history.back()" class="btn-back">Back</a>
        </div>
    </div>
</div>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

        
