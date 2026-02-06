<?php
require_once '../config/dbconnect.php';

$car_id = $_POST['car_id'] ?? null;
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$speed = $_POST['speed'] ?? 0;

if (!$car_id || !$lat || !$lng) exit("Missing data");

// Save location
$stmt = $conn->prepare("
INSERT INTO car_locations (car_id, latitude, longitude, speed)
VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iddd", $car_id, $lat, $lng, $speed);
$stmt->execute();

echo "OK";
