<?php
// includes/db_connection.php
// This file establishes a connection to the MySQL database using MySQLi.

// --- 1. Database Configuration Constants ---
// You MUST change these values to match your local or hosted database settings.
// For APU projects, these might be your XAMPP/WAMP/MAMP settings.
define('DB_SERVER', 'localhost'); // Usually 'localhost' when developing locally
define('DB_USERNAME', 'root');    // Default for XAMPP/WAMP/MAMP is often 'root'
define('DB_PASSWORD', '');        // Default for XAMPP is empty string ''
define('DB_NAME', 'ecoquest'); // The name of the database you must create in phpMyAdmin

// --- 2. Establish Connection ---

// Create connection object
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Stop execution and display a developer-friendly error message if connection fails
    die("Aiyo, database connection failed liao: " . $conn->connect_error);
}

// Optional: Set character set to UTF-8 for better international character support
$conn->set_charset("utf8mb4");

// Now you can use the variable $conn to run database queries anywhere this file is included.
?>
