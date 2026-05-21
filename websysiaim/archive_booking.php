<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* SEARCH */
$search = $_GET['search'] ?? "";

/* ARCHIVE QUERY */
$sql = "
    SELECT 
        aa.archive_id,
        aa.appoint_id,
        aa.appoint_sched,
        aa.status,
        aa.archived_at,

        p.patient_name,
        d.doctor_firstname,
        d.doctor_lastname,
        d.doctor_suffix

    FROM ArchiveAppointments aa
    LEFT JOIN Patient p ON aa.patient_id = p.patient_id
    LEFT JOIN Doctor d ON aa.doctor_id = d.doctor_id

    WHERE p.patient_name LIKE ?
    ORDER BY aa.archived_at DESC
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

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Archived Bookings</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #fff5f7;
    display: flex;
}

/* SIDEBAR */
.sidebar {
    width: 240px;
    height: 100vh;
    background: #ff4f87;
    position: fixed;
    left: 0;
    top: 0;
    padding: 30px 20px;
}

.logo {
    text-align: center;
    font-size: 38px;
    color: white;
    margin-bottom: 40px;
    font-weight: 600;
}

.menu a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 14px;
    margin-bottom: 10px;
    border-radius: 10px;
    font-weight: 500;
    transition: 0.25s;
}

.menu a:hover {
    background: white;
    color: #ff4f87;
}

/* MAIN */
.main {
    margin-left: 240px;
    width: calc(100% - 240px);
    padding: 30px;
}

/* TITLE */
.title {
    font-size: 28px;
    font-weight: 600;
    color: #ff4f87;
    margin-bottom: 20px;
}

/* SEARCH */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.search-form input {
    flex: 1;
    padding: 12px;
    border: 2px solid #ffd1dc;
    border-radius: 10px;
    outline: none;
}

.search-form button {
    background: #ff4f87;
    border: none;
    color: white;
    padding: 12px 18px;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.2s;
}

.search-form button:hover {
    background: #ff2f72;
}

/* TABLE */
.table-container {
    background: white;
    padding: 20px;
    border-radius: 18px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th {
    background: #ff4f87;
    color: white;
    padding: 14px;
    text-align: left;
    font-size: 14px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    color: #333;
}

tr:hover {
    background: #fff0f4;
}

/* ACTION BUTTONS */
.actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 8px 12px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-size: 12px;
    transition: 0.2s;
}

.restore {
    background: #20c997;
}

.delete {
    background: #e63946;
}

.restore:hover {
    background: #12b886;
}

.delete:hover {
    background: #c1121f;
}

/* EMPTY STATE */
.empty {
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
    </div>
</div>

<div class="main">

    <div class="title">Archived Bookings</div>

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search patient..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>

    <div class="table-container">

        <table>

            <tr>
                <th>ID</th>
                <th>Schedule</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Archived At</th>
                <th>Action</th>
            </tr>

            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>

                <tr>
                    <td><?php echo $row['appoint_id']; ?></td>
                    <td><?php echo $row['appoint_sched']; ?></td>
                    <td><?php echo $row['patient_name']; ?></td>

                    <td>
                        <?php
                            echo "Dr. " . $row['doctor_firstname'] . " " . $row['doctor_lastname'];
                            if (!empty($row['doctor_suffix'])) {
                                echo " " . $row['doctor_suffix'];
                            }
                        ?>
                    </td>

                    <td><<?php echo $row['status']; ?></td>

                    <td><?php echo $row['archived_at']; ?></td>

                    <td class="actions">

                        <a href="restore_booking.php?id=<?php echo $row['archive_id']; ?>"
                           class="btn restore"
                           onclick="return confirm('Restore this booking?')">
                           Restore
                        </a>

                        <a href="permanent_delete_booking.php?id=<?php echo $row['archive_id']; ?>"
                           class="btn delete"
                           onclick="return confirm('Permanently delete this booking?')">
                           Delete
                        </a>

                    </td>
                </tr>

                <?php endwhile; ?>
            <?php else: ?>

                <tr>
                    <td colspan="7" class="empty">No archived bookings found.</td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>

</body>
</html>