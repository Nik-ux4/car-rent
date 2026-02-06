<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin/login.php");
    exit;
}

require_once "../config/dbconnect.php";

// Get search value
$search = $_GET['search'] ?? '';

// Count cars needing attention (maintenance or unavailable)
$alert_count = 0;
$alert_query = "SELECT COUNT(*) AS total_alerts FROM cars WHERE type='maintenance' OR type='unavailable'";
$result = $conn->query($alert_query);
if ($result) {
    $row = $result->fetch_assoc();
    $alert_count = $row['total_alerts'];
}

// Handle Add Car submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $price = trim($_POST['price']);
    $type = $_POST['type'] ?? 'available';
    $imgPath = null;

    if (!empty($name) && !empty($brand) && !empty($price)) {

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "../uploads/cars/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imgPath = "uploads/cars/$fileName";
            }
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO cars (name, brand, price_per_day, type, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssdss", $name, $brand, $price, $type, $imgPath);

        if ($stmt->execute()) {
            // Redirect to vehicles.php immediately after adding
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

<header class="flex flex-wrap justify-between items-center gap-4 mb-8">

    <!-- LEFT -->
    <div>
        <h2 class="text-2xl font-bold text-[#0f172a]">Cars Management</h2>
        <p class="text-sm text-gray-500">Manage vehicles, availability, and maintenance</p>
    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-3">

        <!-- SEARCH CARS -->
        <form method="GET" action="vehicles.php" class="hidden md:block">
            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search cars..."
                class="px-4 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
            >
        </form>

        <!-- CAR ALERTS -->
        <a href="vehicles.php?filter=maintenance"
           title="Cars needing attention"
           class="relative p-2 text-gray-400 hover:text-red-500 transition">
            <i class="fa-solid fa-car-burst text-xl"></i>
            <?php if($alert_count > 0): ?>
                <span class="absolute top-1 right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
            <?php endif; ?>
        </a>

        <!-- ADD CAR BUTTON -->
        <button id="openCarModal"
                class="px-4 py-2 bg-amber-400 text-[#0f172a] rounded-xl text-sm font-semibold hover:bg-amber-300 transition">
            + Add Car
        </button>

        <!-- ADMIN PROFILE -->
        <div class="relative group">
            <div class="w-10 h-10 bg-amber-400 rounded-full flex items-center justify-center font-bold text-[#0f172a] cursor-pointer">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>

            <!-- DROPDOWN -->
            <div class="absolute right-0 mt-3 w-40 bg-white shadow-lg rounded-xl opacity-0 invisible group-hover:visible group-hover:opacity-100 transition">
                <div class="px-4 py-3 border-b text-sm font-semibold text-gray-700">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
                <a href="../auth/logout.php"
                   class="block px-4 py-2 text-sm text-red-500 hover:bg-red-50">
                    Logout
                </a>
            </div>
        </div>

    </div>
</header>

<!-- ADD CAR MODAL -->
<div id="carModal" class="fixed inset-0 bg-black/50 flex items-center justify-center opacity-0 invisible transition-opacity z-50">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 relative">
        <button id="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700"><i class="fa-solid fa-xmark"></i></button>
        <h2 class="text-xl font-bold mb-4">Add New Car</h2>

        <?php if(!empty($message)): ?>
            <p class="text-sm text-red-500 mb-4"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="text" name="name" placeholder="Car Name" required class="w-full border px-3 py-2 rounded-md">

            <!-- BRAND DROPDOWN -->
            <select name="brand" class="w-full border px-3 py-2 rounded-md" required>
                <option value="" disabled selected>Select Brand</option>
                <option value="Sedan">Sedan</option>
                <option value="SUV">SUV</option>
                <option value="Van">Van</option>
                <option value="Luxury">Luxury</option>
                <option value="Economy">Economy</option>
            </select>

            <input type="number" name="price" step="0.01" placeholder="Price per Day" required class="w-full border px-3 py-2 rounded-md">

            <!-- TYPE DROPDOWN -->
            <select name="type" class="w-full border px-3 py-2 rounded-md">
                <option value="available">Available</option>
                <option value="rented">Rented</option>
                <option value="maintenance">Maintenance</option>
                <option value="unavailable">Unavailable</option>
            </select>

            <input type="file" name="image" accept="image/*" class="w-full border px-3 py-2 rounded-md">

            <button type="submit" name="add_car" class="w-full bg-amber-400 text-[#0f172a] py-2 rounded-xl font-semibold hover:bg-amber-300 transition">
                Add Car
            </button>
        </form>
    </div>
</div>

<script>
// MODAL FUNCTIONALITY
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

modal.addEventListener('click', (e) => {
    if(e.target === modal){
        modal.classList.add('opacity-0', 'invisible');
        modal.classList.remove('opacity-100', 'visible');
    }
});
</script>
