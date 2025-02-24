<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $name = $_POST['Name'];
    $phone = $_POST['phone'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // Database connection
    $servername = "localhost";
    $db_username = "Admin";
    $db_password = "123456";
    $dbname = "groovin";

    // Create connection
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO tbl_user (Name, Phone, Username, Password, Email) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssss", $name, $phone, $username, $password, $email);

    // Execute the statement
    if ($stmt->execute()) {
        echo '<div style="color: green;">Registration successful! You can now <a href="login.php">login</a>.</div>';
    } else {
        echo '<div style="color: red;">Error: ' . $stmt->error . '</div>';
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
