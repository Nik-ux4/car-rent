<?php
session_start();
require_once '../config/dbconnect.php';

// Protect admin page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ===============================
    FETCH REAL-TIME STATS
================================ */
// 1. Total Revenue (Sum of total_price from confirmed/completed bookings)
$rev_res = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status IN ('confirmed', 'completed')");
$total_revenue = $rev_res->fetch_assoc()['total'] ?? 0;

// 2. Total Vehicles
$car_res = $conn->query("SELECT COUNT(*) as total FROM cars");
$total_cars = $car_res->fetch_assoc()['total'] ?? 0;

// 3. Active Bookings (Pending + Confirmed)
$active_res = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status IN ('pending', 'confirmed')");
$active_bookings = $active_res->fetch_assoc()['total'] ?? 0;

// 4. Registered Users (excluding admins)
$user_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'client'");
$total_users = $user_res->fetch_assoc()['total'] ?? 0;

/* ===============================
    FETCH RECENT BOOKINGS
================================ */
$recent_sql = "
    SELECT 
        b.id, b.status, b.created_at,
        u.fullname as customer_name,
        c.name as car_name, c.image
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN cars c ON b.car_id = c.id
    ORDER BY b.created_at DESC
    LIMIT 5
";
$recent_result = $conn->query($recent_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Elite Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .status-badge { padding: 4px 10px; border-radius: 99px; font-size: 10px; font-weight: 800; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #e0f2fe; color: #075985; }
    </style>
</head>

<body class="bg-[#f8fafc] flex text-[#1e293b]">

<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>

<main class="flex-1 ml-64 p-8">

    <?php include __DIR__ . '/../components/layout/admin-header.php'; ?>

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800">System Overview</h1>
        <p class="text-sm text-slate-500">Welcome back, Admin. Here is what's happening today.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

        <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-wallet"></i>
                </div>
                <span class="text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded">GROWTH</span>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Revenue</p>
            <h3 class="text-2xl font-black mt-1 text-slate-800">₱<?= number_format($total_revenue) ?></h3>
        </div>

        <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-car"></i>
                </div>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Vehicles</p>
            <h3 class="text-2xl font-black mt-1 text-slate-800"><?= $total_cars ?></h3>
        </div>

        <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Bookings</p>
            <h3 class="text-2xl font-black mt-1 text-slate-800"><?= $active_bookings ?></h3>
        </div>

        <div class="stat-card bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registered Users</p>
            <h3 class="text-2xl font-black mt-1 text-slate-800"><?= $total_users ?></h3>
        </div>

    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800">Recent Activity</h3>
            <a href="bookings.php" class="text-xs text-amber-600 hover:text-amber-700 font-bold uppercase tracking-widest">
                View All Bookings <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 font-black">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Vehicle</th>
                        <th class="px-6 py-4">Date Requested</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if($recent_result->num_rows > 0): ?>
                        <?php while($row = $recent_result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $row['id'] ?></td>
                            <td class="px-6 py-4 font-bold text-slate-700 text-sm"><?= htmlspecialchars($row['customer_name'] ?? 'Guest') ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <img src="../<?= htmlspecialchars($row['image']) ?>" class="w-8 h-6 rounded object-cover border" onerror="this.src='https://via.placeholder.com/50'">
                                    <span class="text-sm font-semibold text-slate-600"><?= htmlspecialchars($row['car_name']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="p-10 text-center text-slate-400 italic">No recent activity.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>