<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}


if(!isset($_SESSION['hiddenAppointments'])){
    $_SESSION['hiddenAppointments'] = [];
}

if(isset($_POST['hide_appointment'])){

    $appointmentID = $_POST['hide_appointment'];

    $_SESSION['hiddenAppointments'][] = $appointmentID;
}


$conn = new mysqli("localhost", "root", "", "WebSysSiaIm");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* COUNTS */
$patientCount = $conn->query("SELECT COUNT(*) AS total FROM Patient")->fetch_assoc()['total'] ?? 0;
$appointmentCount = $conn->query("SELECT COUNT(*) AS total FROM Appointments")->fetch_assoc()['total'] ?? 0;
$doctorCount = $conn->query("SELECT COUNT(*) AS total FROM Doctor")->fetch_assoc()['total'] ?? 0;

/* TOTAL REVENUE */
$revenueQuery = $conn->query("
    SELECT IFNULL(SUM(payment_amount),0) AS totalRevenue
    FROM Payment
");
$totalRevenue = $revenueQuery->fetch_assoc()['totalRevenue'] ?? 0;

/* MOST USED SERVICE */
$serviceQuery = $conn->query("
    SELECT Services.service_type, COUNT(*) AS total
    FROM Appointments
    LEFT JOIN Services
        ON Appointments.service_id = Services.service_id
    GROUP BY Services.service_type
    ORDER BY total DESC
    LIMIT 1
");
$topService = $serviceQuery ? $serviceQuery->fetch_assoc() : null;

/* TOP DOCTOR */
$doctorQuery = $conn->query("
    SELECT
        Doctor.doctor_firstname,
        Doctor.doctor_lastname,
        Doctor.doctor_suffix,
        COUNT(Appointments.appoint_id) AS total
    FROM Appointments
    LEFT JOIN Doctor
        ON Appointments.doctor_id = Doctor.doctor_id
    GROUP BY
        Doctor.doctor_id,
        Doctor.doctor_firstname,
        Doctor.doctor_lastname,
        Doctor.doctor_suffix
    ORDER BY total DESC
    LIMIT 1
");

$topDoctor = $doctorQuery ? $doctorQuery->fetch_assoc() : null;

/* SEARCH */
$search = $_GET['search'] ?? "";

/* APPOINTMENTS (SAFE SEARCH) */
if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT 
            Appointments.appoint_id,
            Appointments.appoint_sched,
            Appointments.appoint_status,
            Patient.patient_name,
            Doctor.doctor_firstname,
            Doctor.doctor_lastname,
            Doctor.doctor_suffix
        FROM Appointments
        LEFT JOIN Patient 
            ON Appointments.patient_id = Patient.patient_id
        LEFT JOIN Doctor 
            ON Appointments.doctor_id = Doctor.doctor_id
        WHERE Patient.patient_name LIKE ?
        ORDER BY Appointments.appoint_sched ASC
    ");

    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $appointments = $stmt->get_result();

} else {

    $appointments = $conn->query("
        SELECT 
            Appointments.appoint_id,
            Appointments.appoint_sched,
            Appointments.appoint_status,
            Patient.patient_name,
            Doctor.doctor_firstname,
            Doctor.doctor_lastname,
            Doctor.doctor_suffix
        FROM Appointments
        LEFT JOIN Patient 
            ON Appointments.patient_id = Patient.patient_id
        LEFT JOIN Doctor 
            ON Appointments.doctor_id = Doctor.doctor_id
        ORDER BY Appointments.appoint_sched ASC
    ");
}

/* CALENDAR APPOINTMENTS */
$calendarAppointments = [];

$getAppointments = "
    SELECT 
        appoint_sched,
        appoint_status,
        Patient.patient_name,
        Doctor.doctor_firstname
    FROM Appointments
    LEFT JOIN Patient 
        ON Appointments.patient_id = Patient.patient_id
    LEFT JOIN Doctor 
        ON Appointments.doctor_id = Doctor.doctor_id
";

$result = $conn->query($getAppointments);

if ($result) {
    while ($appt = $result->fetch_assoc()) {

        $date = date('Y-m-d', strtotime($appt['appoint_sched']));

        $calendarAppointments[$date][] = [
            'time' => date('h:i A', strtotime($appt['appoint_sched'])),
            'patient' => $appt['patient_name'] ?? 'Unknown',
            'doctor' => $appt['doctor_firstname'] ?? 'Unknown',
            'status' => $appt['appoint_status']
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">


<style>
/* GLOBAL RESET */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins', sans-serif;
}

body{
    background:#fff5f7;
    display:flex;
}

/* SIDEBAR */
.sidebar{
    width:240px;
    height:100vh;
    background:#ff4f87;
    position:fixed;
    left:0;
    top:0;
    padding:30px 20px;
}

.logo{
    text-align:center;
    font-size:40px;
    margin-bottom:40px;
    color:white;
}

.menu a{
    display:block;
    text-decoration:none;
    color:white;
    padding:14px;
    margin-bottom:10px;
    border-radius:10px;
    font-weight:500;
    transition:0.3s;
}

.menu a:hover{
    background:white;
    color:#ff4f87;
}

/* MAIN */
.main{
    margin-left:240px;
    width:calc(100% - 240px);
    padding:30px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.profile{
    background:white;
    padding:10px 20px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

/* SEARCH */
.search-form{
    display:flex;
    gap:10px;
    margin-bottom:25px;
}

.search-form input{
    flex:1;
    padding:12px;
    border:2px solid #ffd1dc;
    border-radius:10px;
    outline:none;
}

.search-form button{
    border:none;
    background:#ff4f87;
    color:white;
    padding:12px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:500;
}

.search-form button:hover{
    background:#ff2f72;
}

/* DASHBOARD */
.dashboard{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:20px;
}

/* APPOINTMENTS */
.appointment-card{
    background:white;
    padding:20px;
    border-radius:15px;
    margin-bottom:15px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

.appointment-time{
    font-size:24px;
    font-weight:600;
    color:#ff4f87;
}

.appointment-patient{
    font-size:16px;
    margin:10px 0;
}

.status{
    display:inline-block;
    padding:6px 10px;
    border-radius:8px;
    font-size:13px;
}

.checked{
    background:#c8f7c5;
    color:#1b5e20;
}

.pending{
    background:#ffe0b2;
    color:#8a4b00;
}

/* RIGHT PANEL */
.right-panel{
    display:flex;
    flex-direction:column;
    gap:15px;
}

.summary,
.mini-card{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

/* SUMMARY */
.summary-box{
    display:flex;
    justify-content:space-between;
}

/* BUTTONS */
.refresh-btn{
    display:block;
    width:100%;
    padding:12px;
    text-align:center;
    text-decoration:none;
    border-radius:10px;
    background:#ff4f87;
    color:white;
    font-weight:600;
    margin-bottom:10px;
}

.refresh-btn:hover{
    background:#ff2f72;
}

.logout{
    display:block;
    text-align:center;
    padding:12px;
    background:#ff4d6d;
    color:white;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
}

.logout:hover{
    background:#e63946;
}

/* CALENDAR */
.calendar-card{
    text-align:center;
}

#calendar-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

#calendar-header button{
    border:none;
    background:#ff4f87;
    color:white;
    padding:6px 10px;
    border-radius:8px;
    cursor:pointer;
}

#calendar{
    width:100%;
    border-collapse:collapse;
}

#calendar th{
    color:#ff4f87;
    padding:8px 0;
    font-size:14px;
}

#calendar td{
    width:14%;
    height:40px;
    text-align:center;
    border-radius:8px;
    cursor:pointer;
}

#calendar td:hover{
    background:#ffe0ea;
}

.today{
    background:#ff4f87;
    color:white;
    font-weight:600;
}

.has-appointment{
    background:#ffd6e5;
    color:#ff2f72;
    font-weight:600;
}

#appointment-list{
    text-align:left;
    max-height:250px;
    overflow-y:auto;
    margin-top:15px;
}

.calendar-appointment{
    background:#fff5f7;
    border-left:4px solid #ff4f87;
    padding:10px;
    border-radius:10px;
    margin-bottom:10px;
    font-size:14px;
}

/* ANALYTICS */
.analytics-circle-grid{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap:15px;
    margin-top:10px;
}

.circle-card{
    background:white;
    padding:15px;
    border-radius:15px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
    text-align:center;
}

.circle-card h3{
    font-size:14px;
    color:#ff4f87;
    margin-bottom:10px;
}

.glass-circle-wrapper{
    position:relative;
    width:90px;
    height:90px;
    margin:0 auto 10px;
}

.progress-ring{
    transform:rotate(-90deg);
}

.circle-center-value{
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    font-size:16px;
    font-weight:700;
    color:#333;
}

.circle-caption{
    font-size:12px;
    color:#777;
}

/* INFO BOX */
.analytics-info{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-top:10px;
}

.info-item{
    padding:12px 14px;
    border-radius:12px;
    background:#f9fafb;
    border:1px solid #eee;
    transition:0.3s;
}

.info-item:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 15px rgba(0,0,0,0.08);
}

.info-item .label{
    display:block;
    font-size:12px;
    color:#888;
    margin-bottom:4px;
    font-weight:500;
}

.info-item .value{
    font-size:15px;
    font-weight:700;
    color:#111;
}

.info-item.revenue{border-left:4px solid #ff4f87;}
.info-item.service{border-left:4px solid #10b981;}
.info-item.doctor{border-left:4px solid #a855f7;}

/* STAFF */
.staff-card{
    background:white;
    padding:18px;
    border-radius:15px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
    text-align:center;
    transition:0.3s;
}

.staff-card:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(0,0,0,0.12);
}

.staff-card h2{
    font-size:16px;
    color:#ff4f87;
    margin-bottom:10px;
}

.staff-name{
    font-size:18px;
    font-weight:700;
    color:#222;
    margin-bottom:5px;
}

.staff-role{
    font-size:13px;
    color:#777;
    margin-bottom:15px;
}

/* BUTTONS */
.logout-btn{
    display:block;
    padding:10px;
    border-radius:10px;
    background:#ff4d6d;
    color:white;
    text-decoration:none;
    font-weight:600;
    text-align:center;
    transition:0.3s;
}

.logout-btn:hover{
    background:#e63946;
    transform:scale(1.03);
}

.profile-btn{
    background:white;
    border:none;
    padding:10px 16px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
    cursor:pointer;
    font-weight:600;
    color:#333;
    transition:0.3s;
}

.profile-btn:hover{
    transform:translateY(-2px);
    background:#fff0f5;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.4);
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:white;
    padding:20px;
    border-radius:15px;
    width:300px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
}

.close-btn{
    margin-top:15px;
    padding:8px 12px;
    border:none;
    background:#ff4d6d;
    color:white;
    border-radius:8px;
    cursor:pointer;
}

.modal{
    display:none;
    position:fixed;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
}

.modal-content{
    width:400px;
    background:white;
    margin:8% auto;
    padding:25px;
    border-radius:15px;
    position:relative;
}

.modal-content input{
    width:100%;
    padding:10px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:8px;
}

.close{
    position:absolute;
    right:15px;
    top:10px;
    font-size:25px;
    cursor:pointer;
}

.remove-btn{
    background:#ff4f87;
    color:white;
    border:none;
    padding:8px 12px;
    border-radius:8px;
    cursor:pointer;
}

.remove-btn:hover{
    opacity:0.9;
}
</style>

</head>

<body>

<div class="sidebar">
    <div class="logo">🐾 Clinic</div>
    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="patient.php">Patients</a>
        <a href="bookings.php">Bookings</a>
        <a href="payment.php">Payments</a>
        <a href="products.php">Products</a>
    </div>
</div>

<div class="main">

<div class="topbar">

<form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Search patient..."
           value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
</form>

<button class="profile-btn" onclick="openProfileModal()">
    <?php echo $_SESSION['staff_firstname'] ?? 'Profile'; ?>
</button>

<!-- Profile Modal -->
<div id="profileModal" class="modal">

    <div class="modal-content">

        <span class="close" onclick="closeProfileModal()">&times;</span>

        <h2>Edit Profile</h2>

        <form action="update_profile.php" method="POST">

            <input type="hidden"
                   name="staff_id"
                   value="<?= $_SESSION['staff_id'] ?? '' ?>">

            <label>First Name</label>
            <input type="text"
                   name="staff_firstname"
                   value="<?= $_SESSION['staff_firstname'] ?? '' ?>"
                   required>

            <label>Last Name</label>
            <input type="text"
                   name="staff_lastname"
                   value="<?= $_SESSION['staff_lastname'] ?? '' ?>"
                   required>

            <label>Suffix</label>
            <input type="text"
                   name="staff_suffix"
                   value="<?= $_SESSION['staff_suffix'] ?? '' ?>">

            <label>Email</label>
            <input type="email"
                   name="email"
                   value="<?= $_SESSION['email'] ?? '' ?>"
                   required>

            <label>Contact Number</label>
            <input type="text"
                   name="contact_number"
                   value="<?= $_SESSION['contact_number'] ?? '' ?>"
                   required>

            <label>New Password</label>
            <input type="password"
                   name="passw"
                   placeholder="Leave blank if unchanged">

            <label>Role</label>
            <input type="text"
                   value="<?= $_SESSION['role'] ?? '' ?>"
                   readonly>

            <button type="submit" name="update">
                Save Changes
            </button>

        </form>

    </div>

</div>
</div>

<div class="dashboard">

<div>

<h2 style="color:#ff4f87;margin-bottom:15px;">Today's Appointments</h2>

    <?php if($appointments && $appointments->num_rows > 0): ?>

        <?php
        $hasVisibleAppointments = false;
        ?>

        <?php while($row = $appointments->fetch_assoc()): ?>

            <?php
            if(in_array($row['appoint_id'], $_SESSION['hiddenAppointments'])){
                continue;
            }

            $hasVisibleAppointments = true;
            ?>

            <div class="appointment-card">

                <div class="appointment-time">
                    <?php echo $row['appoint_sched']; ?>
                </div>

                <div class="appointment-patient">
                    <?php echo $row['patient_name'] ?? 'Unknown Patient'; ?>
                </div>

                <div class="status <?php echo ($row['appoint_status'] == 'Checked In') ? 'checked' : 'pending'; ?>">
                    <?php echo $row['appoint_status']; ?>
                </div>

            </div>

        <?php endwhile; ?>

        <?php if(!$hasVisibleAppointments): ?>
            <div class="appointment-card">
                <div class="appointment-patient"
                    style="text-align:center;color:#ff4f87;font-weight:600;">
                    No appointments today
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>

        <div class="appointment-card">
            <div class="appointment-patient"
                style="text-align:center;color:#ff4f87;font-weight:600;">
                No appointments today
            </div>
        </div>

    <?php endif; ?>

</div>

<div class="right-panel">

<a href="dashboard.php" class="refresh-btn">⟳ Refresh</a>

<div class="mini-card calendar-card">

<h2>Calendar</h2>

<div id="calendar-header">
    <button onclick="changeMonth(-1)">◀</button>
    <h3 id="month-year"></h3>
    <button onclick="changeMonth(1)">▶</button>
</div>

<table id="calendar">
<thead>
<tr>
<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
</tr>
</thead>
<tbody id="calendar-body"></tbody>
</table>

<div id="appointment-list"></div>

</div>

<div class="summary">

<h2>Summary</h2>

<div class="analytics-circle-grid">

    <!-- PATIENTS -->
    <div class="circle-card">
        <h3>Patients</h3>

        <div class="glass-circle-wrapper">
            <svg class="progress-ring" width="90" height="90">
                <circle cx="45" cy="45" r="35"
                    stroke="rgba(0,0,0,0.08)"
                    stroke-width="8"
                    fill="transparent"/>

                <circle cx="45" cy="45" r="35"
                    stroke="#ff4f87"
                    stroke-width="8"
                    stroke-linecap="round"
                    fill="transparent"
                    stroke-dasharray="219.91"
                    stroke-dashoffset="<?php echo 219.91 - (219.91 * min(1, $patientCount / 200)); ?>"/>
            </svg>

            <div class="circle-center-value">
                <?php echo $patientCount; ?>
            </div>
        </div>

        <div class="circle-caption">Registered</div>
    </div>

    <!-- APPOINTMENTS -->
    <div class="circle-card">
        <h3>Appointments</h3>

        <div class="glass-circle-wrapper">
            <svg class="progress-ring" width="90" height="90">
                <circle cx="45" cy="45" r="35"
                    stroke="rgba(0,0,0,0.08)"
                    stroke-width="8"
                    fill="transparent"/>

                <circle cx="45" cy="45" r="35"
                    stroke="#10b981"
                    stroke-width="8"
                    stroke-linecap="round"
                    fill="transparent"
                    stroke-dasharray="219.91"
                    stroke-dashoffset="<?php echo 219.91 - (219.91 * min(1, $appointmentCount / 100)); ?>"/>
            </svg>

            <div class="circle-center-value">
                <?php echo $appointmentCount; ?>
            </div>
        </div>

        <div class="circle-caption">Scheduled</div>
    </div>

    <!-- DOCTORS -->
    <div class="circle-card">
        <h3>Doctors</h3>

        <div class="glass-circle-wrapper">
            <svg class="progress-ring" width="90" height="90">
                <circle cx="45" cy="45" r="35"
                    stroke="rgba(0,0,0,0.08)"
                    stroke-width="8"
                    fill="transparent"/>

                <circle cx="45" cy="45" r="35"
                    stroke="#a855f7"
                    stroke-width="8"
                    stroke-linecap="round"
                    fill="transparent"
                    stroke-dasharray="219.91"
                    stroke-dashoffset="<?php echo 219.91 - (219.91 * min(1, $doctorCount / 20)); ?>"/>
            </svg>

            <div class="circle-center-value">
                <?php echo $doctorCount; ?>
            </div>
        </div>

        <div class="circle-caption">Active</div>
    </div>

</div>

<hr style="margin:15px 0;">

<div class="analytics-info">

    <div class="info-item revenue">
        <span class="label">Total Revenue</span>
        <span class="value">₱<?php echo number_format($totalRevenue,2); ?></span>
    </div>

    <div class="info-item service">
        <span class="label">Most Used Service</span>
        <span class="value">
            <?php echo $topService['service_type'] ?? 'No Data'; ?>
        </span>
    </div>

    <div class="info-item doctor">
        <span class="label">Top Doctor</span>
        <span class="value">
            Dr. <?php echo $topDoctor['doctor_firstname'] ?? 'No Data'; ?>
        </span>
    </div>

</div>

</div>

<div class="staff-card">

    <h2>Staff Info</h2>

    <div class="staff-name">
        <?php echo $_SESSION['staff_firstname']; ?>
    </div>

    <div class="staff-role">
        <?php echo $_SESSION['role']; ?>
    </div>

</div>

<a href="login.php" class="logout-btn">Logout</a>

</div>

</div>

<script>
const appointments = <?php echo json_encode($calendarAppointments); ?>;

let currentDate = new Date();

const monthYear = document.getElementById("month-year");
const calendarBody = document.getElementById("calendar-body");
const appointmentList = document.getElementById("appointment-list");

function renderCalendar(date){

    calendarBody.innerHTML = "";

    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    const monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

    monthYear.innerText = `${monthNames[month]} ${year}`;

    let row = document.createElement("tr");
    let cellCount = firstDay;

    for(let i=0;i<firstDay;i++){
        row.innerHTML += "<td></td>";
    }

    for(let day=1;day<=lastDate;day++){

        let cell = document.createElement("td");
        cell.innerText = day;

        const fullDate =
            year + "-" +
            String(month + 1).padStart(2,'0') + "-" +
            String(day).padStart(2,'0');

        const today = new Date();

        if(day === today.getDate() && month === today.getMonth() && year === today.getFullYear()){
            cell.classList.add("today");
        }

        if(appointments[fullDate]){
            cell.classList.add("has-appointment");
            cell.onclick = () => showAppointments(fullDate);
        }

        row.appendChild(cell);
        cellCount++;

        if(cellCount % 7 === 0){
            calendarBody.appendChild(row);
            row = document.createElement("tr");
        }
    }

    if(row.children.length > 0){
        calendarBody.appendChild(row);
    }
}

function showAppointments(date){

    appointmentList.innerHTML = "";

    if(!appointments[date]){
        appointmentList.innerHTML = "<p>No appointments.</p>";
        return;
    }

    appointments[date].forEach(appt => {
        appointmentList.innerHTML += `
            <div class="calendar-appointment">
                <strong>${appt.time}</strong><br>
                ${appt.patient}<br>
                Dr. ${appt.doctor}<br>
                Status: ${appt.status}
            </div>
        `;
    });
}

function changeMonth(dir){
    currentDate.setMonth(currentDate.getMonth() + dir);
    renderCalendar(currentDate);
}

renderCalendar(currentDate);

function openProfileModal(){
    document.getElementById("profileModal").style.display = "block";
}

function closeProfileModal(){
    document.getElementById("profileModal").style.display = "none";
}

window.onclick = function(event){

    let modal = document.getElementById("profileModal");

    if(event.target == modal){
        modal.style.display = "none";
    }

}

</script>

<div id="profileModal" class="modal">

    <div class="modal-content">

        <h2>Staff Profile</h2>

        <p><b>Name:</b> <?php echo $_SESSION['staff_firstname']; ?></p>
        <p><b>Role:</b> <?php echo $_SESSION['role']; ?></p>

        <button onclick="closeProfileModal()" class="close-btn">Close</button>

    </div>

</div>

</body>
</html>