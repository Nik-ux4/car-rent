<?php
session_start();
require_once '../config/dbconnect.php';

// Admin check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

// Current timestamp
$current_time = date('Y-m-d H:i:s');

// Fetch active bookings with car info, user info, and latest GPS location
$sql = "
SELECT 
    b.id AS booking_id,
    b.car_id,
    b.reference_number,
    b.start_date,
    b.end_date,
    b.status AS booking_status,
    c.name AS car_name,
    c.brand AS car_brand,
    c.image AS car_image,
    u.fullname,
    l.latitude,
    l.longitude,
    l.speed
FROM bookings b
LEFT JOIN cars c ON b.car_id = c.id
LEFT JOIN users u ON b.user_id = u.id
LEFT JOIN (
    SELECT l1.*
    FROM live_locations l1
    INNER JOIN (
        SELECT car_id, MAX(created_at) AS max_created
        FROM live_locations
        GROUP BY car_id
    ) l2 ON l1.car_id = l2.car_id AND l1.created_at = l2.max_created
) l ON b.car_id = l.car_id
WHERE b.status IN ('pending','confirmed')
   OR (b.status='completed' AND '$current_time' BETWEEN b.start_date AND b.end_date)
ORDER BY b.start_date ASC
";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
