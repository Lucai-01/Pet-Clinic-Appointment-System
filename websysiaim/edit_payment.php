<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* GET PAYMENT ID */
if (!isset($_GET['id'])) {
    die("Invalid request: No payment ID provided.");
}

$id = $_GET['id'];

/* FETCH CURRENT PAYMENT DATA */
$stmt = $conn->prepare("
    SELECT
        Payment.*,
        Patient.patient_name,
        Services.service_type
    FROM Payment
    LEFT JOIN Patient
        ON Payment.patient_id = Patient.patient_id
    LEFT JOIN Services
        ON Payment.service_id = Services.service_id
    WHERE Payment.payment_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Payment not found.");
}

$payment = $result->fetch_assoc();


/* UPDATE PAYMENT */
if (isset($_POST['update'])) {

    $payment_type = $_POST['payment_type'];
    $payment_amount = $_POST['payment_amount'];
    $payment_status = $_POST['payment_status'];

    $update = $conn->prepare("
        UPDATE Payment
        SET payment_type=?,
            payment_amount=?,
            payment_status=?
        WHERE payment_id=?
    ");

    $update->bind_param(
        "sdsi",
        $payment_type,
        $payment_amount,
        $payment_status,
        $id
    );

    if ($update->execute()) {
        header("Location: payment.php");
        exit();
    } else {
        die("Update failed: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Payment</title>

<style>

body{
    font-family:Arial;
    background:#fff5f7;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.form-box{
    width:420px;
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    color:#ff4f87;
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:6px;
    font-weight:bold;
}

input,select{
    width:100%;
    padding:10px;
    margin-bottom:15px;
    border:1px solid #ffd1dc;
    border-radius:8px;
}

.readonly{
    background:#f5f5f5;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#ff4f87;
    color:white;
    cursor:pointer;
}

button:hover{
    background:#ff2f72;
}

a{
    display:block;
    text-align:center;
    margin-top:10px;
    text-decoration:none;
    color:#5c7cfa;
}

</style>

</head>

<body>

<div class="form-box">

<h2>Edit Payment</h2>

<form method="POST">

    <label>Patient</label>
    <input
        type="text"
        value="<?php echo $payment['patient_name']; ?>"
        class="readonly"
        readonly
    >

    <label>Service</label>
    <input
        type="text"
        value="<?php echo $payment['service_type']; ?>"
        class="readonly"
        readonly
    >

    <label>Payment Type</label>
    <select name="payment_type">

        <option value="Cash"
        <?php if($payment['payment_type']=="Cash") echo "selected"; ?>>
        Cash
        </option>

        <option value="Card"
        <?php if($payment['payment_type']=="Card") echo "selected"; ?>>
        Card
        </option>

        <option value="GCash"
        <?php if($payment['payment_type']=="GCash") echo "selected"; ?>>
        GCash
        </option>

    </select>


    <label>Amount</label>
    <input
        type="number"
        step="0.01"
        name="payment_amount"
        value="<?php echo $payment['payment_amount']; ?>"
        class="readonly"
        readonly
    >

    <label>Status</label>
    <select name="payment_status">

        <option value="Paid"
        <?php if($payment['payment_status']=="Paid") echo "selected"; ?>>
        Paid
        </option>

        <option value="Unpaid"
        <?php if($payment['payment_status']=="Unpaid") echo "selected"; ?>>
        Unpaid
        </option>

    </select>

    <button type="submit" name="update">
        Update Payment
    </button>

</form>

<a href="payment.php">← Back</a>

</div>

</body>
</html>