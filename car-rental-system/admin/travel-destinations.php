<?php
session_start();
require_once '../config/dbconnect.php';

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// --- Logic: Add Destination ---
if (isset($_POST['add_destination'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $conn->query("INSERT INTO travel_destinations (name, location) VALUES ('$name', '$location')");
    header("Location: destinations.php?msg=added");
}

// --- Logic: Delete Destination ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM travel_destinations WHERE id = $id");
    header("Location: destinations.php?msg=deleted");
}

// Fetch all destinations
$destinations = $conn->query("SELECT * FROM travel_destinations ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Destinations | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#f8fafc] flex text-[#1e293b]">

<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>

<main class="flex-1 ml-64 p-8">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Travel Destinations</h1>
            <p class="text-sm text-slate-500">Manage locations available for booking.</p>
        </div>
        <button onclick="toggleModal('addModal')" class="bg-slate-800 hover:bg-slate-700 text-white px-5 py-2 rounded-xl text-sm font-bold transition shadow-sm">
            <i class="fa-solid fa-plus mr-2"></i> Add Destination
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 font-black">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Destination Name</th>
                        <th class="px-6 py-4">Notes / Location</th>
                        <th class="px-6 py-4">Date Added</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if($destinations->num_rows > 0): ?>
                        <?php while($row = $destinations->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $row['id'] ?></td>
                            <td class="px-6 py-4 font-bold text-slate-700 text-sm"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="px-6 py-4 text-sm text-slate-500"><?= htmlspecialchars($row['location'] ?: '---') ?></td>
                            <td class="px-6 py-4 text-xs text-slate-400 font-medium"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Remove this destination?')" class="text-rose-500 hover:bg-rose-50 px-3 py-2 rounded-lg transition">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="p-10 text-center text-slate-400 italic">No destinations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="addModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800">Add New Destination</h3>
            <button onclick="toggleModal('addModal')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-black uppercase text-slate-400 mb-2">Destination Name</label>
                <input type="text" name="name" required placeholder="e.g. Davao City, Baguio" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-400 mb-2">Description / Specific Location (Optional)</label>
                <textarea name="location" rows="3" placeholder="Additional notes..." class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"></textarea>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="toggleModal('addModal')" class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancel</button>
                <button type="submit" name="add_destination" class="flex-1 px-4 py-3 rounded-xl bg-slate-800 text-white font-bold text-sm shadow-lg shadow-slate-200">Save Destination</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(id) {
        document.getElementById(id).classList.toggle('hidden');
    }
</script>

</body>
</html>