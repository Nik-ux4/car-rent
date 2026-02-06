<?php
// Database connection
$host = "localhost";     // XAMPP default
$username = "root";      // XAMPP default
$password = "";          // XAMPP default is empty
$database = "car_rental_system";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
