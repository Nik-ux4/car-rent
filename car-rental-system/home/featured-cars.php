<?php
// featured-cars.php

// Example: You can replace this with real database queries
$cars = [
    [
        "name" => "Toyota Fortuner",
        "category" => "SUV",
        "price" => 2500,
        "image" => "assets/images/cars/fortuner.jpg"
    ],
    [
        "name" => "Honda Civic",
        "category" => "Sedan",
        "price" => 1800,
        "image" => "assets/images/cars/civic.jpg"
    ],
    [
        "name" => "Mercedes Benz S-Class",
        "category" => "Luxury",
        "price" => 5000,
        "image" => "assets/images/cars/benz.jpg"
    ],
    [
        "name" => "Toyota Hiace",
        "category" => "Van",
        "price" => 3000,
        "image" => "assets/images/cars/hiace.jpg"
    ],
    [
        "name" => "Ford Mustang",
        "category" => "Sports",
        "price" => 4500,
        "image" => "assets/images/cars/mustang.jpg"
    ],
];

// Get category filter from URL
$category = isset($_GET['category']) ? $_GET['category'] : null;
?>

<?php include_once "components/layout/header.php"; ?>

<main>
    <section class="section featured-cars-section">
        <h2 class="section-title">
            <?php echo $category ? $category . " Cars" : "Featured Cars"; ?>
        </h2>

        <div class="featured-cars">
            <?php
            foreach ($cars as $car) {
                if ($category && $car['category'] !== $category) continue;
            ?>
                <div class="car-card">
                    <img src="<?php echo $car['image']; ?>" alt="<?php echo $car['name']; ?>">
                    <div class="car-info">
                        <h3><?php echo $car['name']; ?></h3>
                        <p>Category: <?php echo $car['category']; ?></p>
                        <p>Price: ₱<?php echo number_format($car['price'], 2); ?> / day</p>
                        <a href="booking.php?car=<?php echo urlencode($car['name']); ?>" class="btn-primary">Book Now</a>
                        <a href="car-details.php?car=<?php echo urlencode($car['name']); ?>" class="btn-outline">View Details</a>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if (empty(array_filter($cars, fn($c) => !$category || $c['category'] === $category))) : ?>
            <p style="text-align:center; margin-top:2rem;">No cars found in this category.</p>
        <?php endif; ?>
    </section>
</main>
