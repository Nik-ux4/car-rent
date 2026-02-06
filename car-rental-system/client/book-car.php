<?php
session_start();
require_once '../config/dbconnect.php';

// 🔐 Secure Client Access
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Client';

// Reschedule check
$reschedule_id = $_GET['booking_id'] ?? null;
$existing_booking = null;
if ($reschedule_id) {
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reschedule_id, $user_id);
    $stmt->execute();
    $existing_booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch cars (excluding maintenance)
$cars = [];
$res = $conn->query("SELECT * FROM cars WHERE type != 'maintenance' ORDER BY name ASC");
while ($row = $res->fetch_assoc()) $cars[] = $row;

// Selected car logic
$selected_car_id = $_GET['car_id'] ?? ($existing_booking['car_id'] ?? null);
$selected_car = null;
if ($selected_car_id) {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id=? AND type!='maintenance'");
    $stmt->bind_param("i",$selected_car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows>0) $selected_car = $result->fetch_assoc();
    $stmt->close();
}

// Booked dates for calendar
$bookings = [];
if ($selected_car) {
    $car_id_int = intval($selected_car['id']);
    $query = "SELECT start_date,end_date FROM bookings WHERE car_id=$car_id_int AND status IN ('pending','approved','confirmed')";
    if ($reschedule_id) $query.=" AND id != ".intval($reschedule_id);
    $res = $conn->query($query);
    if ($res instanceof mysqli_result) while($row=$res->fetch_assoc()) $bookings[]=$row;
}
$booked_json = json_encode($bookings);

// Determine start step
$startStep = ($selected_car && !isset($_GET['reselect'])) ? 2 : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking - <?= $selected_car['name'] ?? 'Select Vehicle' ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root { --primary-pink:#e91e63; --primary-gold:#f9bc60; --dark-blue:#0f172a; --success-green:#10b981;}
body{font-family:'Inter',sans-serif;margin:0;padding:0;background:#f9fafb;color:#1e293b;}
.booking-header{background:linear-gradient(135deg,#0f172a,#1e293b); color:white;text-align:center;padding:60px 20px;}
.stepper{display:flex;align-items:center;justify-content:center;max-width:800px;margin:-30px auto 40px;background:white;padding:20px;border-radius:15px;box-shadow:0 10px 25px rgba(0,0,0,0.1);position:relative;z-index:10;}
.step{text-align:center;width:100px;opacity:0.4;transition:0.3s;}
.step.active{opacity:1;}
.step-number{width:35px;height:35px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-weight:bold;color:#94a3b8;}
.step.active .step-number{background:var(--primary-gold);color:var(--dark-blue);}
.step-line{flex:1;height:2px;background:#e2e8f0;margin-bottom:25px;max-width:100px;}
.booking-grid{display:grid;grid-template-columns:2fr 1fr;gap:25px;max-width:1200px;margin:0 auto;padding:0 20px 50px;}
@media(max-width:992px){.booking-grid{grid-template-columns:1fr;}}
.content-card{background:white;border-radius:16px;padding:25px;border:1px solid #f1f5f9;min-height:400px;position:relative;}
.step-section{display:none;}
.step-section.active{display:block;animation: fadeIn 0.4s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.vehicle-horizontal-card{display:flex;align-items:center;gap:20px;background:#fff;border-radius:12px;padding:15px;border:1px solid #f1f5f9;margin-bottom:15px;cursor:pointer;transition:0.3s;}
.vehicle-horizontal-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.05);border-color: var(--primary-gold);}
.vehicle-horizontal-card img{width:150px;height:100px;border-radius:10px;object-fit:cover;}
.badge-type{display:inline-block;padding:3px 10px;border-radius:10px;background:var(--primary-gold);font-size:10px;font-weight:700;margin-bottom:5px;}
.calendar-container{display:flex;background:white;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.05);margin:20px 0;border:1px solid #eee;height:400px;}
.calendar-side{width:35%;background:var(--dark-blue);color:white;padding:25px;text-align:center;display:flex;flex-direction:column;justify-content:center;}
.calendar-side h1{font-size:5rem;margin:0;color:var(--primary-gold);}
.calendar-main{width:65%;padding:20px;}
.calendar-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
.calendar-days{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;}
.day{height:35px;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:50%;font-size:0.85rem;transition:0.2s;}
.day.past{color:#cbd5e1;cursor:not-allowed;opacity:0.5;}
.day.rented{background:#fee2e2;color:#ef4444;cursor:not-allowed;text-decoration:line-through;}
.day.selected{background:var(--primary-pink)!important;color:white;transform:scale(1.1);}
.day.selected-range{background:#fdf2f8;color:var(--primary-pink);border-radius:0;}
.summary-card{background:white;border-radius:16px;padding:20px;border:1px solid #f1f5f9;position:sticky;top:20px;}
.summary-total{display:flex;justify-content:space-between;font-weight:800;font-size:1.4rem;margin-top:15px;color:var(--primary-pink);}
.btn-primary{background:var(--primary-gold);color:var(--dark-blue);padding:14px 30px;border-radius:10px;font-weight:700;border:none;cursor:pointer;width:100%;transition:0.3s;}
.btn-primary:hover{background:#eab308;transform:translateY(-2px);}
.btn-outline{background:white;border:1px solid #cbd5e1;padding:12px 30px;border-radius:10px;font-weight:700;cursor:pointer;}
.action-footer{display:flex;justify-content:space-between;margin-top:30px;}

/* GCash Innovation UI */
.gcash-panel { background:#f0f9ff; border:2px dashed #0ea5e9; border-radius:15px; padding:20px; margin-top:20px; }
.payment-input-group { margin-bottom:15px; }
.payment-input-group label { display:block; font-size:0.85rem; font-weight:600; color:#0369a1; margin-bottom:5px; }
.payment-input-group input { width:100%; padding:10px; border:1px solid #bae6fd; border-radius:8px; outline:none; font-family:monospace; }

/* Custom Alert Modal Styles */
.custom-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.8); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px); }
.custom-modal { background:white; border-radius:20px; padding:30px; max-width:400px; width:90%; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,0.3); animation: popScale 0.3s ease; }
@keyframes popScale { from { transform:scale(0.8); opacity:0; } to { transform:scale(1); opacity:1; } }
.modal-icon { font-size:4rem; margin-bottom:15px; }
.modal-icon.warning { color:var(--primary-gold); }
.modal-icon.error { color:#ef4444; }
.modal-title { font-size:1.5rem; font-weight:800; margin-bottom:10px; }
.modal-msg { color:#64748b; line-height:1.5; margin-bottom:25px; }
</style>
</head>
<body>

<div class="custom-modal-overlay" id="alertModal">
    <div class="custom-modal">
        <div class="modal-icon warning" id="modalIcon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="modal-title" id="modalTitle">Wait!</div>
        <div class="modal-msg" id="modalMsg">Please select dates before continuing.</div>
        <button type="button" class="btn-primary" onclick="closeAlert()">Understood</button>
    </div>
</div>

<?php include '../components/layout/client-header.php'; ?>

<header class="booking-header">
    <h1><?= $reschedule_id ? 'Reschedule Your Ride' : 'Complete Your Booking' ?></h1>
    <p>Follow the steps to confirm your rental</p>
</header>

<div class="stepper">
    <div class="step" id="step-1-tab"><div class="step-number">1</div>Vehicle</div>
    <div class="step-line"></div>
    <div class="step" id="step-2-tab"><div class="step-number">2</div>Dates</div>
    <div class="step-line"></div>
    <div class="step" id="step-3-tab"><div class="step-number">3</div>Review</div>
    <div class="step-line"></div>
    <div class="step" id="step-4-tab"><div class="step-number">4</div>Payment</div>
    <div class="step-line"></div>
    <div class="step" id="step-5-tab"><div class="step-number">5</div>Confirm</div>
</div>

<form action="process_booking.php" method="POST" id="bookingForm" enctype="multipart/form-data">
<div class="booking-grid">
<section class="booking-main">

<section class="content-card step-section" data-step="1">
<h3>Select Your Vehicle</h3>
<?php foreach($cars as $car): ?>
<div class="vehicle-horizontal-card" onclick="selectCar(<?= $car['id'] ?>)">
    <img src="../<?= htmlspecialchars($car['image']) ?>">
    <div class="vehicle-info">
        <span class="badge-type"><?= htmlspecialchars($car['type']) ?></span>
        <h4><?= htmlspecialchars($car['name']) ?></h4>
        <span>₱<?= number_format($car['price_per_day'],2) ?>/day</span>
    </div>
    <div style="margin-left:auto"><i class="fas fa-chevron-right"></i></div>
</div>
<?php endforeach; ?>
</section>

<?php if ($selected_car): ?>
<input type="hidden" name="booking_id" value="<?= $reschedule_id ?>">
<input type="hidden" name="car_id" value="<?= $selected_car['id'] ?>">
<input type="hidden" name="start_date" id="start_date" value="<?= $existing_booking['start_date'] ?? '' ?>">
<input type="hidden" name="end_date" id="end_date" value="<?= $existing_booking['end_date'] ?? '' ?>">

<section class="content-card step-section" data-step="2">
<h3>Pick Your Dates</h3>
<div class="calendar-container">
<div class="calendar-side">
<h2 id="display-month-year">MONTH - YEAR</h2>
<h1 id="display-day">--</h1>
</div>
<div class="calendar-main">
<div class="calendar-header">
<button type="button" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
<div class="calendar-selectors">
<select id="jumpMonth" onchange="jumpToDate()"></select>
<select id="jumpYear" onchange="jumpToDate()"></select>
</div>
<button type="button" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
</div>
<div class="calendar-days" id="calendarDays"></div>
</div>
</div>
<div class="action-footer">
<button type="button" class="btn-outline" onclick="prevStep()">Back</button>
<button type="button" class="btn-primary" onclick="validateDates()">Continue</button>
</div>
</section>

<section class="content-card step-section" data-step="3">
<h3>Review Your Booking</h3>
<div class="vehicle-horizontal-card">
<img src="../<?= htmlspecialchars($selected_car['image']) ?>">
<div class="vehicle-info">
<h4><?= htmlspecialchars($selected_car['name']) ?></h4>
<p>Pickup: <span id="rev-start">--</span></p>
<p>Return: <span id="rev-end">--</span></p>
</div>
</div>
<div class="action-footer">
<button type="button" class="btn-outline" onclick="prevStep()">Edit Dates</button>
<button type="button" class="btn-primary" onclick="nextStep()">Proceed to Payment</button>
</div>
</section>

<section class="content-card step-section" data-step="4">
<h3>Select Payment Method</h3>
<div style="display:flex; gap:15px; margin-top:20px;">
    <label style="flex:1; border:1px solid #f1f5f9; padding:15px; border-radius:12px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
        <input type="radio" name="payment_method" value="cash" style="margin-right:10px;"> Cash on Pickup
    </label>
    <label style="flex:1; border:1px solid #f1f5f9; padding:15px; border-radius:12px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
        <input type="radio" name="payment_method" value="gcash" style="margin-right:10px;"> GCash
    </label>
</div>

<div id="gcash-panel" class="gcash-panel" style="display:none;">
    <div style="text-align:center; margin-bottom:20px;">
        <p style="font-size:0.9rem; margin-bottom:10px;">Scan to pay <strong>₱<span id="payment-total-qr">0</span></strong></p>
        <img id="gcashQRCode" src="" style="width:180px;height:180px; border-radius:10px; border:5px solid white;">
    </div>
    
    <div class="payment-input-group">
        <label>Reference Number (13 Digits)</label>
        <input type="text" name="reference_number" id="ref_no" placeholder="Enter Ref #" maxlength="13">
    </div>

    <div class="payment-input-group">
        <label>Upload Payment Receipt</label>
        <input type="file" name="payment_receipt" id="receipt_file" accept="image/*">
    </div>
</div>

<div class="action-footer">
<button type="button" class="btn-outline" onclick="prevStep()">Back</button>
<button type="button" class="btn-primary" onclick="validatePayment()">Continue</button>
</div>
</section>

<section class="content-card step-section" data-step="5">
<div style="text-align:center; padding:40px 0;">
    <div style="font-size:4rem; color:var(--success-green); margin-bottom:20px;"><i class="fas fa-check-circle"></i></div>
    <h3>Everything Looks Good!</h3>
    <p style="color:#64748b; margin-bottom:30px;">Once you confirm, our team will review your booking and update your status.</p>
    <button type="submit" class="btn-primary" style="max-width:300px;"><?= $reschedule_id ? 'Confirm Reschedule' : 'Confirm & Book Now' ?></button>
</div>
</section>
<?php endif; ?>
</section>

<aside class="booking-sidebar">
<div class="summary-card">
<h3>Summary</h3>
<?php if($selected_car): ?>
<div style="padding-bottom:15px; border-bottom:1px solid #f1f5f9; margin-bottom:15px;">
    <strong><?= htmlspecialchars($selected_car['name']) ?></strong><br>
    <small style="color:#64748b;"><?= htmlspecialchars($selected_car['type']) ?></small>
</div>
<div class="summary-total"><span>Total</span><span id="sum-total">₱0</span></div>
<p style="font-size:0.75rem; color:#94a3b8; margin-top:10px;">* Includes taxes and standard insurance.</p>
<?php endif; ?>
</div>
</aside>
</div>
</form>

<?php include '../components/layout/client-footer.php'; ?>

<script>
let currentStep=<?= $startStep ?>;
let selectedStart="<?= $existing_booking['start_date'] ?? '' ?>";
let selectedEnd="<?= $existing_booking['end_date'] ?? '' ?>";
let viewDate=selectedStart?new Date(selectedStart):new Date();
const dailyRate=<?= $selected_car['price_per_day'] ?? 0 ?>;
const bookedDates=<?= $booked_json ?? '[]' ?>;

function showAlert(title, message, type = 'warning') {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalMsg').innerText = message;
    const icon = document.getElementById('modalIcon');
    icon.className = 'modal-icon ' + type;
    icon.innerHTML = type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-times-circle"></i>';
    document.getElementById('alertModal').style.display = 'flex';
}

function closeAlert() {
    document.getElementById('alertModal').style.display = 'none';
}

function showStep(step){
    document.querySelectorAll('.step-section').forEach(s=>s.classList.remove('active'));
    const target = document.querySelector(`[data-step="${step}"]`);
    if(target) target.classList.add('active');
    
    document.querySelectorAll('.step').forEach((s,i)=>s.classList.toggle('active',i+1<=step));
    currentStep=step;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function nextStep(){ if(currentStep<5) showStep(currentStep+1); }
function prevStep(){ if(currentStep>1) showStep(currentStep-1); }
function selectCar(id){ window.location.href="?car_id="+id; }

function validateDates(){ 
    if(!selectedStart || !selectedEnd){
        showAlert("Dates Required", "Please pick your pickup and return dates on the calendar before proceeding.", "warning");
        return;
    }
    nextStep(); 
}

// INNOVATION: Enhanced Payment Validation
function validatePayment(){
    const payment = document.querySelector('input[name="payment_method"]:checked');
    if(!payment){
        showAlert("Payment Missing", "Please choose either GCash or Cash on Pickup to continue.", "warning");
        return;
    }

    if(payment.value === 'gcash'){
        const refNo = document.getElementById('ref_no').value;
        const receipt = document.getElementById('receipt_file').files.length;
        
        if(refNo.length !== 13){
            showAlert("Invalid Reference", "GCash reference numbers must be exactly 13 digits.", "error");
            return;
        }
        if(receipt === 0){
            showAlert("Receipt Needed", "Please upload a screenshot of your GCash transaction.", "error");
            return;
        }
    }
    nextStep();
}

function populateSelectors(){
    const monthSel=document.getElementById('jumpMonth');
    const yearSel=document.getElementById('jumpYear');
    if(!monthSel||!yearSel) return;
    const months=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    monthSel.innerHTML='';yearSel.innerHTML='';
    months.forEach((m,i)=>monthSel.innerHTML+=`<option value="${i}">${m}</option>`);
    const curYear=new Date().getFullYear();
    for(let y=curYear;y<=curYear+2;y++) yearSel.innerHTML+=`<option value="${y}">${y}</option>`;
}

function renderCalendar(){
    const container=document.getElementById('calendarDays'); if(!container) return;
    container.innerHTML='';
    const year=viewDate.getFullYear(), month=viewDate.getMonth();
    const monthNames=["JANUARY","FEBRUARY","MARCH","APRIL","MAY","JUNE","JULY","AUGUST","SEPTEMBER","OCTOBER","NOVEMBER","DECEMBER"];
    document.getElementById('display-month-year').innerText=`${monthNames[month]} ${year}`;
    
    const jumpMonth = document.getElementById('jumpMonth');
    const jumpYear = document.getElementById('jumpYear');
    if(jumpMonth) jumpMonth.value=month;
    if(jumpYear) jumpYear.value=year;

    const firstDay=new Date(year,month,1).getDay();
    const lastDate=new Date(year,month+1,0).getDate();
    const today=new Date(); today.setHours(0,0,0,0);

    for(let i=0;i<firstDay;i++) container.innerHTML+='<div class="day empty"></div>';
    
    for(let d=1;d<=lastDate;d++){
        const dateStr=`${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const checkDate=new Date(year,month,d);
        let status='available';
        
        if(checkDate<today) status='past';
        else {
            bookedDates.forEach(b=>{
                if(dateStr>=b.start_date && dateStr<=b.end_date) status='rented';
            });
        }
        
        let className='day '+status;
        if(dateStr===selectedStart||dateStr===selectedEnd) className+=' selected';
        if(selectedStart && selectedEnd && dateStr > selectedStart && dateStr < selectedEnd) className+=' selected-range';
        
        const dayDiv=document.createElement('div');
        dayDiv.className=className;
        dayDiv.innerText=d;
        if(status==='available') dayDiv.onclick=()=>selectDate(dateStr,d);
        container.appendChild(dayDiv);
    }
}

function selectDate(dateStr,dayNum){
    if(!selectedStart || (selectedStart && selectedEnd)){
        selectedStart=dateStr;
        selectedEnd=null;
        document.getElementById('display-day').innerText=dayNum;
    } else {
        if(new Date(dateStr) < new Date(selectedStart)){
            selectedStart=dateStr;
            selectedEnd=null;
            document.getElementById('display-day').innerText=dayNum;
        } else {
            selectedEnd=dateStr;
        }
    }
    updatePricing();
    renderCalendar();
}

function updatePricing(){
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    if(startInput) startInput.value=selectedStart;
    if(endInput) endInput.value=selectedEnd;
    
    if(selectedStart && selectedEnd){
        const s=new Date(selectedStart), e=new Date(selectedEnd);
        const diffTime = Math.abs(e - s);
        const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        const total = days * dailyRate;
        document.getElementById('sum-total').innerText="₱"+total.toLocaleString();
        document.getElementById('rev-start').innerText=selectedStart;
        document.getElementById('rev-end').innerText=selectedEnd;
        
        const qrTotal = document.getElementById('payment-total-qr');
        if(qrTotal) qrTotal.innerText=total.toLocaleString();
    }
}

function changeMonth(dir){ viewDate.setMonth(viewDate.getMonth()+dir); renderCalendar(); }
function jumpToDate(){ 
    viewDate=new Date(document.getElementById('jumpYear').value,document.getElementById('jumpMonth').value,1); 
    renderCalendar(); 
}

window.onload=()=>{
    populateSelectors();
    renderCalendar();
    if(selectedStart && selectedEnd) updatePricing();
    showStep(currentStep);
    
    document.querySelectorAll('input[name="payment_method"]').forEach(radio=>{
        radio.addEventListener('change',function(){
            const gcashPanel=document.getElementById('gcash-panel');
            if(this.value==='gcash'){
                const totalText = document.getElementById('sum-total').innerText.replace('₱','').replace(/,/g,'');
                document.getElementById('gcashQRCode').src=`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=GCASH_PAYMENT_PHP_${totalText}`;
                gcashPanel.style.display='block';
            } else { 
                gcashPanel.style.display='none'; 
            }
        });
    });
};
</script>
</body>
</html>