<?php
session_start();
require_once '../config/dbconnect.php';

/* ===============================
    ADMIN SECURITY
================================ */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

/* ===============================
    TOTAL REVENUE
================================ */
$revenue_result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$total_revenue = $revenue_result ? $revenue_result->fetch_assoc()['total'] ?? 0 : 0;

/* ===============================
    FETCH PAYMENTS & BOOKING DATES
================================ */
$sql = "
    SELECT 
        p.booking_id,
        p.amount,
        p.payment_method,
        p.status AS payment_status,
        p.paid_at,
        p.created_at,
        b.id AS booking_reference,
        b.start_date,
        b.end_date,
        u.fullname AS customer_name,
        c.name AS car_name
    FROM payments p
    LEFT JOIN bookings b ON p.booking_id = b.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN cars c ON b.car_id = c.id
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);
if (!$result) {
    die("Query Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Payment Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .status-completed { background:#dcfce7; color:#166534; }
        .status-pending { background:#fef3c7; color:#92400e; }
        .status-failed { background:#fee2e2; color:#991b1b; }
        
        /* Schedule Badge Styles */
        .sched-reserved { background:#dbeafe; color:#1e40af; } /* Blue */
        .sched-ontrip { background:#f3e8ff; color:#6b21a8; }  /* Purple */
        .sched-finished { background:#f1f5f9; color:#475569; } /* Gray */
    </style>
</head>
<body class="bg-[#f8fafc] flex text-[#1e293b]">

<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>

<main class="flex-1 ml-64 p-8">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Payment Records</h1>
            <p class="text-gray-500 text-sm">Monitor transactions and real-time rental schedules.</p>
        </div>
        <div class="bg-white border border-emerald-100 p-4 rounded-2xl shadow-sm flex items-center gap-4">
            <div class="bg-emerald-500 w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Revenue</p>
                <p class="text-2xl font-black text-emerald-600">₱<?= number_format($total_revenue, 2) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-400 font-bold">
                <tr>
                    <th class="px-6 py-4">Transaction / Schedule</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Vehicle</th>
                    <th class="px-6 py-4">Method</th>
                    <th class="px-6 py-4 text-right">Amount</th>
                    <th class="px-6 py-4">Status & Schedule</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $today = date('Y-m-d');
                        $start = $row['start_date'];
                        $end = $row['end_date'];
                        $payStatus = $row['payment_status'];

                        // Schedule Logic
                        $schedLabel = "";
                        $schedClass = "";
                        
                        if ($payStatus === 'completed') {
                            if ($today < $start) {
                                $schedLabel = "Upcoming / Reserved";
                                $schedClass = "sched-reserved";
                            } elseif ($today >= $start && $today <= $end) {
                                $schedLabel = "Active / On Trip";
                                $schedClass = "sched-ontrip";
                            } else {
                                $schedLabel = "Completed / Finished";
                                $schedClass = "sched-finished";
                            }
                        }
                    ?>
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-blue-600">#TXN-<?= $row['booking_id'] ?></div>
                            <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-1">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('M d', strtotime($start)) ?> - <?= date('M d', strtotime($end)) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-700"><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></div>
                            <div class="text-[10px] text-gray-400">ID: #BK-<?= $row['booking_reference'] ?></div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 font-medium"><?= htmlspecialchars($row['car_name'] ?? 'N/A') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 rounded text-[10px] font-bold text-gray-500 uppercase">
                                <?= htmlspecialchars($row['payment_method']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-black text-gray-800">
                            ₱<?= number_format($row['amount'], 2) ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase status-<?= $payStatus ?>">
                                <?= $payStatus ?>
                            </span>

                            <?php if ($schedLabel): ?>
                                <div class="mt-2">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase <?= $schedClass ?>">
                                        <i class="fas fa-clock mr-1"></i> <?= $schedLabel ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <i class="fas fa-receipt text-5xl text-gray-200 mb-4 block"></i>
                            <p class="text-gray-400 font-medium">No transactions recorded yet.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>
</body>
</html>