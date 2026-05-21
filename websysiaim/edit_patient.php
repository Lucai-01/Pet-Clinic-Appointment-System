<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* GET ID FROM URL (SOURCE OF TRUTH) */
if (!isset($_GET['id'])) {
    die("Invalid request: No patient ID provided.");
}

$id = $_GET['id'];

/* FETCH EXISTING DATA */
$stmt = $conn->prepare("SELECT * FROM Patient WHERE patient_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Patient not found.");
}

$patient = $result->fetch_assoc();

/* UPDATE PROCESS */
if (isset($_POST['update'])) {

    $name = $_POST['patient_name'];
    $gender = $_POST['gender'];
    $type = $_POST['patient_type'];
    $age = $_POST['patient_age'];
    $bod = $_POST['patient_bod'];

    $owners_firstname = $_POST['owners_firstname'];
    $owners_lastname = $_POST['owners_lastname'];
    $owners_suffix = $_POST['owners_suffix'];
    $contact_number = $_POST['contact_number'];

    /* IMPORTANT: USE GET ID (NOT POST) */
    $id = $_GET['id'];

    $update = $conn->prepare("
        UPDATE Patient 
        SET patient_name = ?, 
            gender = ?,
            patient_type = ?, 
            patient_age = ?, 
            patient_bod = ?, 
            owners_firstname = ?,
            owners_lastname = ?,
            owners_suffix = ?,
            contact_number = ?
        WHERE patient_id = ?
    ");

    if (!$update) {
        die("Prepare failed: " . $conn->error);
    }

    $update->bind_param(
        "sssisssssi",
        $name,
        $gender,
        $type,
        $age,
        $bod,
        $owners_firstname,
        $owners_lastname,
        $owners_suffix,
        $contact_number,
        $id
    );

    if ($update->execute()) {
        header("Location: patient.php?updated=1");
        exit();
    } else {
        die("Update failed: " . $update->error);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Patient</title>

    <style>
        body {
            font-family: Arial;
            background: #fff5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h2 {
            color: #ff4f87;
            text-align: center;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 15px;
            border: 1px solid #ffd1dc;
            border-radius: 8px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #ff4f87;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        button:hover {
            background: #ff2f72;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #5c7cfa;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="form-box">

    <h2>Edit Patient</h2>

    <form method="POST">

        <label>Name</label>
        <input type="text" name="patient_name"
            value="<?php echo htmlspecialchars($patient['patient_name']); ?>" required>

        <label>Patient Gender</label>
        <input type="text" name="gender"
            value="<?php echo htmlspecialchars($patient['gender']); ?>" required>

        <label>Type</label>
        <input type="text" name="patient_type"
            value="<?php echo htmlspecialchars($patient['patient_type']); ?>" required>

        <label>Age</label>
        <input type="number" name="patient_age"
            value="<?php echo htmlspecialchars($patient['patient_age']); ?>" required>

        <label>Birth Date</label>
        <input type="text" name="patient_bod"
            value="<?php echo htmlspecialchars($patient['patient_bod']); ?>">

        <label>Owner First Name</label>
        <input type="text" name="owners_firstname"
            value="<?php echo htmlspecialchars($patient['owners_firstname']); ?>">

        <label>Owner Last Name</label>
        <input type="text" name="owners_lastname"
            value="<?php echo htmlspecialchars($patient['owners_lastname']); ?>">

        <label>Owner Suffix</label>
        <input type="text" name="owners_suffix"
            value="<?php echo htmlspecialchars($patient['owners_suffix']); ?>">

        <label>Contact Number</label>
        <input type="text" name="contact_number"
            value="<?php echo htmlspecialchars($patient['contact_number']); ?>">

        <button type="submit" name="update">Update Patient</button>

    </form>

    <a href="patient.php">← Back</a>

</div>

</body>
</html>