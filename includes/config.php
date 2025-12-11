<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_NAME', 'healthdesk');


function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}


session_start();


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}


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
    
}


function calculateInventoryStatus($quantity) {
    if ($quantity <= 0) {
        return 'Critical';
    } elseif ($quantity <= 10) {
        return 'Low Stock';
    } else {
        return 'In Stock';
    }
}
?>
