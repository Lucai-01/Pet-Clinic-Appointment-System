<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* SEARCH */
$search = $_GET['search'] ?? "";

/* PATIENT QUERY (SAFE + CLEAN) */
$sql = "
    SELECT *
    FROM Patient
    WHERE patient_name LIKE ?
    ORDER BY patient_name ASC
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

    <title>Patients</title>

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
        margin-bottom: 40px;
        color: white;
        font-weight: 600;
    }

    .menu a {
        display: block;
        text-decoration: none;
        color: white;
        padding: 12px 14px;
        margin-bottom: 10px;
        border-radius: 10px;
        font-weight: 500;
        transition: 0.3s;
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

    /* TOPBAR */
    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .title {
        font-size: 28px;
        font-weight: 600;
        color: #ff4f87;
    }

    .profile {
        background: white;
        padding: 10px 18px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        font-weight: 500;
    }

    /* SEARCH */
    .search-form {
        display: flex;
        gap: 10px;
        margin-bottom: 18px;
    }

    .search-form input {
        flex: 1;
        padding: 12px;
        border: 2px solid #ffd1dc;
        border-radius: 10px;
        outline: none;
    }

    .search-form button {
        border: none;
        background: #ff4f87;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        transition: 0.3s;
    }

    .search-form button:hover {
        background: #ff2f72;
    }

    /* BUTTONS ROW */
    .add-btn,
    .refresh-btn {
        display: inline-block;
        padding: 10px 18px;
        border-radius: 10px;
        font-weight: 500;
        text-decoration: none;
        margin-bottom: 15px;
        margin-right: 8px;
    }

    .add-btn {
        background: #ff4f87;
        color: white;
    }

    .add-btn:hover {
        background: #ff2f72;
    }

    .refresh-btn {
        background: #5c7cfa;
        color: white;
    }

    .refresh-btn:hover {
        background: #3b5bdb;
    }

    /* TABLE */
    .table-container {
        background: white;
        padding: 20px;
        border-radius: 18px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);

        overflow-x: auto;
        max-height: 70vh; /* makes table scroll vertically */
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1100px;
        font-size: 13px;
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
        gap: 6px;
        align-items: center;
    }

    .actions a {
        text-decoration: none;
        padding: 6px 10px;
        border-radius: 8px;
        color: white;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    .edit-btn {
        background: #ffb347;
    }

    .delete-btn {
        background: #ff4d6d;
    }

    .view-btn {
        background: #5c7cfa;
    }

    .edit-btn:hover { background: #ff9f1c; }
    .delete-btn:hover { background: #e63946; }
    .view-btn:hover { background: #3b5bdb; }

    /* EMPTY STATE */
    .empty {
        text-align: center;
        padding: 20px;
        color: gray;
    }

    </style>

</head>

<body>

    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="logo">
            🐾 Clinic
        </div>

        <div class="menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="patient.php">Patients</a>
            <a href="bookings.php">Bookings</a>
            <a href="payment.php">Payments</a>
            <a href="products.php">Products</a>
        </div>

    </div>

    <!-- MAIN -->

    <div class="main">

        <div class="topbar">

            <div class="title">
                Patient Records
            </div>

            <div class="profile">
                <?php echo $_SESSION['staff_firstname']; ?>
            </div>
            

        </div>

        <!-- SEARCH -->

        <form method="GET" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search patient name..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit">
                Search
            </button>

        </form>

        <!-- BUTTONS -->
        <a href="add_patient.php" class="add-btn">
            + Add New Patient
        </a>

        <a href="patientHistory.php" class="add-btn"">
            Patient History
        </a>

        <a href="archive_patient.php" class="add-btn">
            Archive Patients
        </a>

        <a href="patient.php" class="refresh-btn">
            ⟳ Refresh
        </a>

        <!-- TABLE -->

        <div class="table-container">

            <table>

    <tr>
        <th>ID</th>
        <th>Patient Name</th>
        <th>Gender</th>
        <th>Type</th>
        <th>Age</th>
        <th>Birth Date</th>
        <th>Owner's Firstname</th>
        <th>Owner's Lastname</th>
        <th>Owner's Suffix</th>
        <th>Contact Number</th>
        <th>Actions</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {

        while($row = $result->fetch_assoc()) {
    ?>

        <tr>

            <td>
                <?php echo $row['patient_id']; ?>
            </td>

            <td>
                <?php echo $row['patient_name']; ?>
            </td>

            <td>
                <?php echo $row['gender']; ?>
            </td>

            <td>
                <?php echo $row['patient_type']; ?>
            </td>

            <td>
                <?php echo $row['patient_age']; ?>
            </td>

            <td>
                <?php echo $row['patient_bod']; ?>
            </td>

            <td>
                <?php echo $row['owners_firstname']; ?>
            </td>

            <td>
                <?php echo $row['owners_lastname']; ?>
            </td>

            <td>
                <?php echo $row['owners_suffix']; ?>
            </td>

            <td>
                <?php echo $row['contact_number']; ?>
            </td>

            <td class="actions">

                <a 
                    href="edit_patient.php?id=<?php echo $row['patient_id']; ?>" 
                    class="edit-btn"
                >
                    Edit
                </a>

                <a 
                    href="delete_patient.php?id=<?php echo $row['patient_id']; ?>" 
                    class="delete-btn"
                    onclick="return confirm('Move this patient to archive?')"
                >
                    Delete
                </a>

            </td>

        </tr>

    <?php
        }

    } else {

        echo "
        <tr>
            <td colspan='8' class='empty'>
                No patients found.
            </td>
        </tr>
        ";

    }
    ?>

</table>

        </div>

    </div>

    <script>
function confirmDelete() {
    return confirm("Are you sure you want to delete this patient?");
}
</script>

</body>
</html>