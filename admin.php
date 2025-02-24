<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
$driver_count = isset($_SESSION['driver_count']) ? $_SESSION['driver_count'] : 0;
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "groovin";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User update handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userid = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phoneno', FILTER_SANITIZE_STRING);

    if ($userid && $username && $email && $phone) {
        $sql = "UPDATE tbl_user SET username = ?, email = ?, phoneno = ? WHERE userid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $email, $phone, $userid);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'User updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update user';
        }
        $stmt->close();
    }
}

// Fetch users and count
$sql = "SELECT userid, username, email, phoneno, role FROM tbl_user";
$result = $conn->query($sql);
$total_users = $result ? $result->num_rows : 0;

$query = "SELECT r.review_id, u.username, r.message, r.rating, r.created_at 
          FROM tbl_review r
          JOIN tbl_user u ON r.user_id = u.userid
          ORDER BY r.created_at DESC";

$result = $conn->query($query);
// Count the number of reviews
$review_count = $result->num_rows;


$query = "SELECT COUNT(*) AS driver_count FROM tbl_driver";
$result = $conn->query($query);
$driver_count = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $driver_count = $row['driver_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SwiftAid Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #008374;
            --secondary-color: #f1f4f3;
            --text-color: #333;
        }
        body {
            background-color: var(--secondary-color);
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1;
            z-index: -1;
        }
        .admin-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            position: relative;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.8);
            padding: 15px;
            border-radius: 10px;
        }
        :root {
    --primary-color: #008374;
    --secondary-color: #f1f4f3;
    --text-color: #333;
}

body {
    background-color: var(--secondary-color);
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
}

.background-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
     background-image: url('assets/assets/img/template/Groovin/hero-carousel/road.jpg');  
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0.8;
    z-index: -1;
}

.admin-container {
    max-width: 1200px;
    margin: 50px auto;
    padding: 20px;
    position: relative;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: rgba(255, 255, 255, 0.5); /* Semi-transparent background */
    padding: 15px;
    border-radius: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background:rgba(113, 110, 110, 0.51); /* Semi-transparent background */
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.user-management-card {
    background: rgba(34, 22, 22, 0.46); /* Semi-transparent background */
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(247, 242, 242, 0.73);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .user-management-card {
            background:rgba(255, 251, 251, 0.81);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        .user-table th, .user-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        #backgroundPreview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="background-overlay" id="backgroundOverlay"></div>
    
    <div class="admin-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
           
        </div>

        <div class="stats-grid">
        <a href="UserManagement.php" style="text-decoration: none; color: inherit;">
    <div class="stat-card">
        <h3>User Management</h3>
        <p class="display-4"><?php echo $total_users; ?></p>
    </div>
</a>
<a href="add_driver.php" style="text-decoration: none; color: inherit;">
            <div class="stat-card">
                <h3>Add Drivers</h3>
                <p class="display-4"><?php echo $driver_count; ?></p>
            </div>
    </a>
<a href="driver_detail.php" style="text-decoration: none; color: inherit;">
            <div class="stat-card">
                <h3>Driver Details</h3>
                <p class="display-4"><?php echo $driver_count; ?></p>
            </div>
    </a>
   
            
        </div>
        <a href="admin_review.php" style="text-decoration: none; color: inherit;">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Feedback</h3>
                <p class="display-4"><?php echo $review_count; ?></p>
            </div>
    </a>
            <div class="stat-card">
                <h3>Payment</h3>
                <p class="display-4">0</p>
            </div>
            <div class="stat-card">
                <h3>Feedback</h3>
                <p class="display-4">0</p>
            </div>
        </div>
        <div class="stats-grid">
        <div class="stat-card">
                <h3>Ambulance</h3>
                <p class="display-4">0</p>
            </div>
            <div class="stat-card">
                <h3>Payment</h3>
                <p class="display-4">0</p>
            </div>
            <div class="stat-card">
                <h3>Feedback</h3>
                <p class="display-4">0</p>
            </div>
        </div>

        
</body>
</html>
<?php
$conn->close();
?>