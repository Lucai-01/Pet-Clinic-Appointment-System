<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* SEARCH */
$search = $_GET['search'] ?? "";

/* APPOINTMENTS (SAFE SEARCH) */
$sql = "
    SELECT 
        appoint_id,
        appoint_sched,
        appoint_status,
        Patient.patient_name,
        Doctor.doctor_firstname,
        Doctor.doctor_lastname,
        Doctor.doctor_suffix
    FROM Appointments
    LEFT JOIN Patient ON Appointments.patient_id = Patient.patient_id
    LEFT JOIN Doctor ON Appointments.doctor_id = Doctor.doctor_id
    WHERE Patient.patient_name LIKE ?
    ORDER BY appoint_sched ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$searchTerm = "%$search%";
$stmt->bind_param("s", $searchTerm);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bookings</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ===== YOUR CSS (UNCHANGED) ===== */

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

.menu a,
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

.menu a:hover,
.sidebar a:hover{
    background: white;
    color: #ff4f87;
}

.main{
    margin-left: 240px;
    width: calc(100% - 240px);
    padding: 30px;
}

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

.search-form button{
    border: none;
    background: #ff4f87;
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    cursor: pointer;
}

.search-form button:hover{
    background: #ff2f72;
}

.add-btn,
.refresh-btn{
    display: inline-block;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 500;
    text-decoration: none;
    color: white;
    margin-bottom: 20px;
}

.add-btn{ background: #ff4f87; }
.refresh-btn{ background: #5c7cfa; margin-left: 10px; }

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

.btn{
    display: inline-block;
    padding: 8px 12px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-size: 13px;
    margin-right: 5px;
}


.edit{ background: #5c7cfa; }
.delete{ background: #ff4d6d; }

.status{
    display: inline-block;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 13px;
}

.checked{ background: #c8f7c5; color: #1b5e20; }
.pending{ background: #ffe0b2; color: #8a4b00; }
.completed{ background: #c8f7c5; color: #1b5e20; }
.cancelled{ background: #ffcdd2; color: #b71c1c; }

.empty{
    text-align: center;
    padding: 20px;
    color: gray;
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
        <div class="title">Bookings</div>
        <div class="profile"><?php echo $_SESSION['staff_firstname']; ?></div>
    </div>

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search patient..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>

    <a href="book_appointment.php" class="add-btn">+ Add Booking</a>
    <a href="archive_booking.php" class="add-btn">Archive Booking</a>
    <a href="bookings.php" class="refresh-btn">⟳ Refresh</a>

    <div class="table-container">

        <table>
            <tr>
                <th>ID</th>
                <th>Schedule</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php if ($appointments->num_rows > 0): ?>
                <?php while($row = $appointments->fetch_assoc()): ?>

                <tr>
                    <td><?php echo $row['appoint_id']; ?></td>
                    <td><?php echo $row['appoint_sched']; ?></td>
                    <td><?php echo $row['patient_name'] ?? 'Unknown'; ?></td>

                    <td>
                        <?php
                            $doctor_name = "Dr. " . $row['doctor_firstname'] . " " . $row['doctor_lastname'];

                            if (!empty($row['doctor_suffix'])) {
                                $doctor_name .= " " . $row['doctor_suffix'];
                            }

                            echo $doctor_name;
                        ?>
                    </td>

                    <td>
                        <span class="status
                        <?php
                            if ($row['appoint_status'] == 'Checked In') echo 'checked';
                            elseif ($row['appoint_status'] == 'Pending') echo 'pending';
                            elseif ($row['appoint_status'] == 'Completed') echo 'completed';
                            else echo 'cancelled';
                        ?>">
                            <?php echo $row['appoint_status']; ?>
                        </span>
                    </td>

                    <td>
                        <a href="edit_booking.php?id=<?php echo $row['appoint_id']; ?>" class="btn edit">Edit</a>
                        <a 
                            href="delete_booking.php?id=<?php echo $row['appoint_id']; ?>" 
                            class="delete-btn"
                            onclick="return confirm('Move this appointment to archive?')"
                        >
                            Delete
                        </a>
                    </td>
                </tr>

                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty">No bookings found.</td>
                </tr>
            <?php endif; ?>

        </table>

    </div>

</div>

</body>
</html>