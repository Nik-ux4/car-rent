<?php
session_start();
require_once '../config/dbconnect.php';

// Secure Client Access
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

/* ===============================
    FILTER LOGIC
================================ */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type   = isset($_GET['type']) ? trim($_GET['type']) : 'All'; 

$conditions = [];

// 1. Search Logic
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(name LIKE '%$safeSearch%' OR brand LIKE '%$safeSearch%')";
}

// 2. Category Logic
if ($type !== 'All' && !empty($type)) {
    $safeType = mysqli_real_escape_string($conn, $type);
    $conditions[] = "brand = '$safeType'";
}

$whereSQL = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

/* ===============================
    DATABASE QUERIES
================================ */
$query = "SELECT * FROM cars $whereSQL ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$totalCars = $result ? mysqli_num_rows($result) : 0;

$today = date('Y-m-d');
$bookingQuery = "SELECT car_id FROM bookings WHERE status='confirmed' AND '$today' BETWEEN start_date AND end_date";
$bookingResult = mysqli_query($conn, $bookingQuery);

$bookedCars = [];
if ($bookingResult) {
    while ($row = mysqli_fetch_assoc($bookingResult)) {
        $bookedCars[] = $row['car_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Cars - DriveEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/clientstyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- UI STYLING --- */
        .page-header { background: linear-gradient(135deg,#0f172a,#1e293b); color:white; text-align:center; padding:60px 20px; }
        .page-header h1 { font-size:2.5rem; margin-bottom:10px; font-weight:700; }
        .page-header p { color: #94a3b8; font-size: 1.1rem; }
        
        .filters { max-width:800px; margin:30px auto; padding:0 20px; }
        .filters form { display:flex; flex-direction:column; gap:20px; width:100%; }
        
        .search-container { position:relative; width: 100%; }
        .filters input[type="text"] { width:100%; padding:14px 15px 14px 45px; border-radius:12px; border:1px solid #e2e8f0; font-size: 1rem; outline: none; transition: 0.3s; box-sizing: border-box; }
        .filters input[type="text"]:focus { border-color: #f9bc60; box-shadow: 0 0 0 3px rgba(249, 188, 96, 0.2); }
        .filters .search-icon { position:absolute; left:18px; top:50%; transform:translateY(-50%); color:#94a3b8; }

        .type-buttons { display:flex; gap:10px; flex-wrap:wrap; justify-content: center; }
        .category-btn { padding:10px 20px; border-radius:12px; border:1px solid #e2e8f0; font-weight:700; cursor:pointer; background:white; color: #475569; transition:0.3s; font-size: 14px; }
        .category-btn:hover { border-color: #f9bc60; color: #f9bc60; }
        .category-btn.active { background:#f9bc60; color:#0f172a; border-color:#f9bc60; box-shadow: 0 4px 12px rgba(249, 188, 96, 0.3); }

        /* --- CAR GRID --- */
        .cars-grid { max-width:1200px; margin:0 auto 60px; padding:0 20px; display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:30px; }
        .car-card { background:white; border-radius:24px; overflow:hidden; border:1px solid #f1f5f9; opacity:0; transform:translateY(20px); transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position:relative; }
        .car-card.visible { opacity:1; transform:translateY(0); }
        .car-card:hover { transform:translateY(-10px); box-shadow:0 20px 40px rgba(0,0,0,0.08); }
        .car-card img { width:100%; height:220px; object-fit:cover; }

        .badge-status { position:absolute; top:15px; left:15px; font-size:10px; font-weight:900; padding:6px 12px; border-radius:10px; color:white; text-transform:uppercase; letter-spacing: 0.5px; z-index: 2; }
        .badge-status.available { background:#10b981; }
        .badge-status.rented { background:#ef4444; }
        .badge-status.maintenance { background:#f59e0b; }

        .card-body { padding:25px; }
        .card-body h3 { font-size:1.4rem; font-weight:800; color:#0f172a; margin-bottom:8px; }
        .specs { display:flex; gap:15px; font-size:13px; color:#64748b; margin-bottom:20px; font-weight: 500; }
        
        .price-book { display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f5f9; padding-top:20px; }
        .price { font-weight:900; font-size:20px; color: #0f172a; }
        .price span { font-size: 13px; color: #94a3b8; font-weight: 500; }
        
        .btn-book { background:#f9bc60; border:none; padding:12px 20px; border-radius:12px; cursor:pointer; font-weight:800; transition:0.3s; text-decoration: none; color: #0f172a; font-size: 14px; text-align: center; }
        .btn-book:hover:not(.disabled) { background:#fbbf24; transform: scale(1.02); }
        .btn-book.disabled { background:#e2e8f0; cursor:not-allowed; color:#94a3b8; border: none; }

        /* --- MODAL --- */
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.85); backdrop-filter:blur(8px); z-index:1000; justify-content:center; align-items:center; padding:20px; opacity: 0; transition: 0.3s; }
        .modal-bg.active { display:flex; opacity: 1; }
        .modal-card { background:white; border-radius:30px; max-width:550px; width:100%; padding:35px; position:relative; transform: scale(0.9); transition: 0.3s; }
        .modal-bg.active .modal-card { transform: scale(1); }
        .modal-close { position:absolute; top:25px; right:25px; font-size:28px; cursor:pointer; color:#94a3b8; transition: 0.2s; }
        .modal-badge { display:inline-block; font-size:11px; font-weight:800; padding:5px 12px; border-radius:10px; margin-bottom:15px; text-transform:uppercase; color: white; }
        .modal-badge.available { background:#10b981; }
        .modal-badge.rented { background:#ef4444; }
        .modal-badge.maintenance { background:#f59e0b; }
    </style>
</head>
<body>

<?php include '../components/layout/client-header.php'; ?>

<header class="page-header">
    <h1>Our Vehicle Fleet</h1>
    <p>Premium cars for your next journey</p>
</header>

<div class="filters">
    <form method="GET" id="filterForm">
        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search make or model...">
        </div>

        <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($type) ?>">

        <div class="type-buttons">
            <?php
            $categories = ['All','Sedan','SUV','Van','Luxury','Economy'];
            foreach ($categories as $cat):
                $activeClass = ($type === $cat) ? 'active' : '';
            ?>
                <button type="button" class="category-btn <?= $activeClass ?>" data-value="<?= $cat ?>">
                    <?= $cat ?>
                </button>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<div class="cars-grid">
    <?php if ($totalCars > 0): ?>
        <?php while ($car = mysqli_fetch_assoc($result)):
            $status = strtolower($car['type'] ?? '');
            $isBookable = true;
            $displayStatus = 'available';

            if ($status === 'maintenance') {
                $displayStatus = 'maintenance';
                $isBookable = false;
            } elseif (in_array($car['id'], $bookedCars)) {
                $displayStatus = 'rented';
                $isBookable = false;
            }
        ?>
        <div class="car-card" data-car='<?= json_encode($car) ?>'>
            <div style="position:relative;">
                <img src="../<?= htmlspecialchars($car['image'] ?: 'assets/images/placeholder.png') ?>" alt="<?= htmlspecialchars($car['name']) ?>">
                <span class="badge-status <?= $displayStatus ?>"><?= ucfirst($displayStatus) ?></span>
            </div>
            <div class="card-body">
                <p style="color: #f9bc60; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 4px;"><?= htmlspecialchars($car['brand']) ?></p>
                <h3><?= htmlspecialchars($car['name']) ?></h3>
                <div class="specs">
                    <span><i class="fa-solid fa-users"></i> 5 Seats</span>
                    <span><i class="fa-solid fa-gear"></i> Auto</span>
                </div>
                <div class="price-book">
                    <div class="price">₱<?= number_format($car['price_per_day'], 0) ?><span>/day</span></div>
                    <button type="button" class="btn-book quick-view-btn <?= !$isBookable ? 'disabled' : '' ?>">
                        <?= $isBookable ? 'View Details' : ucfirst($displayStatus) ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:80px 20px;">
            <i class="fa-solid fa-car-burst" style="font-size:60px;color:#e2e8f0;margin-bottom:20px;"></i>
            <h2 style="color: #1e293b;">No Vehicles Found</h2>
            <p style="color:#64748b;">Try adjusting your filters or search terms.</p>
        </div>
    <?php endif; ?>
</div>

<div class="modal-bg" id="modal-bg">
    <div class="modal-card">
        <span class="modal-close" id="modal-close">&times;</span>
        <img id="modal-img" src="" alt="Car Image" style="width:100%; height:260px; object-fit:cover; border-radius:20px;">
        <div class="modal-details" style="margin-top:25px;">
            <span id="modal-badge" class="modal-badge"></span>
            <h2 id="modal-title" style="font-size: 2rem; font-weight: 800; color: #0f172a;"></h2>
            <div id="modal-specs" style="margin:15px 0 25px; color:#64748b; font-size:15px;"></div>
            
            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f5f9; padding-top:25px;">
                <div>
                    <p style="font-size: 12px; color: #94a3b8; font-weight: 600;">Rental Price</p>
                    <h3 id="modal-price" style="font-size:28px; font-weight: 900; color: #0f172a;"></h3>
                </div>
                <a id="modal-book" href="#" class="btn-book" style="padding: 15px 30px; font-size: 16px;">Book Now</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Animation
    document.querySelectorAll('.car-card').forEach((card,index)=>setTimeout(()=>card.classList.add('visible'), index*80));

    // Filter Logic
    const filterForm = document.getElementById('filterForm');
    const typeInput = document.getElementById('typeInput');
    document.querySelectorAll('.category-btn').forEach(button => {
        button.addEventListener('click', function() {
            typeInput.value = this.getAttribute('data-value');
            filterForm.submit();
        });
    });

    // Modal Logic
    const modalBg = document.getElementById('modal-bg');
    const modalClose = document.getElementById('modal-close');
    const modalImg = document.getElementById('modal-img');
    const modalTitle = document.getElementById('modal-title');
    const modalSpecs = document.getElementById('modal-specs');
    const modalPrice = document.getElementById('modal-price');
    const modalBook = document.getElementById('modal-book');
    const modalBadge = document.getElementById('modal-badge');

    document.querySelectorAll('.quick-view-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            const card = e.target.closest('.car-card');
            const car = JSON.parse(card.dataset.car);
            const statusBadge = card.querySelector('.badge-status');
            const status = statusBadge.textContent.toLowerCase();

            modalImg.src = '../' + (car.image || 'assets/images/placeholder.png');
            modalTitle.textContent = car.brand + ' ' + car.name;
            modalSpecs.innerHTML = `<i class="fa-solid fa-users"></i> 5 Seats &nbsp;•&nbsp; <i class="fa-solid fa-gas-pump"></i> Gasoline &nbsp;•&nbsp; <i class="fa-solid fa-gear"></i> Automatic`;
            modalPrice.textContent = '₱' + Number(car.price_per_day).toLocaleString();
            
            modalBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            modalBadge.className = 'modal-badge ' + status;

            if (status !== 'available') {
                modalBook.classList.add('disabled');
                modalBook.href = "javascript:void(0)";
                modalBook.textContent = status === 'maintenance' ? 'Under Maintenance' : 'Already Rented';
            } else {
                modalBook.classList.remove('disabled');
                modalBook.href = 'book-car.php?car_id=' + car.id;
                modalBook.textContent = 'Book This Car';
            }

            modalBg.classList.add('active');
        });
    });

    modalClose.addEventListener('click', () => modalBg.classList.remove('active'));
    window.addEventListener('click', e => { if(e.target === modalBg) modalBg.classList.remove('active'); });
</script>

</body>
</html>