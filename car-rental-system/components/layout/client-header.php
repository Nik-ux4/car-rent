<?php
// /components/layout/client-header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User info
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Client';
$currentPage = basename($_SERVER['PHP_SELF']);

// Base path calculation to load CSS correctly
$currentDir = __DIR__;
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($documentRoot, '', $currentDir);
$relativePath = ltrim($relativePath, '/');
$folderDepth = ($relativePath === '') ? 0 : substr_count($relativePath, '/');
$base = str_repeat('../', $folderDepth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Triple M's Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Client CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>../assets/css/clientstyles.css">

    <!-- Optional icons -->
    <script src="https://unpkg.com/lucide/dist/umd/lucide.min.js"></script>
</head>
<body>

<header class="client-header">
    <div class="header-container">

        <!-- Logo -->
        <div class="header-left">
            <a href="<?php echo $base; ?>../index.php" class="logo">🚗 CarRental</a>
        </div>

        <!-- Hamburger Menu for Mobile -->
        <button class="hamburger" id="hamburgerBtn">
            &#9776;
        </button>

        <!-- Navigation -->
        <nav class="header-nav" id="navMenu">
            <a href="<?php echo $base; ?>dashboard.php" class="nav-link <?php if ($currentPage=='dashboard.php') echo 'active'; ?>">Dashboard</a>
            <a href="<?php echo $base; ?>browse-cars.php" class="nav-link <?php if ($currentPage=='browse-cars.php') echo 'active'; ?>">Browse Cars</a>
            <a href="<?php echo $base; ?>bookings.php" class="nav-link <?php if ($currentPage=='bookings.php') echo 'active'; ?>">My Bookings</a>
            <a href="<?php echo $base; ?>book-car.php" class="nav-link <?php if ($currentPage=='book-car.php') echo 'active'; ?>">Book Car</a>
            <a href="<?php echo $base; ?>account.php" class="nav-link <?php if ($currentPage=='account.php') echo 'active'; ?>">Account</a>

            <!-- Dropdown -->
            <div class="nav-dropdown">
                <span class="nav-link">More ▾</span>
                <div class="dropdown-content">
                    <a href="<?php echo $base; ?>cancel-booking.php">Cancel Booking</a>
                    <a href="<?php echo $base; ?>notifications.php">Booking Notifications</a>
                    <a href="<?php echo $base; ?>wishlist.php">Favorites / Wishlist</a>
                    <a href="<?php echo $base; ?>browse-cars.php">Advanced Filters</a>
                </div>
            </div>
        </nav>

        <!-- User Info & Logout -->
        <div class="header-right">
            <span class="welcome-text">Hi, <?php echo htmlspecialchars($userName); ?></span>
            <a href="<?php echo $base; ?>../auth/logout.php" class="btn-logout">Logout</a>
        </div>

    </div>
</header>

<!-- JS for Hamburger -->
<script>
const hamburgerBtn = document.getElementById('hamburgerBtn');
const navMenu = document.getElementById('navMenu');

hamburgerBtn.addEventListener('click', () => {
    navMenu.classList.toggle('open');
});
</script>

</body>
</html>
