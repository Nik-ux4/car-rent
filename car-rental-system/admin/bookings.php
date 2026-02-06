<?php 
session_start();
require_once '../config/dbconnect.php';

/* =============================== ADMIN SECURITY ================================ */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

/* =============================== AJAX ACTIONS ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $booking_id = (int)$_POST['booking_id'];

    // Fetch booking
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id=?");
    $stmt->bind_param("i",$booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    if (!$booking) {
        echo json_encode(['status'=>'error','message'=>'Booking not found.']);
        exit;
    }

    $payment_status = strtolower($booking['payment_status'] ?? '');
    $payment_method = strtolower($booking['payment_method'] ?? '');

    /* ===== VERIFY PAYMENT ===== */
    if ($_POST['action'] === 'verify_payment') {
        if ($payment_method === 'gcash' && empty($booking['payment_receipt'])) {
            echo json_encode(['status'=>'error','message'=>'Missing receipt for GCash.']);
            exit;
        }

        // Update booking to Paid & cleared
        $stmt = $conn->prepare("UPDATE bookings SET payment_status='paid', cleared=1 WHERE id=?");
        $stmt->bind_param("i",$booking_id);
        $stmt->execute();

        // Insert or update payments table
        $stmt2 = $conn->prepare("INSERT INTO payments 
            (booking_id, amount, payment_method, status, created_at, paid_at)
            VALUES (?, ?, ?, 'Paid', NOW(), NOW())
            ON DUPLICATE KEY UPDATE status='Paid', paid_at=NOW()");
        $stmt2->bind_param("ids",$booking_id,$booking['total_price'],$booking['payment_method']);
        $stmt2->execute();

        echo json_encode(['status'=>'success','message'=>'Payment verified. Booking ready for confirmation.']);
        exit;
    }

    /* ===== CONFIRM BOOKING ===== */
    if ($_POST['action'] === 'confirm_booking') {
        if ($booking['payment_status'] !== 'paid' || $booking['cleared'] != 1) {
            echo json_encode(['status'=>'error','message'=>'Payment must be verified first.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=?");
        $stmt->bind_param("i",$booking_id);
        $stmt->execute();
        echo json_encode(['status'=>'success','message'=>'Booking confirmed.']);
        exit;
    }

    /* ===== UPDATE STATUS (COMPLETE/CANCEL) ===== */
    if ($_POST['action'] === 'update_status') {
        $status = $_POST['status'];
        $valid = ['completed','cancelled'];
        if (!in_array($status,$valid)) {
            echo json_encode(['status'=>'error','message'=>'Invalid status.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        $stmt->bind_param("si",$status,$booking_id);
        $stmt->execute();
        echo json_encode(['status'=>'success','message'=>"Status updated to $status."]);
        exit;
    }
}

/* =============================== FETCH BOOKINGS ================================ */
$stats = $conn->query("
    SELECT COUNT(*) AS total, 
           SUM(CASE WHEN status IN ('confirmed','completed') AND cleared=1 THEN total_price ELSE 0 END) AS revenue
    FROM bookings
")->fetch_assoc();

$sql = "SELECT b.*, u.fullname, u.email, c.name AS car_name, c.brand, c.image, p.payment_method, p.status AS pay_rec_status
        FROM bookings b
        LEFT JOIN users u ON b.user_id=u.id
        LEFT JOIN cars c ON b.car_id=c.id
        LEFT JOIN payments p ON p.booking_id=b.id
        ORDER BY b.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Central | Triple M</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
.status-badge { padding: 4px 12px; border-radius: 99px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-reserved { background: #f0f9ff; color: #0369a1; }
.status-confirmed { background: #dcfce7; color: #166534; }
.status-completed { background: #e0f2fe; color: #075985; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body class="flex min-h-screen">
<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>
<main class="flex-1 ml-64 p-10">

<div class="flex justify-between items-end mb-10">
    <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Booking Management</h1>
        <p class="text-slate-500">Track schedules, payments, and rental completions.</p>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
        <div class="text-right">
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Total Revenue</span>
            <span class="text-xl font-black text-emerald-600 tracking-tighter">₱<?= number_format($stats['revenue'] ?? 0, 2) ?></span>
        </div>
    </div>
</div>

<div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
<table class="w-full text-left">
<thead class="bg-slate-50/50 border-b border-slate-100">
<tr>
<th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase">Customer</th>
<th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase">Rental Schedule</th>
<th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase">Vehicle</th>
<th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase">Total Payments</th>
<th class="px-6 py-5 text-[11px] font-black text-slate-400 uppercase">Status</th>
<th class="px-6 py-5 text-right text-[11px] font-black text-slate-400 uppercase">Actions</th>
</tr>
</thead>

<tbody class="divide-y divide-slate-50" id="bookingsTableBody">
<?php while($row = $result->fetch_assoc()):
    $status = strtolower($row['status'] ?? '');
    $payment_status = strtolower($row['payment_status'] ?? $row['pay_rec_status'] ?? '');
    $payment_method = strtolower($row['payment_method'] ?? '');
    $cleared = (int)($row['cleared'] ?? 0);
    $start_time = strtotime($row['start_date']);
    $now = time();

    // Button logic
    $isPendingVer = ($status === 'pending' && $cleared === 0);
    $isPendingPaid = ($status === 'pending' && $cleared === 1);
    $isReserved = ($status === 'reserved' || $start_time > $now);

    $start = new DateTime($row['start_date']);
    $end = new DateTime($row['end_date']);
    $interval = $start->diff($end);
    $days = $interval->days;
    $hours = $interval->h;
    $duration = ($days>0?$days.' Day'.($days>1?'s':''):'') . ($hours>0?($days>0?' ':'').$hours.' Hour'.($hours>1?'s':''):'');
    if(!$duration) $duration='Same Time';
?>
<tr id="bookingRow<?= $row['id'] ?>" class="hover:bg-slate-50/50 transition-colors">
<td class="px-6 py-6">
    <div class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($row['fullname']) ?></div>
    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-tight italic"><?= htmlspecialchars($row['email']) ?></div>
</td>
<td class="px-6 py-6">
    <div class="flex flex-col gap-1">
        <div class="text-xs font-bold text-slate-700 flex items-center gap-2">
            <i class="far fa-calendar-check text-emerald-500 text-[10px]"></i> <?= $start->format('M d, Y H:i') ?>
        </div>
        <div class="text-xs font-bold text-slate-400 flex items-center gap-2">
            <i class="far fa-calendar-times text-rose-400 text-[10px]"></i> <?= $end->format('M d, Y H:i') ?>
        </div>
        <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded self-start mt-1">DURATION: <?= $duration ?></span>
    </div>
</td>
<td class="px-6 py-6">
    <div class="flex items-center gap-3">
        <img src="../<?= $row['image'] ?>" class="w-12 h-8 object-cover rounded-lg border shadow-sm" onerror="this.src='https://via.placeholder.com/50'">
        <div>
            <div class="text-xs font-bold text-slate-700 leading-none mb-1"><?= htmlspecialchars($row['car_name']) ?></div>
            <div class="text-[9px] font-black text-slate-400 uppercase"><?= htmlspecialchars($row['brand']) ?></div>
        </div>
    </div>
</td>
<td class="px-6 py-6" id="paymentCell<?= $row['id'] ?>">
    <div class="text-[10px] font-bold text-slate-800 italic"><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></div>
    <div class="text-xs font-black text-blue-600">₱<?= number_format($row['total_price'],2) ?></div>
    <div class="text-[9px] font-bold text-slate-500">Payment: <?= htmlspecialchars($row['payment_status'] ?? 'N/A') ?></div>
    <!-- NEW: Reference Number -->
    <?php if(!empty($row['reference_number'])): ?>
        <div class="text-[9px] font-black text-purple-600 mt-1">
            REF: <?= htmlspecialchars($row['reference_number']) ?>
        </div>
    <?php endif; ?>
    <?php if($row['payment_receipt']): ?>
    <button onclick="viewReceipt('../uploads/receipts/<?= htmlspecialchars($row['payment_receipt']) ?>')" 
        class="mt-1 text-[9px] font-black text-emerald-600 flex items-center gap-1 hover:underline uppercase">
        <i class="fas fa-file-invoice"></i> View Proof
    </button>
    <?php endif; ?>
</td>
<td class="px-6 py-6" id="statusCell<?= $row['id'] ?>">
    <span class="status-badge status-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span>
    <?php if($isPendingVer): ?>
        <span class="block text-[8px] font-black text-blue-500 mt-1 animate-pulse tracking-widest">VERIFICATION REQ.</span>
    <?php elseif($isReserved): ?>
        <span class="block text-[8px] font-black text-slate-500 mt-1">RESERVED (Future Booking)</span>
    <?php endif; ?>
</td>
<td class="px-6 py-6 text-right" id="actionCell<?= $row['id'] ?>">
<?php if($isPendingVer): ?>
    <button onclick="openActionModal(<?= $row['id'] ?>,'verify_payment','<?= htmlspecialchars($row['payment_method']) ?>')" 
        class="bg-amber-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg hover:bg-amber-700 transition-all hover:-translate-y-0.5">
        VERIFY PAYMENT
    </button>
<?php elseif($isPendingPaid): ?>
    <button onclick="openActionModal(<?= $row['id'] ?>,'confirm_booking')" 
        class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg hover:bg-emerald-600 transition-all hover:-translate-y-0.5">
        CONFIRM BOOKING
    </button>
<?php elseif($status==='confirmed'): ?>
    <button onclick="openActionModal(<?= $row['id'] ?>,'update_status','completed')" 
        class="bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-blue-700 transition-all shadow-lg hover:-translate-y-0.5">
        COMPLETE
    </button>
<?php elseif(in_array($status, ['completed','cancelled'])): ?>
    <div class="text-emerald-500 text-xs font-black"><i class="fas fa-check-double mr-1"></i> DONE</div>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</main>

<!-- Receipt & Action Modals -->
<div id="receiptModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-md" onclick="closeReceipt()"></div>
    <img id="receiptImg" src="" class="relative max-w-full max-h-[90vh] rounded-3xl shadow-2xl border-8 border-white/10">
</div>

<div id="actionModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeActionModal()"></div>
    <div class="relative bg-white rounded-[2rem] p-8 max-w-sm w-full shadow-2xl text-center">
        <div id="modalIcon" class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl"></div>
        <h3 id="modalTitle" class="text-xl font-black text-slate-900 mb-2"></h3>
        <p id="modalDesc" class="text-xs text-slate-500 mb-8 leading-relaxed"></p>
        <div class="flex gap-3">
            <button onclick="closeActionModal()" class="flex-1 py-3 font-bold text-slate-400 text-[10px] uppercase">Cancel</button>
            <button id="confirmBtn" class="flex-1 py-3 rounded-2xl font-black text-white text-[10px] uppercase shadow-lg"></button>
        </div>
    </div>
</div>

<script>
let activeId = null;

function viewReceipt(src){document.getElementById('receiptImg').src=src;document.getElementById('receiptModal').classList.replace('hidden','flex');}
function closeReceipt(){document.getElementById('receiptModal').classList.replace('flex','hidden');}

function openActionModal(id,type,method=''){
    activeId=id;
    const modal=document.getElementById('actionModal');
    const icon=document.getElementById('modalIcon');
    const title=document.getElementById('modalTitle');
    const desc=document.getElementById('modalDesc');
    const btn=document.getElementById('confirmBtn');

    if(type==='verify_payment'){
        icon.className="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl bg-amber-50 text-amber-500";
        icon.innerHTML='<i class="fas fa-shield-check"></i>';
        title.innerText="Verify Payment";
        desc.innerText="Ensure the payment reference and receipt (or cash) are correct.";
        btn.innerText="Verify Payment";
        btn.className="flex-1 py-3 rounded-2xl font-black text-white text-[10px] bg-amber-600 hover:bg-amber-700 shadow-amber-200";
        btn.onclick=()=>runAjax('verify_payment');
    } else if(type==='confirm_booking'){
        icon.className="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl bg-slate-50 text-slate-700";
        icon.innerHTML='<i class="fas fa-check-circle"></i>';
        title.innerText="Confirm Booking";
        desc.innerText="Payment verified. Confirm the booking.";
        btn.innerText="Confirm Booking";
        btn.className="flex-1 py-3 rounded-2xl font-black text-white text-[10px] bg-slate-900 hover:bg-emerald-600 shadow-slate-200";
        btn.onclick=()=>runAjax('confirm_booking');
    } else {
        icon.className="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl bg-blue-50 text-blue-500";
        icon.innerHTML='<i class="fas fa-flag-checkered"></i>';
        title.innerText="Complete Rental";
        desc.innerText="Ensure the vehicle is inspected before closing this record.";
        btn.innerText="Mark Completed";
        btn.className="flex-1 py-3 rounded-2xl font-black text-white text-[10px] bg-blue-600 hover:bg-blue-700 shadow-blue-200";
        btn.onclick=()=>runAjax('update_status','completed');
    }
    modal.classList.replace('hidden','flex');
}

async function runAjax(action,status=''){
    const btn=document.getElementById('confirmBtn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    const fd=new FormData
    fd.append('action', action);
    fd.append('booking_id', activeId);
    if (status) fd.append('status', status);

    try {
        const res = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') {
            updateRow(activeId, action, status);
            closeActionModal();
        } else {
            alert(data.message || 'Error');
            btn.disabled = false;
            btn.innerHTML = 'Retry';
        }
    } catch (e) {
        alert("Error: " + e.message);
        btn.disabled = false;
        btn.innerHTML = 'Retry';
    }
}

function updateRow(id, action, status='') {
    const statusCell = document.getElementById('statusCell' + id);
    const actionCell = document.getElementById('actionCell' + id);
    const paymentCell = document.getElementById('paymentCell' + id);

    if (action === 'verify_payment') {
        // Update payment status in the table
        paymentCell.querySelector('.text-slate-500').innerText = 'Payment: paid';
        // Show confirm booking button
        actionCell.innerHTML = `<button onclick="openActionModal(${id},'confirm_booking')" 
            class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg hover:bg-emerald-600 transition-all hover:-translate-y-0.5">
            CONFIRM BOOKING
        </button>`;
    } else if (action === 'confirm_booking') {
        // Update status badge
        statusCell.querySelector('.status-badge').innerText = 'confirmed';
        statusCell.querySelector('.status-badge').className = 'status-badge status-confirmed';
        // Show complete button
        actionCell.innerHTML = `<button onclick="openActionModal(${id},'update_status','completed')" 
            class="bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-blue-700 transition-all shadow-lg hover:-translate-y-0.5">
            COMPLETE
        </button>`;
    } else if (action === 'update_status') {
        const finalStatus = status || 'completed';
        statusCell.querySelector('.status-badge').innerText = finalStatus;
        statusCell.querySelector('.status-badge').className = `status-badge status-${finalStatus}`;
        // Mark as done
        actionCell.innerHTML = `<div class="text-emerald-500 text-xs font-black"><i class="fas fa-check-double mr-1"></i> DONE</div>`;
    }
}

function closeActionModal() {
    document.getElementById('actionModal').classList.replace('flex','hidden');
}
</script>
</body>
</html>
