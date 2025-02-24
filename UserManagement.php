<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// First, check if the status column exists, if not create it
$check_column = "SHOW COLUMNS FROM tbl_user LIKE 'status'";
$result = $conn->query($check_column);
if ($result->num_rows === 0) {
    $add_column = "ALTER TABLE tbl_user ADD COLUMN status VARCHAR(20) DEFAULT 'active'";
    $conn->query($add_column);
}

// User update handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $userid = filter_input(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phoneno', FILTER_SANITIZE_STRING);

    if ($userid && $username && $email && $phone) {
        $sql = "UPDATE tbl_user SET username = ?, email = ?, phoneno = ? WHERE userid = ? AND status = 'active'";
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

// Fetch only active users
$sql = "SELECT userid, username, email, phoneno, role FROM tbl_user WHERE status = 'active' OR status IS NULL";
$result = $conn->query($sql);
$total_users = $result ? $result->num_rows : 0;

// Add error handling for the query
if (!$result) {
    $_SESSION['error'] = 'Error fetching users: ' . $conn->error;
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
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/road.jpg');
            background-size: cover;
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
        .user-management-card {
            background:rgba(215, 212, 212, 0.81);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin:30px 100px 100px 100px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
<div class="user-management-card">
    <div class="admin-container">
        <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; background-color:rgb(221, 223, 224); padding: 10px 20px;">
            <h1>Users</h1>
            <a href="admin.php" style="text-decoration: none; color: white; background-color:rgb(28, 179, 83); padding: 10px 20px; border-radius: 5px; font-size: 16px;">Back</a>
        </div>
        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $userid = $row['userid'] ?? '';
                        $username = $row['username'] ?? '';
                        $email = $row['email'] ?? '';
                        $phone = $row['phoneno'] ?? '';
                        $role = $row['role'] ?? '';

                        echo "<tr data-userid='" . htmlspecialchars($userid) . "'>";
                        echo "<td>" . htmlspecialchars($username) . "</td>";
                        echo "<td>" . htmlspecialchars($email) . "</td>";
                        echo "<td>" . htmlspecialchars($phone) . "</td>";
                        echo "<td>" . htmlspecialchars($role) . "</td>";
                        echo "<td>
                                <button class='btn btn-sm btn-danger delete-btn' data-userid='" . htmlspecialchars($userid) . "'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No users found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '.delete-btn', function() {
        const userId = $(this).data('userid');
        const row = $(this).closest('tr');
        
        if (confirm('Are you sure you want to deactivate this user?')) {
            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: { user_id: userId },
                success: function(response) {
                    response = response.trim();
                    switch(response) {
                        case 'success':
                            row.fadeOut(400, function() {
                                $(this).remove();
                            });
                            break;
                        case 'unauthorized':
                            alert('You are not authorized to perform this action');
                            break;
                        case 'connection_error':
                            alert('Database connection error');
                            break;
                        case 'invalid_id':
                            alert('Invalid user ID');
                            break;
                        case 'no_id_provided':
                            alert('No user ID provided');
                            break;
                        default:
                            alert('An error occurred while deactivating the user');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }
    });
});
</script>
</body>
</html>
<?php
$conn->close();
?>