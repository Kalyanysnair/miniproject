<?php
// functions.php
function isLoggedIn() {
    return isset($_SESSION['userid']) && !empty($_SESSION['userid']);
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>

