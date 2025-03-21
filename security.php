<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$errorMessage = '';
$successMessage = '';

// Include database connection
include 'connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $existingPassword = $_POST['existingPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Direct query to get user details and avoid prepare issues
    try {
        $username = $_SESSION['username'];
        
        // First get the user data directly
        $query = "SELECT * FROM tbl_user WHERE username = '$username'";
        $result = $conn->query($query);
        
        if ($result && $result->rowCount() > 0) {
            $user = $result->fetch(PDO::FETCH_ASSOC);
            
            // Verify existing password
            if (!password_verify($existingPassword, $user['password'])) {
                $errorMessage = "Existing password is incorrect.";
            } elseif ($newPassword === $existingPassword) {
                $errorMessage = "New password cannot be the same as the existing password.";
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = "New password and confirm password do not match.";
            } else {
                // Password validation passed, update the password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Direct update query
                $updateQuery = "UPDATE tbl_user SET password = '$hashedPassword' WHERE username = '$username'";
                if ($conn->exec($updateQuery)) {
                    $successMessage = "Password updated successfully.";
                } else {
                    $errorMessage = "Failed to update password. Please try again.";
                }
            }
        } else {
            $errorMessage = "User not found in database.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Page - Change Password</title>
    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-image: url('background.jpg'); /* Add your background image path here */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Transparent Container */
        .container {
            background: rgba(255, 255, 255, 0.9); /* Slightly less transparent for better readability */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }

        /* Header Styles */
        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }

        /* Button Styles */
        .btn {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Error and Success Messages */
        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        .success-message {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">Change Password</div>

        <!-- Form -->
        <form id="changePasswordForm" method="POST">
            <div class="form-group">
                <label for="existingPassword">Existing Password</label>
                <input type="password" id="existingPassword" name="existingPassword" required>
            </div>
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <input type="password" id="newPassword" name="newPassword" required>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>

        <!-- Error and Success Messages -->
        <div id="errorMessage" class="error-message">
            <?php echo $errorMessage; ?>
        </div>
        <div id="successMessage" class="success-message">
            <?php echo $successMessage; ?>
        </div>
    </div>

    <script>
        // JavaScript for live validations
        document.getElementById('changePasswordForm').addEventListener('submit', function (event) {
            const existingPassword = document.getElementById('existingPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            errorMessage.textContent = ""; // Clear previous error messages
            successMessage.textContent = ""; // Clear previous success messages

            // Password validation rules
            const minLength = 8;
            const hasUppercase = /[A-Z]/.test(newPassword);
            const hasLowercase = /[a-z]/.test(newPassword);
            const hasNumber = /[0-9]/.test(newPassword);
            const hasSpecialChar = /[!@#$%^&*]/.test(newPassword);

            // Validate new password
            if (newPassword === existingPassword) {
                errorMessage.textContent = "New password cannot be the same as the existing password.";
                event.preventDefault();
            } else if (newPassword !== confirmPassword) {
                errorMessage.textContent = "New password and confirm password do not match.";
                event.preventDefault();
            } else if (newPassword.length < minLength) {
                errorMessage.textContent = `Password must be at least ${minLength} characters long.`;
                event.preventDefault();
            } else if (!hasUppercase) {
                errorMessage.textContent = "Password must contain at least one uppercase letter.";
                event.preventDefault();
            } else if (!hasLowercase) {
                errorMessage.textContent = "Password must contain at least one lowercase letter.";
                event.preventDefault();
            } else if (!hasNumber) {
                errorMessage.textContent = "Password must contain at least one number.";
                event.preventDefault();
            } else if (!hasSpecialChar) {
                errorMessage.textContent = "Password must contain at least one special character (!@#$%^&*).";
                event.preventDefault();
            }
        });
    </script>
</body>
</html>