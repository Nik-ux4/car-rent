<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Use the correct session variable
$client_logged_in = isset($_SESSION['user_id']); // change from 'client_id' to 'user_id'

// Calculate base path for CSS and links
$currentDir = __DIR__;
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($documentRoot, '', $currentDir);

// Ensure leading slash is removed
$relativePath = ltrim($relativePath, '/');

// Count folder depth
$folderDepth = ($relativePath === '') ? 0 : substr_count($relativePath, '/');

// Build base path
$base = str_repeat('../', $folderDepth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Triple M's Car Rental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/styles.css">

    <!-- Optional Tailwind or icons if used -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide/dist/umd/lucide.min.js"></script>
</head>
<body>
<header class="header">
    <div class="header-container">

        <!-- Logo -->
        <div class="logo">
            <a href="<?php echo $base; ?>index.php">Triple M's CarRental</a>
        </div>

        <!-- Main Navigation -->
        <nav class="nav">
            <ul class="nav-menu">
                <li><a href="<?php echo $base; ?>index.php">Home</a></li>
                <li><a href="<?php echo $base; ?>client/browse-cars.php">Our Fleet</a></li>
                <li><a href="<?php echo $base; ?>client/book-car.php">Book Now</a></li>
                <li><a href="<?php echo $base; ?>about.php">About</a></li>
                <li><a href="<?php echo $base; ?>contact.php">Contact</a></li>
            </ul>
        </nav>

        <!-- Right Buttons -->
        <div class="header-buttons">
            <?php if($client_logged_in): ?>
                <a href="<?php echo $base; ?>client/dashboard.php" class="btn-outline">Dashboard</a>
                <a href="<?php echo $base; ?>auth/logout.php" class="btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base; ?>auth/login.php" class="btn-outline">Sign In</a>
                <a href="<?php echo $base; ?>auth/register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger Menu -->
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

    </div>
</header>

<script>
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const headerButtons = document.querySelector('.header-buttons');

    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        headerButtons.classList.toggle('active');
        hamburger.classList.toggle('active');
    });
</script>
