<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* GET ID */
if (!isset($_GET['id'])) {
    die("Invalid request: No appointment ID provided.");
}

$id = $_GET['id'];

/* FETCH EXISTING BOOKING */
$stmt = $conn->prepare("
    SELECT * 
    FROM Appointments 
    WHERE appoint_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();

/* DROPDOWNS */
$patients = $conn->query("SELECT patient_id, patient_name FROM Patient ORDER BY patient_name ASC");
$doctors = $conn->query("
    SELECT
        doctor_id,
        doctor_firstname,
        doctor_lastname,
        doctor_suffix
    FROM Doctor
    ORDER BY doctor_lastname ASC
");


/* UPDATE PROCESS */
if (isset($_POST['update'])) {

    $sched = $_POST['appoint_sched'];
    $status = $_POST['appoint_status'];
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];

    /* 1. UPDATE APPOINTMENT */
    $update = $conn->prepare("
        UPDATE Appointments
        SET appoint_sched = ?,
            appoint_status = ?,
            patient_id = ?,
            doctor_id = ?
        WHERE appoint_id = ?
    ");

    $update->bind_param(
        "ssiii",
        $sched,
        $status,
        $patient_id,
        $doctor_id,
        $id
    );

    if ($update->execute()) {

        /* 2. INSERT INTO HISTORY ONLY IF COMPLETED */
        if ($status === "Completed") {

            /* GET service_id FROM APPOINTMENT */
            $get = $conn->prepare("
                SELECT service_id
                FROM Appointments
                WHERE appoint_id = ?
            ");

            $get->bind_param("i", $id);
            $get->execute();
            $data = $get->get_result()->fetch_assoc();

            $service_id = $data['service_id'];

            /* GET INPUTS */
            $diagnosis = $_POST['diagnosis'];
            $treatment = $_POST['treatment'];

            /* INSERT HISTORY */
            $insert = $conn->prepare("
                INSERT INTO patientHistory
                (patient_id, service_id, doctor_id, appointment_id, diagnosis, treatment, history_date)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $insert->bind_param(
                "iiiiss",
                $patient_id,
                $service_id,
                $doctor_id,
                $id,
                $diagnosis,
                $treatment
            );

            if (!$insert->execute()) {
                die("History insert failed: " . $conn->error);
            }
        }

        header("Location: bookings.php");
        exit();

    } else {
        die("Update failed: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking</title>

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

        input, select {
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

    <h2>Edit Booking</h2>

    <form method="POST">

        <label>Schedule</label>
        <input type="datetime-local" name="appoint_sched"
               value="<?php echo date('Y-m-d\TH:i', strtotime($booking['appoint_sched'])); ?>"
               required>

        <label>Status</label>
        <select name="appoint_status" required>
            <option value="Pending" <?php if($booking['appoint_status']=="Pending") echo "selected"; ?>>Pending</option>
            <option value="Checked In" <?php if($booking['appoint_status']=="Checked In") echo "selected"; ?>>Checked In</option>
            <option value="Completed" <?php if($booking['appoint_status']=="Completed") echo "selected"; ?>>Completed</option>
            <option value="Cancelled" <?php if($booking['appoint_status']=="Cancelled") echo "selected"; ?>>Cancelled</option>
        </select>

        <label>Patient</label>
        <select name="patient_id" required>
            <?php while($p = $patients->fetch_assoc()): ?>
                <option value="<?php echo $p['patient_id']; ?>"
                    <?php if($p['patient_id'] == $booking['patient_id']) echo "selected"; ?>>
                    <?php echo $p['patient_name']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Doctor</label>
        <select name="doctor_id" required>
            <?php while($d = $doctors->fetch_assoc()): ?>

                <?php
                $doctorName =
                    $d['doctor_firstname'] . " " .
                    $d['doctor_lastname'];

                if (!empty($d['doctor_suffix'])) {
                    $doctorName .= " " . $d['doctor_suffix'];
                }
                ?>

                <option value="<?php echo $d['doctor_id']; ?>"
                    <?php if($d['doctor_id'] == $booking['doctor_id']) echo "selected"; ?>>

                    Dr. <?php echo htmlspecialchars($doctorName); ?>

                </option>

            <?php endwhile; ?>
        </select>

        <label>Diagnosis</label>
        <input type="text" name="diagnosis" required>

        <label>Treatment</label>
        <input type="text" name="treatment" required>

        <button type="submit" name="update">Update Booking</button>

    </form>

    <a href="bookings.php">← Back</a>

</div>

</body>
</html>