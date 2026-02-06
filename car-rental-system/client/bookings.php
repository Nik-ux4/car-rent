<?php
session_start();
require_once '../config/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Client';

// Fetch bookings with car info, exclude cleared ones
$query = "
SELECT 
    b.id AS booking_id,
    b.user_id,
    b.car_id,
    b.start_date,
    b.end_date,
    b.status,
    b.created_at AS booking_created,
    c.name AS car_name,
    c.brand,
    c.type,
    c.image,
    c.price_per_day
FROM bookings b
JOIN cars c ON b.car_id = c.id
WHERE b.user_id = ? AND b.cleared = 0
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) die("SQL Prepare Error: " . $conn->error);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/**
 * Innovated Status Badge Logic
 */
function statusBadge($status, $startDate, $endDate) {
    $status = strtolower($status);
    $today = date('Y-m-d');
    
    if ($status === 'confirmed') {
        if ($today < $startDate) {
            return "<span class='badge-pill badge-reserved'>📅 Reserved </span>";
        } elseif ($today >= $startDate && $today <= $endDate) {
            return "<span class='badge-pill badge-ontrip'>🚗 Rented / On Trip</span>";
        } elseif ($today > $endDate) {
            return "<span class='badge-pill badge-completed'>🏁 Completed</span>";
        }
    }

    $colors = [
        'pending'   => 'badge-pending',
        'confirmed' => 'badge-confirmed',
        'completed' => 'badge-completed',
        'cancelled' => 'badge-cancelled'
    ];
    $icons = [
        'pending'   => '⏳',
        'confirmed' => '✅',
        'completed' => '🏁',
        'cancelled' => '❌'
    ];
    
    $class = $colors[$status] ?? 'badge-completed';
    $icon = $icons[$status] ?? '';
    return "<span class='badge-pill $class'>$icon ".ucfirst($status)."</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Bookings</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/clientstyles.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #3498db;
        --trip: #8e44ad;
        --success: #27ae60;
        --danger: #e74c3c;
    }
    body { font-family:'Inter', sans-serif; background:#f4f7f6; margin:0; color:#333; }
    .dashboard-main { max-width:1100px; margin:3rem auto; padding:0 1rem; }
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { font-size:2.2rem; margin-bottom:0.5rem; }

    .bookings-list { display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:1.5rem; }

    .booking-card { 
        background:#fff; border-radius:15px; overflow:hidden; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: all 0.3s ease;
        display:flex; flex-direction:column; border: 1px solid #eee;
    }
    .booking-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    
    .booking-card.ontrip { border-left: 5px solid var(--trip); }
    .booking-card.completed, .booking-card.cancelled { opacity: 0.8; filter: grayscale(0.3); }

    .booking-image { position: relative; height: 180px; }
    .booking-image img { width:100%; height:100%; object-fit:cover; }

    .booking-details { padding:1.5rem; flex-grow:1; display:flex; flex-direction:column; }
    .booking-details h3 { font-size:1.4rem; margin:0 0 0.5rem 0; }
    .car-meta { font-size:0.85rem; color:#888; font-weight:600; text-transform:uppercase; margin-bottom:1rem; }
    
    .stats-grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:1.2rem; font-size:0.9rem; }
    .stat-box { background:#f8f9fa; padding:8px; border-radius:8px; text-align:center; }
    .stat-label { display:block; font-size:0.7rem; color:#999; text-transform:uppercase; }
    .stat-value { font-weight:700; color:#444; }

    .badge-pill { padding:0.4rem 0.8rem; border-radius:50px; color:#fff; font-weight:600; font-size:0.8rem; display:inline-block; }
    .badge-pending { background:#f39c12; }
    .badge-confirmed { background:#27ae60; }
    .badge-reserved { background:#27ae60; border: 2px solid #fff; box-shadow: 0 0 0 1px #27ae60; }
    .badge-completed { background:#2980b9; }
    .badge-cancelled { background:#c0392b; }
    .badge-ontrip { background:var(--trip); animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity:1; } 50% { opacity:0.7; } 100% { opacity:1; } }

    .action-group { display:flex; gap:10px; margin-top:auto; padding-top:1.5rem; }
    .btn { flex:1; padding:0.7rem; border-radius:8px; border:none; cursor:pointer; font-weight:600; font-size:0.85rem; transition:0.2s; text-align:center; }
    .btn-view { background:#eef2f7; color:#34495e; }
    .btn-view:hover { background:#dde4ed; }
    .btn-cancel { background:#fff1f0; color:var(--danger); }
    .btn-cancel:hover { background:var(--danger); color:#fff; }
    .btn-clear { background:#f5f5f5; color:#777; width: 100%; margin-top:10px; }
    .btn-clear:hover { background:#e0e0e0; color:#333; }

    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; }
    .modal-content { background:#fff; border-radius:12px; padding:2rem; max-width:450px; width:90%; animation: slideUp 0.3s ease; }
    @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
</style>

<script>
function openModal(id) { document.getElementById('modal-'+id).style.display = 'flex'; }
function closeModal(id) { document.getElementById('modal-'+id).style.display = 'none'; }

function executeClear(id) {
    fetch('clear_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'booking_id=' + id
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const card = document.querySelector('.booking-card[data-id="'+id+'"]');
            card.style.transform = "scale(0.9)";
            card.style.opacity = "0";
            setTimeout(() => card.remove(), 300);
            closeModal('clear-' + id);
        } else {
            alert(data.message);
        }
    });
}
</script>
</head>
<body>

<?php include '../components/layout/client-header.php'; ?>

<main class="dashboard-main">
    <section class="page-header">
        <h1>My Bookings</h1>
        <p>Current time is <strong>Feb 3</strong>. View your upcoming Feb 5 schedules below.</p>
    </section>

    <div class="bookings-list">
        <?php if($bookings): ?>
            <?php foreach($bookings as $booking): 
                $today = date('Y-m-d');
                $start = $booking['start_date'];
                $end = $booking['end_date'];
                $days = max(1, ceil((strtotime($end) - strtotime($start))/86400));
                $totalPrice = $booking['price_per_day'] * $days;
                
                $status_raw = strtolower($booking['status']); 
                $card_class = $status_raw;
                
                if ($status_raw === 'confirmed' && $today >= $start && $today <= $end) {
                    $card_class = 'ontrip';
                }
            ?>
                <div class="booking-card <?= $card_class; ?>" data-id="<?= $booking['booking_id']; ?>">
                    <div class="booking-image">
                        <img src="../<?= htmlspecialchars($booking['image']); ?>" alt="Car Image">
                    </div>
                    
                    <div class="booking-details">
                        <div class="car-meta"><?= htmlspecialchars($booking['brand']); ?> • <?= htmlspecialchars($booking['type']); ?></div>
                        <h3><?= htmlspecialchars($booking['car_name']); ?></h3>
                        
                        <div class="stats-grid">
                            <div class="stat-box">
                                <span class="stat-label">Pickup</span>
                                <span class="stat-value"><?= date('M d', strtotime($start)); ?></span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-label">Return</span>
                                <span class="stat-value"><?= date('M d', strtotime($end)); ?></span>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <?= statusBadge($booking['status'], $start, $end); ?>
                            <span style="font-weight:700; color:var(--success);">₱<?= number_format($totalPrice,2); ?></span>
                        </div>

                        <div class="action-group">
                            <button class="btn btn-view" onclick="openModal(<?= $booking['booking_id']; ?>)">Details</button>

                            <?php if($status_raw == 'pending' || ($status_raw == 'confirmed' && $today < $start)): ?>
                                <button class="btn btn-cancel" onclick="openModal('cancel-<?= $booking['booking_id']; ?>')">Cancel</button>
                            <?php endif; ?>
                        </div>

                        <?php if($status_raw == 'completed' || $status_raw == 'cancelled' || ($status_raw == 'confirmed' && $today > $end)): ?>
                            <button class="btn btn-clear" onclick="openModal('clear-<?= $booking['booking_id']; ?>')">🗑 Remove from View</button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-overlay" id="modal-<?= $booking['booking_id']; ?>">
                    <div class="modal-content">
                        <h2>Reservation Details</h2>
                        <p><strong>Booking ID:</strong> #BK-<?= $booking['booking_id']; ?></p>
                        <p><strong>Status:</strong> <?= ucfirst($booking['status']); ?></p>
                        <p><strong>Price per Day:</strong> ₱<?= number_format($booking['price_per_day'],2); ?></p>
                        <p><strong>Created on:</strong> <?= date('M d, Y', strtotime($booking['booking_created'])); ?></p>
                        <button class="btn btn-view" style="width:100%; margin-top:1rem;" onclick="closeModal(<?= $booking['booking_id']; ?>)">Close</button>
                    </div>
                </div>

                <?php if($status_raw == 'pending' || ($status_raw == 'confirmed' && $today < $start)): ?>
                <div class="modal-overlay" id="modal-cancel-<?= $booking['booking_id']; ?>">
                    <div class="modal-content">
                        <h2>Confirm Cancellation</h2>
                        <p>Do you want to cancel your rental for <strong><?= htmlspecialchars($booking['car_name']); ?></strong>?</p>
                        <div class="action-group">
                            <form method="POST" action="cancel_booking.php" style="flex:1;">
                                <input type="hidden" name="booking_id" value="<?= $booking['booking_id']; ?>">
                                <button type="submit" class="btn btn-cancel" style="width:100%;">Yes, Cancel</button>
                            </form>
                            <button class="btn btn-view" style="flex:1;" onclick="closeModal('cancel-<?= $booking['booking_id']; ?>')">No, Keep</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal-overlay" id="modal-clear-<?= $booking['booking_id']; ?>">
                    <div class="modal-content">
                        <h2 style="color:var(--danger)">Remove Booking?</h2>
                        <p>This will remove <strong><?= htmlspecialchars($booking['car_name']); ?></strong> from your dashboard view. You cannot undo this.</p>
                        <div class="action-group">
                            <button onclick="executeClear(<?= $booking['booking_id']; ?>)" class="btn btn-cancel" style="flex:1; background:var(--danger); color:#fff;">Yes, Remove</button>
                            <button onclick="closeModal('clear-<?= $booking['booking_id']; ?>')" class="btn btn-view" style="flex:1;">Cancel</button>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:4rem;">
                <p style="color:#888;">No active bookings found.</p>
                <a href="browse-cars.php" class="btn btn-view" style="display:inline-block; padding:0.8rem 2rem;">Book a Car Now</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../components/layout/client-footer.php'; ?>
</body>
</html>