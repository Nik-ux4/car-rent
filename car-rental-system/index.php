<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Rental System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="min-h-screen">

    <!-- Header -->
    <?php include_once "components/layout/header.php"; ?>

    <!-- Main Content -->
    <main>
        <?php include_once "home/hero-section.php"; ?>
        <?php include_once "home/featured-cars.php"; ?>
        <?php include_once "home/why-choose-us.php"; ?>
        <?php include_once "home/contact.php"; ?>
        <?php include_once "home/about.php"; ?>
    </main>

    <!-- Footer -->
    <?php include_once "components/layout/footer.php"; ?>
</body>
</html>
