<?php
session_start();
require_once '../config/dbconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'You must log in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$car_id = $_POST['car_id'] ?? null;
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;

if(!$car_id || !$start_date || !$end_date){
    echo json_encode(['status'=>'error','message'=>'Missing booking details']);
    exit;
}

// Fetch the car
$stmt = $conn->prepare("SELECT * FROM cars WHERE id=? AND type='available'");
$stmt->bind_param("i",$car_id);
$stmt->execute();
$result = $stmt->get_result();
if(!$result || $result->num_rows==0){
    echo json_encode(['status'=>'error','message'=>'Selected car is not available']);
    exit;
}
$car = $result->fetch_assoc();
$stmt->close();

// Calculate total days and price
$days = max(1, (strtotime($end_date)-strtotime($start_date))/(60*60*24));
$total_price = $days * $car['price_per_day'];

// Insert booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, car_id, start_date, end_date, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())");
$stmt->bind_param("iissd",$user_id,$car_id,$start_date,$end_date,$total_price);
if($stmt->execute()){
    echo json_encode([
        'status'=>'success',
        'message'=>'Booking confirmed!',
        'booking'=>[
            'car_name'=>$car['name'],
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'total_price'=>$total_price
        ]
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to save booking']);
}
$stmt->close();
$conn->close();
