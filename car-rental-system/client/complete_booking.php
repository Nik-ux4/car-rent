<?php
session_start();
require_once '../config/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cars = [];
$res = $conn->query("SELECT * FROM cars WHERE type != 'maintenance' ORDER BY name ASC");
while ($row = $res->fetch_assoc()) $cars[] = $row;

$selected_car_id = $_GET['car_id'] ?? null;
$selected_car = null;
if ($selected_car_id) {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND type != 'maintenance'");
    $stmt->bind_param("i", $selected_car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) $selected_car = $result->fetch_assoc();
    $stmt->close();
}

$bookings = [];
if ($selected_car) {
    $stmt = $conn->prepare("SELECT start_date, end_date FROM bookings WHERE car_id=? AND status='confirmed'");
    $stmt->bind_param("i", $selected_car['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $bookings[] = $row;
    $stmt->close();
}
$booked_json = json_encode($bookings);
$startStep = $selected_car ? 2 : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Booking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        :root { --primary-pink: #e91e63; --primary-gold: #f9bc60; --dark-blue: #0f172a; }
        body{font-family:'Inter', sans-serif; margin:0; padding:0; background:#f9fafb; color: #1e293b;}
        
        /* Layout & Stepper */
        .booking-header{background:linear-gradient(135deg,#0f172a,#1e293b); color:white; text-align:center; padding:50px 20px;}
        .stepper{display:flex; align-items:center; justify-content:center; max-width:800px; margin:-30px auto 40px; background:white; padding:20px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1); position:relative; z-index:10;}
        .step{text-align:center; width:100px; opacity:0.4; transition:0.3s;}
        .step.active{opacity:1;}
        .step-number{width:35px; height:35px; background:#f1f5f9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:bold; color:#94a3b8;}
        .step.active .step-number{background:var(--primary-gold); color:var(--dark-blue);}
        .step-line{flex:1; height:2px; background:#e2e8f0; margin-bottom:25px; max-width:100px;}

        .booking-grid{display:grid; grid-template-columns: 2fr 1fr; gap:25px; max-width:1200px; margin:0 auto; padding:0 20px 50px;}
        .content-card{background:white; border-radius:16px; padding:25px; border:1px solid #f1f5f9; min-height:400px;}
        .step-section{display:none;}
        .step-section.active{display:block; animation: fadeIn 0.4s ease;}
        @keyframes fadeIn { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);}}

        /* Calendar Styling */
        .calendar-container { display: flex; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid #eee; height: 400px; }
        .calendar-side { width: 40%; background: var(--dark-blue); color: white; display: flex; flex-direction: column; justify-content: center; text-align: center; }
        .calendar-side h1 { font-size: 5rem; color: var(--primary-gold); margin: 0; }
        .calendar-main { width: 60%; padding: 20px; display: flex; flex-direction: column; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .calendar-header select { padding: 5px; border-radius: 5px; border: 1px solid #ddd; }
        .calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 700; font-size: 0.8rem; margin-bottom: 10px; color: #475569; }
        .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; flex-grow: 1; }
        .day { height: 35px; display: flex; align-items: center; justify-content: center; cursor: pointer; border-radius: 50%; font-size: 0.9rem; }
        .day.past { color: #cbd5e1; cursor: not-allowed; opacity: 0.5; }
        .day.rented { background: #fee2e2; color: #ef4444; cursor: not-allowed; text-decoration: line-through; }
        .day.selected { background: var(--primary-pink) !important; color: white; }

        /* Modal Popup Styling */
        .modal-overlay { 
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; 
        }
        .modal-box { 
            background: white; padding: 40px; border-radius: 20px; text-align: center; 
            max-width: 400px; width: 90%; transform: scale(0.7); opacity: 0; transition: 0.3s all;
        }
        .modal-box.active { transform: scale(1); opacity: 1; }
        .modal-icon { font-size: 5rem; color: #22c55e; margin-bottom: 20px; }

        .btn-primary { background: var(--primary-gold); color: var(--dark-blue); padding: 12px 25px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; }
        .btn-outline { background: transparent; border: 1px solid #ddd; padding: 12px 25px; border-radius: 10px; cursor: pointer; }
    </style>
</head>
<body>

<?php include '../components/layout/client-header.php'; ?>

<header class="booking-header"><h1>Complete Your Booking</h1></header>

<div class="stepper">
    <div class="step" id="step-1-tab"><div class="step-number">1</div>Vehicle</div>
    <div class="step-line"></div>
    <div class="step" id="step-2-tab"><div class="step-number">2</div>Dates</div>
    <div class="step-line"></div>
    <div class="step" id="step-3-tab"><div class="step-number">3</div>Review</div>
    <div class="step-line"></div>
    <div class="step" id="step-4-tab"><div class="step-number">4</div>Confirm</div>
</div>

<form id="bookingForm">
    <div class="booking-grid">
        <section class="booking-main">
            <?php if ($selected_car): ?>
                <input type="hidden" name="car_id" value="<?= $selected_car['id'] ?>">
                <input type="hidden" name="start_date" id="start_date">
                <input type="hidden" name="end_date" id="end_date">

                <section class="content-card step-section" data-step="2">
                    <div class="calendar-container">
                        <div class="calendar-side">
                            <h2 id="display-month-year">...</h2>
                            <h1 id="display-day">--</h1>
                        </div>
                        <div class="calendar-main">
                            <div class="calendar-header">
                                <button type="button" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <div>
                                    <select id="jumpMonth" onchange="jumpToDate()">
                                        <?php $m = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]; 
                                        foreach($m as $i=>$name) echo "<option value='$i'>$name</option>"; ?>
                                    </select>
                                    <select id="jumpYear" onchange="jumpToDate()">
                                        <option value="2026">2026</option><option value="2027">2027</option>
                                    </select>
                                </div>
                                <button type="button" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-weekdays"><div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div></div>
                            <div class="calendar-days" id="calendarDays"></div>
                        </div>
                    </div>
                    <div class="action-footer">
                        <button type="button" class="btn-outline" onclick="prevStep()">Back</button>
                        <button type="button" class="btn-primary" style="width:auto" onclick="validateDates()">Continue</button>
                    </div>
                </section>

                <section class="content-card step-section" data-step="4">
                    <h3>Confirm Booking</h3>
                    <p>Double check your dates before confirming.</p>
                    <button type="button" id="confirmBtn" class="btn-primary" onclick="submitBooking()">Confirm & Book Now</button>
                </section>
            <?php endif; ?>
        </section>

        <aside>
            <div class="content-card" style="min-height: auto;">
                <h3>Summary</h3>
                <h2 id="sum-total">₱0.00</h2>
            </div>
        </aside>
    </div>
</form>

<div class="modal-overlay" id="successModal">
    <div class="modal-box" id="modalBox">
        <div class="modal-icon"><i class="fas fa-check-circle"></i></div>
        <h2>Booking Successful!</h2>
        <p>Your ride is confirmed. You can view it in your dashboard.</p>
        <button class="btn-primary" onclick="window.location.href='client-dashboard.php'">Go to Dashboard</button>
    </div>
</div>

<script>
let currentStep = <?= $startStep ?>;
let selectedStart = null, selectedEnd = null;
let viewDate = new Date();
const dailyRate = <?= $selected_car['price_per_day'] ?? 0 ?>;
const bookedDates = <?= $booked_json ?? '[]' ?>;

function showStep(step){
    document.querySelectorAll('.step-section').forEach(s=>s.classList.remove('active'));
    document.querySelector(`[data-step="${step}"]`).classList.add('active');
    document.querySelectorAll('.step').forEach((s,i)=>s.classList.toggle('active', i+1<=step));
    currentStep = step;
}
showStep(currentStep);

// AJAX SUBMISSION LOGIC (Fixes the raw text issue)
function submitBooking() {
    const btn = document.getElementById('confirmBtn');
    const form = document.getElementById('bookingForm');
    const formData = new FormData(form);

    btn.disabled = true;
    btn.innerText = "Processing...";

    fetch('process_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json()) // Expect JSON back
    .then(data => {
        if(data.status === 'success') {
            // SHOW THE POP-UP
            const modal = document.getElementById('successModal');
            const box = document.getElementById('modalBox');
            modal.style.display = 'flex';
            setTimeout(() => box.classList.add('active'), 10);
        } else {
            alert("Error: " + data.message);
            btn.disabled = false;
            btn.innerText = "Confirm & Book Now";
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Something went wrong. Please try again.");
        btn.disabled = false;
        btn.innerText = "Confirm & Book Now";
    });
}

// Calendar rendering functions (selectDate, renderCalendar, etc.) omitted for brevity
// but should remain in your code as previously written.

window.onload = renderCalendar;
</script>
</body>
</html>