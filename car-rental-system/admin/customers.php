<?php
session_start();
require_once '../config/dbconnect.php';

/* ===============================
    ADMIN PROTECTION
================================ */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

/* ===============================
    SEARCH, FILTER, SORT, PAGINATION
================================ */
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'fullname_asc';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$sort_options = [
    'fullname_asc'  => 'fullname ASC',
    'fullname_desc' => 'fullname DESC',
    'email_asc'     => 'email ASC',
    'email_desc'    => 'email DESC'
];
$sort_sql = $sort_options[$sort] ?? 'fullname ASC';

$search_sql = $search ? "AND (fullname LIKE ? OR email LIKE ?)" : "";

// Total Count for Pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role='client' $search_sql");
if ($search) {
    $lk = "%$search%";
    $count_stmt->bind_param("ss", $lk, $lk);
}
$count_stmt->execute();
$total_customers = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_customers / $limit);

// Fetch Customers
$sql = "SELECT id, fullname, email FROM users WHERE role='client' $search_sql ORDER BY $sort_sql LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search) {
    $stmt->bind_param("ssii", $lk, $lk, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Database | Triple M</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .status-badge { padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
        .status-pending { background:#fef3c7; color:#92400e; }
        .status-confirmed { background:#dcfce7; color:#166534; }
        .status-completed { background:#e0f2fe; color:#075985; }
        .status-cancelled { background:#fee2e2; color:#991b1b; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
    <script>
        function toggleDetails(id) {
            const row = document.getElementById('details-' + id);
            const icon = document.getElementById('icon-' + id);
            row.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }
    </script>
</head>

<body class="flex text-[#1e293b]">

<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>

<main class="flex-1 ml-64 p-10 min-h-screen">
    
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0f172a] tracking-tight">Customer Database</h1>
            <p class="text-slate-500 mt-1">Review client history, spending, and reservation status.</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-sm flex items-center gap-2">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Clients:</span>
            <span class="text-lg font-black text-blue-600"><?= $total_customers ?></span>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form method="get" class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>"
                    class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none font-medium text-sm transition-all">
            </div>
            <select name="sort" onchange="this.form.submit()" 
                class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg font-semibold text-slate-600 text-sm outline-none cursor-pointer">
                <option value="fullname_asc" <?= $sort==='fullname_asc'?'selected':'' ?>>Name: A to Z</option>
                <option value="fullname_desc" <?= $sort==='fullname_desc'?'selected':'' ?>>Name: Z to A</option>
            </select>
            <button type="submit" class="bg-[#2563eb] text-white px-8 py-2.5 rounded-lg font-bold text-sm hover:bg-blue-700 shadow-md shadow-blue-100 transition-all">
                Apply Filters
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/50 border-b border-slate-200">
                <tr class="text-[11px] uppercase text-slate-400 font-bold tracking-widest">
                    <th class="px-8 py-4">Customer Details</th>
                    <th class="px-8 py-4 text-center">Stats</th>
                    <th class="px-8 py-4">Total Revenue</th>
                    <th class="px-8 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($customers as $c): 
                    $today = date('Y-m-d');
                    // Get detailed history for the dropdown
                    $b_sql = "SELECT b.*, c.name as car_name, c.brand as car_brand 
                              FROM bookings b 
                              LEFT JOIN cars c ON b.car_id = c.id 
                              WHERE b.user_id = ? 
                              ORDER BY b.created_at DESC";
                    $b_stmt = $conn->prepare($b_sql);
                    $b_stmt->bind_param("i", $c['id']);
                    $b_stmt->execute();
                    $bookings = $b_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    $done = $upcoming = $spent = 0;
                    foreach($bookings as $b) {
                        $status = ($b['status'] == 'confirmed' && $b['end_date'] < $today) ? 'completed' : $b['status'];
                        if($status == 'completed') $done++;
                        if($b['start_date'] > $today && $b['status'] != 'cancelled') $upcoming++;
                        if(in_array($status, ['confirmed', 'completed'])) $spent += $b['total_price'];
                    }
                ?>
                <tr class="hover:bg-slate-50/30 transition-colors group">
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm border border-blue-100">
                                <?= strtoupper(substr($c['fullname'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($c['fullname']) ?></div>
                                <div class="text-[11px] text-slate-400 font-medium"><?= htmlspecialchars($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center justify-center gap-4 text-center">
                            <div><p class="text-[9px] font-bold text-slate-300 uppercase">Total</p><p class="text-sm font-black text-slate-700"><?= count($bookings) ?></p></div>
                            <div><p class="text-[9px] font-bold text-slate-300 uppercase">Done</p><p class="text-sm font-black text-emerald-600"><?= $done ?></p></div>
                            <div><p class="text-[9px] font-bold text-slate-300 uppercase">Upcoming</p><p class="text-sm font-black text-blue-500"><?= $upcoming ?></p></div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="text-sm font-black text-slate-700">₱<?= number_format($spent, 2) ?></span>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <button onclick="toggleDetails(<?= $c['id'] ?>)" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:border-blue-400 hover:text-blue-600 transition-all shadow-sm">
                            Details <i id="icon-<?= $c['id'] ?>" class="fa-solid fa-chevron-down text-[10px] transition-transform"></i>
                        </button>
                    </td>
                </tr>

                <tr id="details-<?= $c['id'] ?>" class="hidden bg-slate-50/50">
                    <td colspan="4" class="px-8 py-6">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-inner overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                                <h3 class="text-sm font-bold text-slate-800">All Bookings History</h3>
                            </div>
                            <div class="overflow-x-auto custom-scrollbar">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b">
                                        <tr>
                                            <th class="px-6 py-3">Car</th>
                                            <th class="px-6 py-3">Brand</th>
                                            <th class="px-6 py-3">Period</th>
                                            <th class="px-6 py-3">Status</th>
                                            <th class="px-6 py-3 text-right">Total Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if(count($bookings) > 0): ?>
                                            <?php foreach($bookings as $bk): 
                                                $s = ($bk['status'] == 'confirmed' && $bk['end_date'] < $today) ? 'completed' : $bk['status'];
                                            ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($bk['car_name'] ?? 'N/A') ?></td>
                                                <td class="px-6 py-4 text-slate-500 font-medium"><?= htmlspecialchars($bk['car_brand'] ?? 'N/A') ?></td>
                                                <td class="px-6 py-4 text-slate-500"><?= $bk['start_date'] ?> to <?= $bk['end_date'] ?></td>
                                                <td class="px-6 py-4"><span class="status-badge status-<?= strtolower($s) ?>"><?= ucfirst($s) ?></span></td>
                                                <td class="px-6 py-4 text-right font-bold text-slate-700">₱<?= number_format($bk['total_price'], 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">No reservation history available.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-8 flex justify-between items-center">
        <p class="text-xs font-bold text-slate-400">Page <?= $page ?> of <?= $total_pages ?></p>
        <div class="flex gap-2">
            <?php for($i=1; $i<=$total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                   class="w-9 h-9 flex items-center justify-center rounded-lg font-bold text-sm transition-all <?= $i == $page ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</main>

</body>
</html>