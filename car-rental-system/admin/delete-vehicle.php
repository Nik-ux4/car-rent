<?php
session_start();
require_once '../config/dbconnect.php';

// Protect admin page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Get car ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: vehicles.php");
    exit;
}

// Delete car from DB
$stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Redirect back to vehicles page
header("Location: vehicles.php");
exit;
