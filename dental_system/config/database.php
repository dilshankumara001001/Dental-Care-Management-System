<?php
// ============================================
// Database Configuration
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dental_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Timezone
date_default_timezone_set('Asia/Colombo');

// Base URL
define('BASE_URL', 'http://localhost/dental_system/');
define('SITE_NAME', 'Dental Care System');

// Start session (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>