<?php
session_start();
require_once '../config/dbconnect.php';

// Secure client access
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?redirected=1");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch all bookings
$query = "
SELECT b.id AS booking_id, b.car_id, b.start_date, b.end_date, b.status,
       c.name AS car_name, c.brand, c.type, c.price_per_day, c.image
FROM bookings b
JOIN cars c ON b.car_id = c.id
WHERE b.user_id = ?
ORDER BY b.start_date ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$today = date('Y-m-d');
$currentTime = time();

// --- AUTO-COMPLETE PAST BOOKINGS ---
foreach ($bookings as &$b) {
    $status = strtolower($b['status']);
    $endTime = strtotime($b['end_date']);

    if ($endTime < $currentTime && !in_array($status, ['cancelled','rejected','completed'])) {
        $b['status'] = 'completed';
    }
}
unset($b);

// --- STATS LOGIC ---
$totalBookings = count($bookings);
$upcoming = array_filter($bookings, function($b) use ($today) {
    $status = strtolower($b['status']);
    return $b['start_date'] >= $today && !in_array($status, ['cancelled', 'rejected']);
});
$completed = array_filter($bookings, fn($b) => strtolower($b['status']) === 'completed');

$totalSpent = 0;
foreach ($bookings as $b) {
    $status = strtolower($b['status']);
    if ($status !== 'cancelled' && $status !== 'rejected') {
        $days = max(1, ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400));
        $totalSpent += $b['price_per_day'] * $days;
    }
}

/**
 * INNOVATED STATUS BADGE
 * Displays "Reserved" for confirmed future bookings.
 */
function statusBadge($status, $startDate) {
    $status = strtolower($status);
    $today = date('Y-m-d');
    
    // Check if it's confirmed but in the future
    if (($status === 'approved' || $status === 'confirmed') && $startDate > $today) {
        return "<span class='badge-reserved'>📅 Reserved</span>";
    }

    $colors = [
        'pending'   => 'badge-pending',
        'approved'  => 'badge-approved',
        'confirmed' => 'badge-approved',
        'completed' => 'badge-completed',
        'cancelled' => 'badge-cancelled',
        'rejected'  => 'badge-rejected'
    ];
    $class = $colors[$status] ?? 'badge-completed';
    return "<span class='$class'>".ucfirst($status)."</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-blue: #0288d1; --bg-light: #f7f8fa; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); margin:0; padding:0; color: #333; }
        .dashboard-main { max-width:1200px; margin:2rem auto; padding:0 1rem; }
        
        /* Stats Styling */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem; margin-bottom:2.5rem; }
        .stat-card { background:#fff; padding:1.5rem; border-radius:12px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.02); border: 1px solid #edf2f7; }
        .stat-card h2 { font-size:0.85rem; color:#718096; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; }
        .stat-card p { font-size:1.75rem; font-weight:700; color:#1a202c; margin:0; }

        /* Booking Cards */
        .upcoming-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1.5rem; }
        .booking-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); transition:transform 0.2s; display:flex; flex-direction:column; border: 1px solid #f1f5f9; }
        .booking-card:hover { transform: translateY(-4px); }
        .booking-card img { width:100%; height:180px; object-fit:cover; }
        .booking-info { padding:1.25rem; flex-grow:1; }
        .booking-info h3 { font-size:1.2rem; margin-bottom:0.25rem; font-weight: 700; }
        .booking-info p { font-size:0.9rem; color:#64748b; margin:0.4rem 0; }

        /* Badges */
        .badge-reserved { background: #e0f2fe; color: var(--primary-blue); padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border: 1px solid #bae6fd; }
        .badge-pending { background:#fffbeb; color:#92400e; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .badge-approved { background:#f0fdf4; color:#166534; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .badge-completed { background:#f1f5f9; color:#475569; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .badge-cancelled, .badge-rejected { background:#fef2f2; color:#991b1b; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; }

        /* Buttons */
        .btn-reschedule { display: inline-block; background: #fff; color: var(--primary-blue); border: 1px solid var(--primary-blue); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-top: 1rem; transition: 0.2s; }
        .btn-reschedule:hover { background: var(--primary-blue); color: #fff; }

        /* Table */
        .history-section { margin-top: 4rem; background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        table { width:100%; border-collapse:collapse; margin-top:1rem; }
        th { text-align:left; padding:1rem; color:#64748b; font-size:0.85rem; border-bottom: 2px solid #f1f5f9; }
        td { padding:1rem; border-bottom: 1px solid #f1f5f9; font-size:0.9rem; }
    </style>
</head>
<body>

<?php include '../components/layout/client-header.php'; ?>

<main class="dashboard-main">
    <section class="page-header">
        <h1>Hello, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p style="color: #64748b;">It is currently <?php echo date('F d, Y'); ?>. Manage your rentals below.</p>
    </section>

    <section class="dashboard-stats stats-grid">
        <div class="stat-card"><h2>Total Bookings</h2><p><?php echo $totalBookings; ?></p></div>
        <div class="stat-card"><h2>Upcoming</h2><p><?php echo count($upcoming); ?></p></div>
        <div class="stat-card"><h2>Completed</h2><p><?php echo count($completed); ?></p></div>
        <div class="stat-card"><h2>Total Spent</h2><p>₱<?php echo number_format($totalSpent,2); ?></p></div>
    </section>

    <?php if(count($upcoming)>0): ?>
    <section class="upcoming-bookings">
        <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Your Scheduled Rides</h2>
        <div class="upcoming-cards">
            <?php foreach($upcoming as $b):
                $days = max(1, ceil((strtotime($b['end_date']) - strtotime($b['start_date']))/86400));
                $totalPrice = $b['price_per_day'] * $days;
                $isReserved = (($b['status'] == 'approved' || $b['status'] == 'confirmed') && $b['start_date'] > $today);
            ?>
            <div class="booking-card">
                <img src="../<?php echo htmlspecialchars($b['image']); ?>" alt="Car Image">
                <div class="booking-info">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <h3><?php echo htmlspecialchars($b['car_name']); ?></h3>
                            <p><?php echo htmlspecialchars($b['brand']); ?> • <?php echo htmlspecialchars($b['type']); ?></p>
                        </div>
                        <?php echo statusBadge($b['status'], $b['start_date']); ?>
                    </div>
                    
                    <p style="margin-top:1rem;"><i class="far fa-calendar-alt"></i> <strong><?php echo date('M d', strtotime($b['start_date'])); ?></strong> to <strong><?php echo date('M d', strtotime($b['end_date'])); ?></strong></p>
                    <p><strong>Total Price:</strong> ₱<?php echo number_format($totalPrice,2); ?></p>

                    <?php if($isReserved || strtolower($b['status']) == 'pending'): ?>
                        <a href="complete_booking.php?booking_id=<?php echo $b['booking_id']; ?>&car_id=<?php echo $b['car_id']; ?>" class="btn-reschedule">
                            <i class="fas fa-clock"></i> Reschedule Trip
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="history-section">
        <h2 style="font-size: 1.25rem;">Full Rental History</h2>
        <table>
            <thead>
                <tr>
                    <th>VEHICLE</th>
                    <th>RENTAL DATES</th>
                    <th>STATUS</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bookings as $b): 
                     $days = max(1, ceil((strtotime($b['end_date']) - strtotime($b['start_date']))/86400));
                     $totalPrice = $b['price_per_day'] * $days;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($b['car_name']); ?></strong><br><small><?php echo htmlspecialchars($b['brand']); ?></small></td>
                    <td><?php echo date('m/d/Y', strtotime($b['start_date'])); ?> - <?php echo date('m/d/Y', strtotime($b['end_date'])); ?></td>
                    <td><?php echo statusBadge($b['status'], $b['start_date']); ?></td>
                    <td>₱<?php echo number_format($totalPrice, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<?php include '../components/layout/client-footer.php'; ?>

</body>
</html>