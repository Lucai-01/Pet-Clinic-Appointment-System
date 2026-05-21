<?php include "db_connect.php"; ?>

<?php
if (isset($_POST['add'])) {

    /* PATIENT INFO */
    $patient_name = trim($_POST['patient_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $patient_type = trim($_POST['patient_type'] ?? '');
    $patient_age = intval($_POST['patient_age'] ?? 0);
    $patient_bod = $_POST['patient_bod'] ?? '';

    /* OWNER INFO */
    $owners_firstname = trim($_POST['owners_firstname'] ?? '');
    $owners_lastname = trim($_POST['owners_lastname'] ?? '');
    $owners_suffix = trim($_POST['owners_suffix'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    if (
        $patient_name === '' ||
        $gender === '' ||
        $patient_type === '' ||
        $patient_bod === ''
    ) {
        die("Invalid form submission: missing required fields.");
    }

    if ($patient_age <= 0) {
        die("Invalid age.");
    }

    /* INSERT */
    $stmt = $conn->prepare("
        INSERT INTO Patient
        (patient_name, gender, patient_type, patient_age, patient_bod,
         owners_firstname, owners_lastname, owners_suffix, contact_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    /* 🔴 FIX 2: explicitly bind variables (no change in order, just safety) */
    $stmt->bind_param(
        "sssisssss",
        $patient_name,
        $gender,
        $patient_type,
        $patient_age,
        $patient_bod,
        $owners_firstname,
        $owners_lastname,
        $owners_suffix,
        $contact_number
    );

    if ($stmt->execute()) {
        header("Location: add_patient.php?success=1");
        exit();
    } else {
        die("Execute failed: " . $stmt->error);
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Patient</title>

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

.container{
    width:480px;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(255,105,180,0.2);
}

h1{
    text-align:center;
    color:#ff4f87;
    margin-bottom:20px;
}

.section-title{
    margin-top:20px;
    margin-bottom:10px;
    color:#d63384;
    font-weight:600;
    border-left:4px solid #ff4f87;
    padding-left:10px;
}

.form-group{
    margin-bottom:12px;
}

label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
    color:#d63384;
}

input{
    width:100%;
    padding:10px;
    border:2px solid #ffd1dc;
    border-radius:10px;
    outline:none;
}

button{
    width:100%;
    padding:12px;
    background:#ff4f87;
    border:none;
    border-radius:12px;
    color:white;
    font-weight:bold;
    cursor:pointer;
    margin-top:10px;
}

button:hover{
    background:#ff2f72;
}

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

.back{
    display:block;
    text-align:center;
    margin-top:15px;
    color:#5c7cfa;
    text-decoration:none;
}

/* SELECT STYLE */
.form-group select {
    width: 100%;
    padding: 10px 30px 10px 10px;
    border: 2px solid #ffd1dc;
    border-radius: 10px;
    outline: none;
    font-family: Poppins, sans-serif;
    font-size: 14px;
    background: white;
    color: #333;
    cursor: pointer;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath fill='%23ff4f87' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 10px;
}

.form-group select:focus {
    border-color: #ff4f87;
    box-shadow: 0 0 5px rgba(255, 79, 135, 0.3);
}
</style>

</head>

<body>

<div class="container">

    <h1>Add Patient</h1>

    <?php
    if (isset($_GET['success'])) {
        echo "<div class='success'>Patient added successfully!</div>";
    }

    if (isset($error)) {
        echo "<div class='error'>$error</div>";
    }
    ?>

    <form method="POST">

        <div class="section-title">Patient Information</div>

        <div class="form-group">
            <label>Patient Name</label>
            <input type="text" name="patient_name" required>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>

        <div class="form-group">
            <label>Type</label>
            <input type="text" name="patient_type" required>
        </div>

        <div class="form-group">
            <label>Age</label>
            <input type="number" name="patient_age" required>
        </div>

        <div class="form-group">
            <label>Birth Date</label>
            <input type="date" name="patient_bod" required>
        </div>

        <div class="section-title">Owner Information</div>

        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="owners_firstname" required>
        </div>

        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="owners_lastname" required>
        </div>

        <div class="form-group">
            <label>Suffix (Optional)</label>
            <input type="text" name="owners_suffix">
        </div>

        <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact_number">
        </div>

        <button type="submit" name="add">Save Patient</button>

    </form>

    <a href="patient.php" class="back">← Back</a>

</div>

</body>
</html>