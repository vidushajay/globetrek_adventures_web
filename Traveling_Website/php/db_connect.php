<?php
// db_connect.php
// Central place for DB credentials — every other PHP file requires this one.

$db_host = "localhost";
$db_user = "root";      // default XAMPP username
$db_pass = "";          // default XAMPP password is empty
$db_name = "globetrek_adventures";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
