<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PostgreSQL database config
$host = "dpg-cvq1o649c44c73e2t260-a";
$port = "5432";
$dbname = "groovin";
$user = "groovin_user";
$password = "J9HwvYnI9oktSort0UzBIW9PvAjCdWCV";

// Build connection string
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";

// Connect to PostgreSQL
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>
