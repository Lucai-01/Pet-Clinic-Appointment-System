<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* SEARCH */
$search = $_GET['search'] ?? "";

/* QUERY */
    $sql = "
    SELECT 
        patientHistory.history_id,
        Patient.patient_name,
        Patient.patient_type,

        Patient.owners_firstname,
        Patient.owners_lastname,
        Patient.owners_suffix,

        Services.service_type,

        CONCAT(
            Doctor.doctor_firstname, ' ',
            Doctor.doctor_lastname,
            IF(
                Doctor.doctor_suffix IS NOT NULL 
                AND Doctor.doctor_suffix != '',
                CONCAT(' ', Doctor.doctor_suffix),
                ''
            )
        ) AS doctor_name,

        Appointments.appoint_sched,
        patientHistory.diagnosis,
        patientHistory.treatment,
        patientHistory.history_date

    FROM patientHistory

    LEFT JOIN Patient
        ON patientHistory.patient_id = Patient.patient_id

    LEFT JOIN Services
        ON patientHistory.service_id = Services.service_id

    LEFT JOIN Doctor
        ON patientHistory.doctor_id = Doctor.doctor_id

    LEFT JOIN Appointments
        ON patientHistory.appointment_id = Appointments.appoint_id

    WHERE
        Patient.patient_name LIKE ?
        OR Patient.patient_type LIKE ?
        OR patientHistory.diagnosis LIKE ?
        OR Services.service_type LIKE ?

    ORDER BY patientHistory.history_date DESC
";

$stmt = $conn->prepare($sql);

$searchTerm = "%$search%";
$stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient History</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body{
    background: #fff5f7;
    display: flex;
}

/* SIDEBAR */
.sidebar{
    width: 240px;
    height: 100vh;
    background: #ff4f87;
    position: fixed;
    left: 0;
    top: 0;
    padding: 30px 20px;
}

.logo{
    text-align: center;
    font-size: 40px;
    margin-bottom: 40px;
    color: white;
}

.sidebar a{
    display: block;
    text-decoration: none;
    color: white;
    padding: 14px;
    margin-bottom: 10px;
    border-radius: 10px;
    transition: 0.3s;
    font-weight: 500;
}

.sidebar a:hover{
    background: white;
    color: #ff4f87;
}

/* MAIN */
.main{
    margin-left: 240px;
    width: calc(100% - 240px);
    padding: 30px;
}

/* TOPBAR */
.topbar{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.title{
    font-size: 30px;
    font-weight: 600;
    color: #ff4f87;
}

.profile{
    background: white;
    padding: 10px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

/* SEARCH */
.search-form{
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.search-form input{
    flex: 1;
    padding: 12px;
    border: 2px solid #ffd1dc;
    border-radius: 10px;
    outline: none;
}

/* TABLE */
.table-container{
    background: white;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table{
    width: 100%;
    border-collapse: collapse;
}

th{
    background: #ff4f87;
    color: white;
    padding: 14px;
    text-align: left;
}

td{
    padding: 14px;
    border-bottom: 1px solid #eee;
}

tr:hover{
    background: #fff0f4;
}

.empty{
    text-align: center;
    padding: 20px;
    color: gray;
}

.search-form button:hover{
    background:#ff2f72;
}

</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">🐾 Clinic</div>

    <a href="dashboard.php">Dashboard</a>
    <a href="patient.php">Patients</a>
    <a href="bookings.php">Bookings</a>
    <a href="payment.php">Payments</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div class="title">Patient History</div>

        <div class="profile">
            <?php echo $_SESSION['staff_firstname']; ?>
        </div>
    </div>

<!-- SEARCH + BUTTONS -->
    <form method="GET" class="search-form">

        <input type="text" name="search" placeholder="Search patient or diagnosis..."
            value="<?php echo htmlspecialchars($search); ?>">

        <button type="submit" style="
            padding:12px 18px;
            border:none;
            background:#ff4f87;
            color:white;
            border-radius:10px;
            cursor:pointer;
        ">
            Search
        </button>

        <a href="patientHistory.php" style="
            padding:12px 18px;
            background:#5c7cfa;
            color:white;
            border-radius:10px;
            text-decoration:none;
            display:inline-block;
            margin-left:5px;
        ">
            Refresh
        </a>

    </form>

    <!-- TABLE -->
    <div class="table-container">

        <table>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Type</th>
            <th>Owner</th>
            <th>Service</th>
            <th>Doctor</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Date</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>

            <tr>
                <td><?php echo $row['history_id']; ?></td>
                <td><?php echo $row['patient_name']; ?></td>
                <td><?php echo $row['patient_type']; ?></td>
                <td>
                <?php
                echo trim(
                    $row['owners_firstname'] . " " .
                    $row['owners_lastname'] . " " .
                    $row['owners_suffix']
                );
                ?>
                </td>
                </td>
                <td><?php echo $row['service_type']; ?></td>
                <td><?php echo $row['doctor_name']; ?></td>
                <td>
                    <?php 
                        echo !empty($row['diagnosis']) 
                            ? $row['diagnosis'] 
                            : "No diagnosis recorded"; 
                    ?>
                </td>
                <td>
                    <?php 
                        echo !empty($row['treatment']) 
                            ? $row['treatment'] 
                            : "No treatment recorded"; 
                    ?>
                </td>
                <td><?php echo $row['history_date']; ?></td>
            </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="empty">No history found</td>
            </tr>
        <?php endif; ?>
        </table>

    </div>

</div>

</body>
</html>