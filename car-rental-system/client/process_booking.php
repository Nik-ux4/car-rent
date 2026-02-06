<?php
session_start();
require_once '../config/dbconnect.php';

/* ===============================
   AUTH CHECK
================================ */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===============================
   INPUTS
================================ */
$car_id           = $_POST['car_id'] ?? null;
$start_date       = $_POST['start_date'] ?? null;
$end_date         = $_POST['end_date'] ?? null;
$payment_method   = strtolower($_POST['payment_method'] ?? '');
$reference_number = $_POST['reference_number'] ?? null;

/* ===============================
   VALIDATION
================================ */
if (!$car_id || !$start_date || !$end_date || !$payment_method) {
    die("Missing booking information.");
}

if (!in_array($payment_method, ['gcash', 'cash'])) {
    die("Invalid payment method.");
}

/* ===============================
   AVAILABILITY CHECK
================================ */
$stmt = $conn->prepare("
    SELECT start_date, end_date 
    FROM bookings
    WHERE car_id=? AND status IN ('pending','confirmed','reserved')
");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    if ($start_date <= $row['end_date'] && $end_date >= $row['start_date']) {
        die("Car is already booked on selected dates.");
    }
}
$stmt->close();

/* ===============================
   PRICE COMPUTATION
================================ */
$stmt = $conn->prepare("SELECT price_per_day FROM cars WHERE id=?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

$days = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
if ($days <= 0) die("Invalid date range.");

$total_price = $days * $car['price_per_day'];

/* ===============================
   RECEIPT UPLOAD (GCASH)
================================ */
$receipt_file = null;

if ($payment_method === 'gcash') {
    if (!isset($_FILES['payment_receipt']) || $_FILES['payment_receipt']['error'] !== 0) {
        die("GCash requires receipt upload.");
    }

    $upload_dir = "../uploads/receipts/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext = strtolower(pathinfo($_FILES['payment_receipt']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'])) {
        die("Invalid receipt format.");
    }

    $receipt_file = "RCPT_" . time() . "_" . $user_id . "." . $ext;

    if (!move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $upload_dir . $receipt_file)) {
        die("Receipt upload failed.");
    }
}

/* ===============================
   STATUS & PAYMENT LOGIC
================================ */
// Standardize statuses to lowercase for admin panel
if ($payment_method === 'cash') {
    $booking_status  = 'pending';  // always pending for admin panel
    $payment_status  = 'paid';
    $cleared         = 1; // cash auto-cleared
} else { // GCash
    $booking_status  = 'pending';
    $payment_status  = 'pending';
    $cleared         = 0; // needs admin verification
}

/* ===============================
   TRANSACTION
================================ */
$conn->begin_transaction();

try {
    // INSERT BOOKING
    $stmt = $conn->prepare("
        INSERT INTO bookings
        (user_id, car_id, start_date, end_date, status,
         total_price, created_at, cleared,
         reference_number, payment_receipt, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
    ");

    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param(
        "iisssdisss",
        $user_id,
        $car_id,
        $start_date,
        $end_date,
        $booking_status,
        $total_price,
        $cleared,
        $reference_number,
        $receipt_file,
        $payment_status
    );

    $stmt->execute();
    $booking_id = $stmt->insert_id;

    // INSERT PAYMENT
    $stmt = $conn->prepare("
        INSERT INTO payments
        (booking_id, amount, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param(
        "idss",
        $booking_id,
        $total_price,
        $payment_method,
        $payment_status
    );

    $stmt->execute();

    $conn->commit();

    header("Location: dashboard.php?success=Booking submitted successfully");
    exit;

} catch (Exception $e) {

    $conn->rollback();
    if ($receipt_file) {
        @unlink("../uploads/receipts/" . $receipt_file);
    }
    die("Booking failed: " . $e->getMessage());
}
