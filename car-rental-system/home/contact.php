<section class="contact-section">
    <div class="container">
        <div class="contact-wrapper">
            <!-- Contact Info -->
            <div class="contact-info">
                <h2>Contact Us</h2>
                <p><i class="fas fa-map-marker-alt"></i> 123 CarRental Street, City</p>
                <p><i class="fas fa-phone-alt"></i> +63 912 345 6789</p>
                <p><i class="fas fa-envelope"></i> info@carrental.com</p>
                <p><i class="fas fa-clock"></i> Mon-Sat, 8AM-6PM</p>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send a Message</h2>
                <form action="contact-process.php" method="POST">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <input type="tel" name="phone" placeholder="Phone Number" required>
                    <textarea name="message" rows="5" placeholder="Your Message" required></textarea>
                    <button type="submit" class="btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>
