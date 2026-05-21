<?php
session_start();
require("db_connect.php");

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

/* HANDLE FORM */
if (isset($_POST['submit'])) {

    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $service_id = $_POST['service_id'];
    $schedule = $_POST['schedule'];

    $result = $conn->query("SELECT MAX(appoint_id) AS max_id FROM appointments");
    $row = $result->fetch_assoc();
    $next_id = $row['max_id'] + 1;

    $stmt = $conn->prepare("
        INSERT INTO appointments 
        (appoint_id, appoint_sched, doctor_id, patient_id, service_id, appoint_status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");

    $stmt->bind_param("isiii", $next_id, $schedule, $doctor_id, $patient_id, $service_id);

    if ($stmt->execute()) {
        include 'telegram_Appointment.php';
        require_once 'functions.php';
//================================================================
//       Query AYAW HILABTI PITI KA RON
        
            /* GET PATIENT */
        $patientQuery = $conn->query("
        SELECT patient_name
        FROM Patient
        WHERE patient_id = $patient_id
        ");

        $patient = $patientQuery->fetch_assoc();

        /* GET DOCTOR */
        $doctorQuery = $conn->query("
        SELECT doctor_firstname, doctor_lastname
        FROM Doctor
        WHERE doctor_id = $doctor_id
        ");

        $doctor = $doctorQuery->fetch_assoc();

            /* GET SERVICE */
        $serviceQuery = $conn->query("
        SELECT service_type
        FROM Services
        WHERE service_id = $service_id
        ");

        $service = $serviceQuery->fetch_assoc();
//================================================================        
        $message = "
        🐾 New Appointment Booked

        Patient: {$patient['patient_name']}

        Doctor: Dr. {$doctor['doctor_firstname']} {$doctor['doctor_lastname']}

        Service: {$service['service_type']}

        Schedule: $schedule

        Status: Pending
        ";
        sendAppointmentNotification($message);
//===============================================================
        sendToGoogleSheet(
        "Appointment Booking",
        $patient['patient_name'],
        "Doctor: Dr. {$doctor['doctor_firstname']} {$doctor['doctor_lastname']} | Schedule: $schedule",
        "Pending"
        );
//==============================================================

        header("Location: book_appointment.php?success=1");
        exit();
    } else {
        $error = $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>

<style>
body{
    margin:0;
    font-family:Poppins, sans-serif;
    background:#fff5f7;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

/* CARD */
.box{
    width:420px;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(255,79,135,0.2);
}

/* TITLE */
h2{
    text-align:center;
    color:#ff4f87;
    margin-bottom:20px;
}

/* LABEL */
label{
    display:block;
    margin-top:10px;
    font-weight:600;
    color:#d63384;
}

/* INPUTS */
select, input{
    width:100%;
    padding:10px;
    margin-top:5px;
    border:2px solid #ffd1dc;
    border-radius:10px;
    outline:none;
}

select:focus, input:focus{
    border-color:#ff4f87;
    box-shadow:0 0 6px rgba(255,79,135,0.3);
}

/* BUTTON */
button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:12px;
    background:#ff4f87;
    color:white;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#ff2f72;
}

/* BACK BUTTON */
.back-btn{
    width: 100%;
    padding: 15px;
    margin-top: 15px;
    border: none;
    border-radius: 15px;
    background: #ff8fb1;   /* pink */
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

.back-btn:hover{
    background: #ffb6c1;
}

/* MESSAGES */
.success{
    background:#d4edda;
    color:#155724;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
    text-align:center;
}

.error{
    background:#f8d7da;
    color:#721c24;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}

/* DOCTOR INFO */
#doctorInfo{
    font-size:13px;
    color:#555;
    margin-top:5px;
}

/* SERVICE INFO */
#serviceInfo{
    margin-top:10px;
    background:#fff0f5;
    padding:12px;
    border-radius:10px;
    font-size:14px;
    color:#444;
    border-left:4px solid #ff4f87;
}

.fee{
    color:#ff2f72;
    font-weight:bold;
    font-size:18px;
}
</style>

</head>

<body>

<div class="box">

    <h2>Book Appointment</h2>

    <?php
    if (isset($_GET['success'])) {
        echo "<div class='success'>Appointment booked successfully!</div>";
    }

    if (isset($error)) {
        echo "<div class='error'>$error</div>";
    }
    ?>

    <form method="POST" onsubmit="return validateForm()">

        <label>Patient</label>
        <select name="patient_id" required>
            <option value="">Select Patient</option>
            <?php
            $patients = $conn->query("SELECT * FROM patient");
            while ($p = $patients->fetch_assoc()) {
                echo "<option value='{$p['patient_id']}'>{$p['patient_name']}</option>";
            }
            ?>
        </select>

        <label>Service Type</label>

        <select name="service_id" id="serviceSelect" required>
            <option value="">Select Service</option>

            <?php
            $services = $conn->query("SELECT * FROM services");

            while ($s = $services->fetch_assoc()) {

                echo "
                <option 
                    value='{$s['service_id']}'

                    data-fee='{$s['service_fee']}'

                    data-description='{$s['service_description']}'

                    data-specialization='{$s['required_specialization']}'
                >
                    {$s['service_type']}
                </option>
                ";
            }
            ?>
        </select>

<div id="serviceInfo"></div>

        <label>Doctor</label>
        <select name="doctor_id" id="doctorSelect" required>
            <option value="">Select Doctor</option>
            <?php
            $doctors = $conn->query("SELECT * FROM doctor");
            while ($d = $doctors->fetch_assoc()) {
                echo "
                <option 
                    value='{$d['doctor_id']}'
                    data-specialization='{$d['specialization']}'
                    data-info='Specialization: {$d['specialization']} | Schedule: {$d['work_hours']}'
                >
                    Dr. {$d['doctor_firstname']} {$d['doctor_lastname']} {$d['doctor_suffix']}
                </option>
                ";
            }
            ?>
        </select>

        <p id="doctorInfo"></p>

        <label>Appointment Schedule</label>
        <input type="datetime-local" name="schedule" required>

        <button type="submit" name="submit">Book Appointment</button>

    </form>

    <a href="dashboard.php">
        <button type="button" class="back-btn">← Return to Home</button>
    </a>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const serviceSelect = document.getElementById("serviceSelect");
    const doctorSelect = document.getElementById("doctorSelect");
    const doctorInfo = document.getElementById("doctorInfo");
    const serviceInfo = document.getElementById("serviceInfo");

    function normalize(text) {
        return (text || "")
            .toString()
            .trim()
            .toLowerCase();
    }

    serviceSelect.addEventListener("change", function () {

        const selectedService = this.options[this.selectedIndex];

        const fee = selectedService.getAttribute("data-fee");
        const description = selectedService.getAttribute("data-description");
        const requiredSpec = normalize(
            selectedService.getAttribute("data-specialization")
        );

        // show service info
        serviceInfo.innerHTML = fee ? `
            <strong>Service Description:</strong><br>
            ${description}
            <br><br>
            <span class="fee">Service Fee: ₱${parseFloat(fee).toFixed(2)}</span>
            <br><br>
            <strong>Required Specialist:</strong> ${requiredSpec}
        ` : "";

        // reset doctor
        doctorSelect.selectedIndex = 0;
        doctorInfo.innerText = "";

        let foundDoctorIndex = -1;

        // IMPORTANT: start at 0 (NOT 1)
        for (let i = 0; i < doctorSelect.options.length; i++) {

            const docSpec = normalize(
                doctorSelect.options[i].getAttribute("data-specialization")
            );

            if (docSpec && docSpec === requiredSpec) {
                foundDoctorIndex = i;
                break;
            }
        }

        if (foundDoctorIndex !== -1) {

            doctorSelect.selectedIndex = foundDoctorIndex;

            doctorInfo.innerText =
                doctorSelect.options[foundDoctorIndex].getAttribute("data-info");

        } else {

            doctorInfo.innerText =
                "No available doctor for this specialization.";

            doctorSelect.selectedIndex = 0;
        }
    });

});
</script>

</body>
</html>