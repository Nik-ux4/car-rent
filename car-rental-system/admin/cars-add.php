<?php
// Handle form submission
session_start();
require_once "../config/dbconnect.php";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $price = trim($_POST['price']);
    $status = $_POST['status'] ?? 'available';

    // Simple validation
    if (empty($name) || empty($brand) || empty($price)) {
        $message = "All fields are required!";
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "../uploads/cars/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imgPath = "uploads/cars/$fileName";
            } else {
                $message = "Failed to upload image.";
            }
        } else {
            $imgPath = null;
        }

        if (!$message) {
            $stmt = $conn->prepare("INSERT INTO cars (name, brand, price_per_day, status, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssdss", $name, $brand, $price, $status, $imgPath);
            if ($stmt->execute()) {
                $message = "Car added successfully!";
            } else {
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!-- Add Car Button -->
<button id="openCarModal" class="px-4 py-2 bg-amber-400 text-[#0f172a] rounded-xl hover:bg-amber-300 transition font-semibold">
    + Add Car
</button>

<!-- Modal -->
<div id="carModal" class="fixed inset-0 bg-black/50 flex items-center justify-center opacity-0 invisible transition-opacity">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 relative">
        <button id="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="text-xl font-bold mb-4">Add New Car</h2>

        <?php if($message): ?>
            <p class="text-sm text-green-600 mb-4"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="text" name="name" placeholder="Car Name" required class="w-full border px-3 py-2 rounded-md">
            <input type="text" name="brand" placeholder="Brand" required class="w-full border px-3 py-2 rounded-md">
            <input type="number" name="price" step="0.01" placeholder="Price per Day" required class="w-full border px-3 py-2 rounded-md">
            <select name="status" class="w-full border px-3 py-2 rounded-md">
                <option value="available">Available</option>
                <option value="rented">Rented</option>
                <option value="maintenance">Maintenance</option>
            </select>
            <input type="file" name="image" accept="image/*" class="w-full border px-3 py-2 rounded-md">
            <button type="submit" name="add_car" class="w-full bg-amber-400 text-[#0f172a] py-2 rounded-xl font-semibold hover:bg-amber-300 transition">Add Car</button>
        </form>
    </div>
</div>

<script>
// Modal JS
const modal = document.getElementById('carModal');
const openBtn = document.getElementById('openCarModal');
const closeBtn = document.getElementById('closeModal');

openBtn.addEventListener('click', () => {
    modal.classList.remove('opacity-0', 'invisible');
    modal.classList.add('opacity-100', 'visible');
});

closeBtn.addEventListener('click', () => {
    modal.classList.add('opacity-0', 'invisible');
    modal.classList.remove('opacity-100', 'visible');
});

// Close modal when clicking outside content
modal.addEventListener('click', (e) => {
    if(e.target === modal) {
        modal.classList.add('opacity-0', 'invisible');
        modal.classList.remove('opacity-100', 'visible');
    }
});
</script>
