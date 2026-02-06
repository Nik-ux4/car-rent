<!-- /components/layout/client-footer.php -->
<style>
/* Make body and html full height */
html, body {
    height: 100%;
    margin: 0;
}

/* Wrapper for content to push footer down */
.page-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Main content grows */
.page-content {
    flex: 1;
}

/* Footer styling */
.sticky-footer {
    background:#0f172a;
    color:white;
    padding:2rem 1rem;
    font-family: 'Inter', sans-serif;
}
.sticky-footer .footer-container {
    max-width:1200px;
    margin:0 auto;
    display:flex;
    flex-wrap:wrap;
    justify-content:space-between;
    gap:2rem;
}
.sticky-footer h3, .sticky-footer h4 { font-weight:700; margin-bottom:0.75rem; }
.sticky-footer p, .sticky-footer li a { font-size:0.9rem; color:#cbd5e1; text-decoration:none; }
.sticky-footer ul { list-style:none; padding:0; margin:0; }

.sticky-footer .social-links a {
    color:white;
    font-size:1.2rem;
    margin-right:0.5rem;
    transition: transform 0.2s, color 0.2s;
}
.sticky-footer .social-links a:hover {
    transform: translateY(-2px);
    color:#f9bc60;
}

.sticky-footer hr { border-color:#1e293b; margin:2rem 0; }
.sticky-footer .copyright { text-align:center; font-size:0.85rem; color:#64748b; }
</style>

<footer class="sticky-footer">
    <div class="footer-container">
        <!-- About Section -->
        <div style="flex:1; min-width:200px;">
            <h3>Car Rental</h3>
            <p>Your trusted partner for premium vehicles. Book, drive, and enjoy hassle-free rides with our reliable fleet.</p>
        </div>

        <!-- Quick Links -->
        <div style="flex:1; min-width:150px;">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="../client/dashboard.php">Dashboard</a></li>
                <li><a href="../client/browse-cars.php">Browse Cars</a></li>
                <li><a href="../client/bookings.php">My Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- Contact Info -->
        <div style="flex:1; min-width:200px;">
            <h4>Contact Us</h4>
            <p><i class="fa-solid fa-phone"></i> +63 912 345 6789</p>
            <p><i class="fa-solid fa-envelope"></i> info@carrental.com</p>
            <p><i class="fa-solid fa-location-dot"></i> Manila, Philippines</p>
        </div>

        <!-- Social Links -->
        <div style="flex:1; min-width:150px;">
            <h4>Follow Us</h4>
            <div class="social-links" style="margin-top:0.5rem;">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>

    <hr>
    <div class="copyright">
        &copy; <?= date('Y') ?> Car Rental. All rights reserved.
    </div>
</footer>
