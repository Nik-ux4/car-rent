<?php
session_start();
require_once '../config/dbconnect.php';

// Admin check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Live GPS Tracking | Triple M</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
#map { height: 70vh; border-radius: 20px; }
.status-badge { padding: 4px 10px; border-radius: 99px; font-size: 10px; font-weight: 800; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-confirmed { background: #dcfce7; color: #166534; }
.status-completed { background: #e0f2fe; color: #075985; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body class="bg-[#f8fafc] flex text-[#1e293b]">

<!-- Sidebar -->
<?php include __DIR__ . '/../components/layout/admin-sidebar.php'; ?>

<main class="flex-1 ml-64 p-8">

<h1 class="text-3xl font-bold mb-6">Live Vehicle Tracking</h1>

<div id="map"></div>

<!-- Table of active bookings -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mt-8">
    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
        <h3 class="font-bold text-slate-800">Active Bookings & Vehicles</h3>
        <span class="text-xs text-slate-400">Updated every 5 seconds</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left" id="bookingsTable">
            <thead class="bg-slate-50 text-[10px] uppercase text-slate-400 font-black">
                <tr>
                    <th class="px-6 py-4">Ref #</th>
                    <th class="px-6 py-4">Vehicle</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Start</th>
                    <th class="px-6 py-4">End</th>
                    <th class="px-6 py-4">Lat</th>
                    <th class="px-6 py-4">Lng</th>
                    <th class="px-6 py-4">Speed (km/h)</th>
                </tr>
            </thead>
            <tbody id="bookingsBody" class="divide-y divide-slate-50"></tbody>
        </table>
    </div>
</div>

</main>

<script>
const map = L.map('map').setView([7.0731, 125.6128], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let markers = {};
let polylines = {};

async function loadData() {
    try {
        const res = await fetch('live-locations-data.php');
        const bookings = await res.json();

        const tbody = document.getElementById('bookingsBody');
        tbody.innerHTML = '';

        bookings.forEach(booking => {
            // Check if we actually have coordinates for this booking
            if (!booking.latitude || !booking.longitude) return;

            const pos = [parseFloat(booking.latitude), parseFloat(booking.longitude)];
            const carId = booking.car_id;

            // 1. Update or Create Marker
            const popupContent = `
                <div class="text-sm">
                    <b class="text-blue-600">${booking.car_brand} ${booking.car_name}</b><br>
                    <span class="text-gray-500">Driver:</span> ${booking.fullname ?? 'N/A'}<br>
                    <span class="text-gray-500">Speed:</span> ${booking.speed ?? 0} km/h<br>
                    <hr class="my-1">
                    <span class="text-xs">Ref: ${booking.reference_number}</span>
                </div>
            `;

            if (markers[carId]) {
                markers[carId].setLatLng(pos).setPopupContent(popupContent);
            } else {
                markers[carId] = L.marker(pos).addTo(map).bindPopup(popupContent);
            }

            // 2. Append Row to Table
            const row = document.createElement('tr');
            row.className = "hover:bg-slate-50/50 transition-colors border-b border-slate-50";
            row.innerHTML = `
                <td class="px-6 py-4 font-bold text-slate-700">${booking.reference_number}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <img src="../${booking.car_image}" class="w-10 h-6 rounded object-cover border" onerror="this.src='https://via.placeholder.com/50'">
                        <span>${booking.car_name}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-500">${booking.fullname ?? 'N/A'}</td>
                <td class="px-6 py-4"><span class="status-badge status-${booking.booking_status}">${booking.booking_status}</span></td>
                <td class="px-6 py-4 text-xs">${booking.start_date}</td>
                <td class="px-6 py-4 text-xs">${booking.end_date}</td>
                <td class="px-6 py-4 font-mono text-[10px]">${booking.latitude}, ${booking.longitude}</td>
                <td class="px-6 py-4 text-center font-bold">${booking.speed ?? 0}</td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error("Error fetching GPS data:", error);
    }
}

// Initial load + auto refresh every 5 seconds
loadData();
setInterval(loadData, 5000);

</script>
</body>
</html>
