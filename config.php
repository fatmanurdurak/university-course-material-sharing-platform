<?php
// config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); // start session so we can remember logged-in users

$host = "localhost";
$db   = "course_platform";
$user = "root";      // XAMPP default username
$pass = "";          // XAMPP default password is empty

// create connection object
$conn = new mysqli($host, $user, $pass, $db);

// check if something went wrong
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// helper: call this in any page that requires login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
