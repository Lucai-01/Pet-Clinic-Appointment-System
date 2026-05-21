<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* SEARCH */
$search = $_GET['search'] ?? "";

/* FIXED PAYMENTS QUERY */
$sql = "
SELECT
    Payment.payment_id,
    Patient.patient_name,
    Payment.payment_type,
    Payment.payment_amount,
    Payment.payment_status,
    Payment.payment_date,
    Services.service_type
FROM Payment

LEFT JOIN Patient
    ON Payment.patient_id = Patient.patient_id

LEFT JOIN Services
    ON Payment.service_id = Services.service_id

WHERE Payment.payment_status LIKE ?
   OR Patient.patient_name LIKE ?
   OR Services.service_type LIKE ?

ORDER BY Payment.payment_date DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$searchTerm = "%$search%";
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payments</title>

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

.status{
    display: inline-block;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 13px;
}

.paid{
    background: #c8f7c5;
    color: #1b5e20;
}

.unpaid{
    background: #ffe0b2;
    color: #8a4b00;
}

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
    <div class="title">Payments</div>
    <div class="profile"><?php echo $_SESSION['staff_firstname']; ?></div>
</div>

<form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Search service or status..."
           value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
</form>

<a href="add_payment.php" class="add-btn">+ Add Payment</a>
<a href="payment.php" class="refresh-btn">⟳ Refresh</a>

<div class="table-container">

<table>
<tr>
    <th>ID</th>
    <th>Patient</th>
    <th>Service</th>
    <th>Type</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Date</th>
    <th>Actions</th>
</tr>

<?php if ($payments->num_rows > 0): ?>
    <?php while($row = $payments->fetch_assoc()): ?>

    <tr>
    <td><?php echo $row['payment_id']; ?></td>
    <td><?php echo $row['patient_name'] ?? 'Unknown'; ?></td>
    <td><?php echo $row['service_type'] ?? 'Walk-in purchase'; ?></td>
    <td><?php echo $row['payment_type']; ?></td>
    <td>₱<?php echo $row['payment_amount']; ?></td>

    <td>
        <span class="status <?php echo strtolower($row['payment_status']); ?>">
            <?php echo $row['payment_status']; ?>
        </span>
    </td>

    <td><?php echo $row['payment_date']; ?></td>

    <!-- ✅ ACTIONS -->
    <td>
        <a href="edit_payment.php?id=<?php echo $row['payment_id']; ?>"
           style="background:#5c7cfa;color:white;padding:6px 10px;border-radius:6px;text-decoration:none;">
            Edit
        </a>

        <a href="delete_payment.php?id=<?php echo $row['payment_id']; ?>"
           onclick="return confirm('Are you sure you want to delete this payment?')"
           style="background:#ff4d6d;color:white;padding:6px 10px;border-radius:6px;text-decoration:none;margin-left:5px;">
            Delete
        </a>
    </td>
</tr>

    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="7" class="empty">No payments found.</td>
    </tr>
<?php endif; ?>

</table>

</div>

</div>

</body>
</html>