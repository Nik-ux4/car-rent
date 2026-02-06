<?php
session_start();
require_once '../config/dbconnect.php';

$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id) die('Invalid booking.');

$stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=? AND status='pending_payment'");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->close();

$_SESSION['success'] = "Payment received! Your booking is confirmed.";
header("Location: dashboard.php");
exit;
?>
