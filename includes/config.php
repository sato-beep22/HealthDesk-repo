<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password
define('DB_NAME', 'healthdesk');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Start session
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Function to redirect based on role
function redirectBasedOnRole() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }

    $role = getUserRole();
    if ($role === 'Staff') {
        header("Location: add_record.php");
        exit();
    }
    // Admin stays on dashboard
}
?>
