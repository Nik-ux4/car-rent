<aside class="w-64 bg-[#0f172a] h-screen fixed top-0 left-0 flex flex-col p-6 text-white z-40">

    <div class="flex items-center gap-3 mb-10">
        <div class="bg-amber-400 p-2 rounded-lg text-[#0f172a]">
            <i class="fa-solid fa-car-side text-xl"></i>
        </div>
        <h1 class="text-xl font-bold tracking-tight">Triple M Admin</h1>
    </div>

    <nav class="flex-1 space-y-1">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-amber-400/10 text-amber-400 rounded-xl font-semibold">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        <a href="vehicles.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl transition">
            <i class="fa-solid fa-car"></i> Vehicles
        </a>

        <div class="relative">
    <div class="flex items-center justify-between w-full">
        <!-- Bookings link -->
        <a href="bookings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl transition flex-1">
            <i class="fa-solid fa-calendar-check"></i> Bookings
        </a>
        
        <!-- Dropdown toggle button -->
        <button id="bookingsDropdownBtn" class="px-4 py-3 hover:bg-white/5 rounded-xl transition">
            <i id="bookingsDropdownIcon" class="fa-solid fa-chevron-down transition-transform duration-300"></i>
        </button>
    </div>

    <!-- Dropdown menu -->
    <div id="bookingsDropdown" class="ml-6 mt-1 overflow-hidden max-h-0 transition-[max-height] duration-300 ease-in-out">
        <a href="travel-destinations.php" class="flex items-center gap-2 px-4 py-2 hover:bg-white/5 rounded-xl transition text-sm">
            <i class="fa-solid fa-map-location-dot"></i> Travel Destinations
        </a>
    </div>
</div>

        <a href="customers.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl transition">
            <i class="fa-solid fa-users"></i> Customers
        </a>

        <a href="payments.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl transition">
            <i class="fa-solid fa-wallet"></i> Payments
        </a>

    <a href="live-tracking.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl transition">
        <i class="fa-solid fa-location-dot"></i> Live Tracking
    </a>
</nav>

    <div class="pt-6 border-t border-white/10">
        <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl transition">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>

<script>
const btn = document.getElementById('bookingsDropdownBtn');
const menu = document.getElementById('bookingsDropdown');
const icon = document.getElementById('bookingsDropdownIcon');

let isOpen = false; // track state

btn.addEventListener('click', (e) => {
    e.stopPropagation(); // prevent triggering document click

    if(isOpen){
        // close menu
        menu.style.maxHeight = '0px';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        isOpen = false;
    } else {
        // open menu
        menu.style.maxHeight = menu.scrollHeight + 'px';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        isOpen = true;
    }
});

// Optional: close dropdown when clicking outside
document.addEventListener('click', (e) => {
    if(isOpen && !btn.contains(e.target) && !menu.contains(e.target)){
        menu.style.maxHeight = '0px';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        isOpen = false;
    }
});
</script>
</aside>
