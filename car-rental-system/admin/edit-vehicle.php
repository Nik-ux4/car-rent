<?php
session_start();
require_once '../config/dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: vehicles.php");
    exit;
}

// Fetch car data
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    header("Location: vehicles.php");
    exit;
}

// Handle Update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_car'])) {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $price = trim($_POST['price']);
    $type = $_POST['type'] ?? 'available';
    $imgPath = $car['image'];

    if (!empty($name) && !empty($brand) && !empty($price)) {
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "../uploads/cars/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imgPath = "uploads/cars/$fileName";
            }
        }

        $stmt = $conn->prepare("UPDATE cars SET name = ?, brand = ?, price_per_day = ?, type = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssdssi", $name, $brand, $price, $type, $imgPath, $id);
        if ($stmt->execute()) {
            $message = "Vehicle updated successfully!";
            header("Location: vehicles.php");
            exit;
        } else {
            $message = "Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "All fields are required!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Vehicle</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
.container { max-width: 500px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);}
input, select { width: 100%; border: 1px solid #ccc; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
button { background-color: #f59e0b; color: white; padding: 10px; border-radius: 8px; width: 100%; font-weight: bold; }
button:hover { background-color: #d97706; }
</style>
</head>
<body>

<div class="container">
    <h2 class="text-xl font-bold mb-4">Edit Vehicle</h2>

    <?php if(!empty($message)): ?>
        <p class="text-red-500 mb-4"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Car Name" value="<?= htmlspecialchars($car['name']) ?>" required>
        <select name="brand" required>
            <option value="Sedan" <?= $car['brand'] === 'Sedan' ? 'selected' : '' ?>>Sedan</option>
            <option value="SUV" <?= $car['brand'] === 'SUV' ? 'selected' : '' ?>>SUV</option>
            <option value="Van" <?= $car['brand'] === 'Van' ? 'selected' : '' ?>>Van</option>
            <option value="Luxury" <?= $car['brand'] === 'Luxury' ? 'selected' : '' ?>>Luxury</option>
            <option value="Economy" <?= $car['brand'] === 'Economy' ? 'selected' : '' ?>>Economy</option>
        </select>
        <input type="number" name="price" step="0.01" placeholder="Price per Day" value="<?= htmlspecialchars($car['price_per_day']) ?>" required>
        <select name="type">
            <option value="available" <?= $car['type']==='available' ? 'selected' : '' ?>>Available</option>
            <option value="rented" <?= $car['type']==='rented' ? 'selected' : '' ?>>Rented</option>
            <option value="maintenance" <?= $car['type']==='maintenance' ? 'selected' : '' ?>>Maintenance</option>
            <option value="unavailable" <?= $car['type']==='unavailable' ? 'selected' : '' ?>>Unavailable</option>
        </select>
        <input type="file" name="image" accept="image/*">
        <img src="../<?= htmlspecialchars($car['image'] ?: 'assets/images/placeholder.png') ?>" width="100" class="mb-4 rounded-md">

        <button type="submit" name="update_car">Update Vehicle</button>
    </form>
</div>

</body>
</html>
