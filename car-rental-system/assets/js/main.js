// ===== HEADER & MENU =====
const header = document.querySelector(".header");
const menuBtn = document.querySelector(".menu-toggle"); // if you have another menu
const mobileMenu = document.querySelector(".mobile-menu");
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('nav-menu');

// Add 'scrolled' class on header when scrolling
window.addEventListener("scroll", () => {
  header.classList.toggle("scrolled", window.scrollY > 20);
});

// Mobile menu toggle
if(menuBtn && mobileMenu) {
  menuBtn.addEventListener("click", () => {
    mobileMenu.classList.toggle("active");
  });
}

// Hamburger menu toggle
if(hamburger && navMenu) {
  hamburger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    hamburger.classList.toggle('active');
  });
}