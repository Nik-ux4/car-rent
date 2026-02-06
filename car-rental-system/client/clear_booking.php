<?php
session_start();
require_once '../config/dbconnect.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || !isset($_POST['booking_id'])){
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['user_id'];

// Update booking to cleared
$stmt = $conn->prepare("UPDATE bookings SET cleared=1 WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $booking_id, $user_id);
if($stmt->execute()){
    // Fetch booking HTML to append to history dynamically
    $stmt2 = $conn->prepare("
        SELECT b.id AS booking_id, b.start_date, b.end_date, b.status, c.name AS car_name,
        c.brand, c.type, c.image, c.price_per_day
        FROM bookings b
        JOIN cars c ON b.car_id=c.id
        WHERE b.id=? AND b.user_id=?
    ");
    $stmt2->bind_param("ii", $booking_id, $user_id);
    $stmt2->execute();
    $result = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    $days = max(1, ceil((strtotime($result['end_date']) - strtotime($result['start_date']))/86400));
    $totalPrice = $result['price_per_day']*$days;

    $html = '
    <div class="booking-card '.strtolower($result['status']).' cleared">
        <div class="booking-image">
            <img src="../'.htmlspecialchars($result['image']).'" alt="'.htmlspecialchars($result['car_name']).'">
        </div>
        <div class="booking-details">
            <h3>'.htmlspecialchars($result['car_name']).'</h3>
            <p class="booking-meta">'.htmlspecialchars($result['brand']).' • '.htmlspecialchars($result['type']).'</p>
            <p><strong>Rental Period:</strong> '.date('M d, Y', strtotime($result['start_date'])).' – '.date('M d, Y', strtotime($result['end_date'])).'</p>
            <p><strong>Total Price:</strong> ₱'.number_format($totalPrice,2).'</p>
            <p class="booking-status">'.statusBadge($result['status']).'</p>
        </div>
    </div>';

    echo json_encode(['status'=>'success','html'=>$html]);
    exit;
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to clear booking']);
    exit;
}

// Status badge helper
function statusBadge($status){
    $colors = ['pending'=>'badge-pending','confirmed'=>'badge-confirmed','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
    $icons  = ['pending'=>'⏳','confirmed'=>'✅','completed'=>'🏁','cancelled'=>'❌'];
    $class = $colors[strtolower($status)] ?? 'badge-completed';
    $icon = $icons[strtolower($status)] ?? '';
    return "<span class='$class'>$icon ".ucfirst($status)."</span>";
}
?>
