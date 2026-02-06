<?php
session_start();
require_once '../config/dbconnect.php';

// Protect admin page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Add Car submission
$add_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $price = trim($_POST['price']);
    $type = $_POST['type'] ?? 'available';
    $imgPath = null;

    if (!empty($name) && !empty($brand) && !empty($price)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "../uploads/cars/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imgPath = "uploads/cars/$fileName";
            }
        }
        $stmt = $conn->prepare("INSERT INTO cars (name, brand, price_per_day, type, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssdss", $name, $brand, $price, $type, $imgPath);
        $stmt->execute();
        $stmt->close();
        header("Location: vehicles.php");
        exit;
    } else {
        $add_message = "All fields are required!";
    }
}

// Handle Edit Car submission
$edit_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_car'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $price = trim($_POST['price']);
    $type = $_POST['type'] ?? 'available';
    $imgPath = $_POST['old_image'] ?? null;

    if (!empty($name) && !empty($brand) && !empty($price)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "../uploads/cars/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imgPath = "uploads/cars/$fileName";
            }
        }

        $stmt = $conn->prepare("UPDATE cars SET name=?, brand=?, price_per_day=?, type=?, image=? WHERE id=?");
        $stmt->bind_param("ssdssi", $name, $brand, $price, $type, $imgPath, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: vehicles.php");
        exit;
    } else {
        $edit_message = "All fields are required!";
    }
}

// Fetch cars
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$query = "SELECT * FROM cars WHERE 1";

if ($search) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $query .= " AND (name LIKE '%$search_safe%' OR brand LIKE '%$search_safe%')";
}
if ($filter) {
    $filter_safe = mysqli_real_escape_string($conn, $filter);
    $query .= " AND type='$filter_safe'";
}

$query .= " ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$totalCars = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Vehicles - Triple M Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
.table-container { background: #fff; border-radius: 16px; overflow-x: auto; border: 1px solid #e5e7eb; }
table { width: 100%; min-width: 900px; border-collapse: collapse; }
th, td { padding: 14px 18px; border-bottom: 1px solid #e5e7eb; text-align: left; }
th { background-color: #f1f5f9; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; }
td img { width: 90px; height: 55px; object-fit: cover; border-radius: 10px; background: #f1f5f9; }
.badge-status { padding: 4px 12px; border-radius: 9999px; font-size: 10px; font-weight: 800; color: white; display: inline-block; }
.status-available { background-color: #10b981; }
.status-rented { background-color: #ef4444; }
.status-maintenance { background-color: #f59e0b; }
.btn-edit { background-color: #f59e0b; color: #fff !important; padding: 8px; border-radius: 8px; }
.btn-delete { background-color: #fee2e2; color: #ef4444 !important; padding: 8px; border-radius: 8px; }
</style>
</head>
<body class="flex text-[#1e293b]">

<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>

<main class="flex-1 ml-64 p-8">

<!-- Header -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Vehicle Management</h1>
    <p class="text-sm text-slate-500"><?= $totalCars ?> Vehicles Registered</p>
    <button id="openAddModal" class="px-4 py-2 bg-amber-400 text-[#0f172a] rounded-xl hover:bg-amber-300 transition font-semibold">+ Add Car</button>
</div>

<!-- Table -->
<div class="table-container shadow-sm">
<table>
<thead>
<tr>
<th>ID</th>
<th>Image</th>
<th>Vehicle Details</th>
<th>Status</th>
<th>Price / Day</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if ($totalCars > 0): ?>
<?php while ($car = mysqli_fetch_assoc($result)): ?>
<?php
$id = $car['id'];
$name = $car['name'];
$brand = $car['brand'];
$status = $car['type'];
$price = $car['price_per_day'];
$image = $car['image'] ?: 'assets/images/placeholder.png';
$statusClass = strtolower($status);
?>
<tr class="hover:bg-slate-50">
<td>#<?= $id ?></td>
<td><img src="../<?= $image ?>" onerror="this.src='../assets/images/placeholder.png'"></td>
<td><div class="font-bold text-slate-800"><?= $brand ?></div><div class="text-sm text-slate-500"><?= $name ?></div></td>
<td><span class="badge-status status-<?= $statusClass ?>"><?= strtoupper($status) ?></span></td>
<td class="font-bold text-amber-600">₱<?= number_format($price, 0) ?></td>
<td>
<div class="flex gap-2">
<button class="btn-edit openEditModal"
data-id="<?= $id ?>"
data-name="<?= htmlspecialchars($name) ?>"
data-brand="<?= htmlspecialchars($brand) ?>"
data-price="<?= $price ?>"
data-type="<?= $status ?>"
data-image="<?= $image ?>"><i class="fa-solid fa-pen-to-square"></i></button>
<a href="delete-vehicle.php?id=<?= $id ?>" class="btn-delete" onclick="return confirm('Delete this vehicle?')"><i class="fa-solid fa-trash"></i></a>
</div>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" class="text-center py-20 text-slate-400">No vehicles found in the database.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Add Car Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 flex items-center justify-center opacity-0 invisible transition-opacity z-50">
<div class="bg-white rounded-2xl w-full max-w-lg p-6 relative">
<button id="closeAddModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700"><i class="fa-solid fa-xmark"></i></button>
<h2 class="text-xl font-bold mb-4">Add New Car</h2>
<?php if($add_message) echo "<p class='text-red-500 mb-4'>$add_message</p>"; ?>
<form method="POST" enctype="multipart/form-data" class="space-y-4">
<input type="text" name="name" placeholder="Car Name" required class="w-full border px-3 py-2 rounded-md">
<select name="brand" class="w-full border px-3 py-2 rounded-md" required>
<option value="" disabled selected>Select Brand</option>
<option value="Sedan">Sedan</option>
<option value="SUV">SUV</option>
<option value="Van">Van</option>
<option value="Luxury">Luxury</option>
<option value="Economy">Economy</option>
</select>
<input type="number" name="price" step="0.01" placeholder="Price per Day" required class="w-full border px-3 py-2 rounded-md">
<select name="type" class="w-full border px-3 py-2 rounded-md">
<option value="available">Available</option>
<option value="rented">Rented</option>
<option value="maintenance">Maintenance</option>
<option value="unavailable">Unavailable</option>
</select>
<input type="file" name="image" accept="image/*" class="w-full border px-3 py-2 rounded-md">
<button type="submit" name="add_car" class="w-full bg-amber-400 text-[#0f172a] py-2 rounded-xl font-semibold hover:bg-amber-300 transition">Add Car</button>
</form>
</div>
</div>

<!-- Edit Car Modal -->
<div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center opacity-0 invisible transition-opacity z-50">
<div class="bg-white rounded-2xl w-full max-w-lg p-6 relative">
<button id="closeEditModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700"><i class="fa-solid fa-xmark"></i></button>
<h2 class="text-xl font-bold mb-4">Edit Vehicle</h2>
<?php if($edit_message) echo "<p class='text-red-500 mb-4'>$edit_message</p>"; ?>
<form method="POST" enctype="multipart/form-data" class="space-y-4">
<input type="hidden" name="id" id="editId">
<input type="hidden" name="old_image" id="editOldImage">
<input type="text" name="name" id="editName" placeholder="Car Name" required class="w-full border px-3 py-2 rounded-md">
<select name="brand" id="editBrand" class="w-full border px-3 py-2 rounded-md" required>
<option value="Sedan">Sedan</option>
<option value="SUV">SUV</option>
<option value="Van">Van</option>
<option value="Luxury">Luxury</option>
<option value="Economy">Economy</option>
</select>
<input type="number" name="price" id="editPrice" step="0.01" placeholder="Price per Day" required class="w-full border px-3 py-2 rounded-md">
<select name="type" id="editType" class="w-full border px-3 py-2 rounded-md">
<option value="available">Available</option>
<option value="rented">Rented</option>
<option value="maintenance">Maintenance</option>
<option value="unavailable">Unavailable</option>
</select>
<input type="file" name="image" id="editImage" accept="image/*" class="w-full border px-3 py-2 rounded-md">
<img id="editPreview" src="" class="w-32 rounded-md mb-2">
<button type="submit" name="edit_car" class="w-full bg-amber-400 text-[#0f172a] py-2 rounded-xl font-semibold hover:bg-amber-300 transition">Update Vehicle</button>
</form>
</div>
</div>

</main>

<script>
// Add Modal
const addModal = document.getElementById('addModal');
document.getElementById('openAddModal').onclick = () => { addModal.classList.remove('opacity-0','invisible'); addModal.classList.add('opacity-100','visible'); };
document.getElementById('closeAddModal').onclick = () => { addModal.classList.add('opacity-0','invisible'); addModal.classList.remove('opacity-100','visible'); };
addModal.addEventListener('click', e => { if(e.target === addModal) { addModal.classList.add('opacity-0','invisible'); addModal.classList.remove('opacity-100','visible'); }});

// Edit Modal
const editModal = document.getElementById('editModal');
document.getElementById('closeEditModal').onclick = () => { editModal.classList.add('opacity-0','invisible'); editModal.classList.remove('opacity-100','visible'); };
editModal.addEventListener('click', e => { if(e.target === editModal) { editModal.classList.add('opacity-0','invisible'); editModal.classList.remove('opacity-100','visible'); }});

document.querySelectorAll('.openEditModal').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editName').value = btn.dataset.name;
        document.getElementById('editBrand').value = btn.dataset.brand;
        document.getElementById('editPrice').value = btn.dataset.price;
        document.getElementById('editType').value = btn.dataset.type;
        document.getElementById('editOldImage').value = btn.dataset.image;
        document.getElementById('editPreview').src = '../' + btn.dataset.image;
        editModal.classList.remove('opacity-0','invisible'); editModal.classList.add('opacity-100','visible');
    });
});
</script>
</body>
</html>
