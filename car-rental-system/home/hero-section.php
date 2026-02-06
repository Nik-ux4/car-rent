<section class="hero-section">
<div class="hero-dots" id="hero-dots">
    <span class="dot active" data-index="0"></span>
    <span class="dot" data-index="1"></span>
    <span class="dot" data-index="2"></span>
</div>
    <div class="hero-slider" id="hero-slider">
        <!-- Slide 1 -->
        <div class="hero-slide active">
            <img src="assets/images/hero1.jpg" alt="Luxury Car">
            <div class="hero-content">
                <h1>Drive Your Dream Car Today!</h1>
                <p>Affordable and reliable car rentals for any occasion.</p>
                <div class="hero-buttons">
                    <a href="client/browse-cars.php" class="btn-primary">Browse Cars</a>
                    <a href="client/booking.php" class="btn-outline">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="hero-slide">
            <img src="assets/images/hero2.jpg" alt="Sports Car">
            <div class="hero-content">
                <h1>Luxury Cars at Great Prices</h1>
                <p>Experience comfort, style, and speed in one ride.</p>
                <div class="hero-buttons">
                    <a href="client/browse-cars.php" class="btn-primary">Browse Cars</a>
                    <a href="client/booking.php" class="btn-outline">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="hero-slide">
            <img src="assets/images/hero3.jpg" alt="Family Car">
            <div class="hero-content">
                <h1>Perfect Cars for Every Trip</h1>
                <p>Safe, reliable, and suitable for the whole family.</p>
                <div class="hero-buttons">
                    <a href="client/browse-cars.php" class="btn-primary">Browse Cars</a>
                    <a href="client/booking.php" class="btn-outline">Book Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slider Controls -->
    <div class="hero-controls">
        <button id="prev-hero">&#10094;</button>
        <button id="next-hero">&#10095;</button>
    </div>

    <!-- Slider Dots -->
    <div class="hero-dots" id="hero-dots">
        <span class="dot active" data-index="0"></span>
        <span class="dot" data-index="1"></span>
        <span class="dot" data-index="2"></span>
    </div>
</section>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".hero-dots .dot");
    const prevBtn = document.getElementById("prev-hero");
    const nextBtn = document.getElementById("next-hero");

    let currentIndex = 0;
    let slideInterval = setInterval(nextSlide, 5000); // Auto slide every 5 seconds

    // Show slide by index
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.style.display = i === index ? "block" : "none";
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle("active", i === index);
        });
        currentIndex = index;
    }

    // Next slide
    function nextSlide() {
        let nextIndex = (currentIndex + 1) % slides.length;
        showSlide(nextIndex);
    }

    // Previous slide
    function prevSlide() {
        let prevIndex = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(prevIndex);
    }

    // Dot click
    dots.forEach(dot => {
        dot.addEventListener("click", () => {
            let index = parseInt(dot.dataset.index);
            showSlide(index);
            resetInterval();
        });
    });

    // Button click
    nextBtn.addEventListener("click", () => {
        nextSlide();
        resetInterval();
    });

    prevBtn.addEventListener("click", () => {
        prevSlide();
        resetInterval();
    });

    // Reset auto-slide interval after manual control
    function resetInterval() {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 5000);
    }

    // Initialize slider
    showSlide(currentIndex);
});
</script>

