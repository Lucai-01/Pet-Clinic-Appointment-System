<?php include "db_connect.php"; ?>

<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

/* ADD PAYMENT */
if (isset($_POST['add_payment'])) {

    $patient_id = $_POST['patient_id'];
    $service_id = $_POST['service_id'];
    $appoint_id = $_POST['appoint_id'];
    $payment_type = $_POST['payment_type'];
    $payment_amount = $_POST['payment_amount'];
    $payment_status = $_POST['payment_status'];
    $payment_date = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
        INSERT INTO Payment
        (patient_id, service_id, appoint_id, payment_type, payment_amount, payment_status, payment_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiisdss",
        $patient_id,
        $service_id,
        $appoint_id,
        $payment_type,
        $payment_amount,
        $payment_status,
        $payment_date
    );

    if ($stmt->execute()) {
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
        $patient_name = $patient['patient_name'];

//================================================================  

        sendToGoogleSheet(
        "Payment",
        $patient_name,
        "Amount: ₱$payment_amount",
        "Paid"
        );
//================================================================

        header("Location: add_payment.php?success=1");
        exit();
    } else {
        $error = $stmt->error;
    }

    $stmt->close();
}

/* PATIENTS */
$patients = $conn->query("SELECT * FROM Patient");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Payment</title>

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
.container{
    width:450px;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(255,105,180,0.2);
}

/* TITLE */
h1{
    text-align:center;
    color:#ff4f87;
    margin-bottom:20px;
}

/* FORM */
.form-group{
    margin-bottom:15px;
}

label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
    color:#d63384;
}

input, select{
    width:100%;
    padding:10px;
    border:2px solid #ffd1dc;
    border-radius:10px;
    outline:none;
}

/* BUTTON */
button{
    width:100%;
    padding:12px;
    background:#ff4f87;
    border:none;
    border-radius:12px;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

button:hover{
    background:#ff2f72;
}

/* SUCCESS / ERROR */
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

/* BACK */
/* BUTTON (Save Payment) */
button{
    width:100%;
    padding:12px;
    background:#ff4f87;
    border:none;
    border-radius:12px;
    color:white;
    font-weight:bold;
    cursor:pointer;
    margin-bottom:10px;
}

button:hover{
    background:#ff2f72;
}

/* BACK BUTTON (same size as save button) */
.back-btn{
    display:block;
    width:100%;
    padding:12px;
    text-align:center;
    background:#5c7cfa;
    color:white;
    border-radius:12px;
    text-decoration:none;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.back-btn:hover{
    background:#3b5bdb;
}
</style>

</head>

<body>

<div class="container">

    <h1>Add Payment</h1>

    <?php
    if (isset($_GET['success'])) {
        echo "<div class='success'>Payment added successfully!</div>";
    }

    if (isset($error)) {
        echo "<div class='error'>$error</div>";
    }
    ?>

    <form method="POST">

        <input type="hidden" name="appoint_id" id="appoint_id">

        <div class="form-group">
            <label>Patient</label>
            <select id="patient" name="patient_id" required>
                <option value="">Select Patient</option>
                <?php while($p = $patients->fetch_assoc()): ?>
                    <option value="<?php echo $p['patient_id']; ?>">
                        <?php echo $p['patient_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Service</label>
            <select name="service_id" id="service_id" required>
                <option value="">Select patient first</option>
            </select>
        </div>

        <div class="form-group">
            <label>Payment Type</label>
            <select name="payment_type" required>
                <option value="Cash">Cash</option>
                <option value="Card">Card</option>
                <option value="GCash">GCash</option>
            </select>
        </div>

        <div class="form-group">
            <label>Amount</label>
            <input type="number" step="0.01" name="payment_amount" id="amount" readonly required>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="payment_status">
                <option value="Paid">Paid</option>
                <option value="Unpaid">Unpaid</option>
            </select>
        </div>

        <button type="submit" name="add_payment">Save Payment</button>
        <a href="payment.php" class="back-btn">← Back</a>

    </form>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const patientSelect = document.getElementById("patient");
    const serviceSelect = document.getElementById("service_id");
    const amountInput = document.getElementById("amount");
    const appointInput = document.getElementById("appoint_id");

    patientSelect.addEventListener("change", function () {

        let patient_id = this.value;

        serviceSelect.innerHTML = "<option>Loading...</option>";
        amountInput.value = "";
        appointInput.value = "";

        if (!patient_id) {
            serviceSelect.innerHTML = "<option>Select Patient first</option>";
            return;
        }

        fetch("get_payment_data.php?patient_id=" + patient_id)
            .then(res => res.json())
            .then(data => {

                serviceSelect.innerHTML = "<option value=''>Select Service</option>";

                if (!data || data.length === 0) {
                    serviceSelect.innerHTML = "<option>No appointments found</option>";
                    return;
                }

                data.forEach(item => {
                    serviceSelect.innerHTML += `
                        <option
                            value="${item.service_id}"
                            data-fee="${item.service_fee}"
                            data-appoint="${item.appoint_id}"
                        >
                            ${item.service_type} (${item.appoint_sched})
                        </option>
                    `;
                });

            })
            .catch(error => {
                console.error("Fetch error:", error);
                serviceSelect.innerHTML = "<option>Error loading data</option>";
            });
    });

    serviceSelect.addEventListener("change", function () {

        let selected = this.options[this.selectedIndex];

        if (!selected) return;

        amountInput.value = selected.getAttribute("data-fee") || "";
        appointInput.value = selected.getAttribute("data-appoint") || "";
    });

});
</script>

</body>
</html>