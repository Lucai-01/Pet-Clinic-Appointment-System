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
SELECT *
FROM ArchivePatient
WHERE patient_name LIKE ?
ORDER BY archived_at DESC
";

$stmt = $conn->prepare($sql);

$searchTerm = "%$search%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<title>Archived Patients</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:#fff5f7;
display:flex;
}

.sidebar{
width:240px;
height:100vh;
background:#ff4f87;
position:fixed;
left:0;
top:0;
padding:30px 20px;
}

.logo{
text-align:center;
font-size:38px;
margin-bottom:40px;
color:white;
font-weight:600;
}

.menu a{
display:block;
text-decoration:none;
color:white;
padding:12px 14px;
margin-bottom:10px;
border-radius:10px;
font-weight:500;
transition:.3s;
}

.menu a:hover{
background:white;
color:#ff4f87;
}

.main{
margin-left:240px;
width:calc(100% - 240px);
padding:30px;
}

.topbar{
display:flex;
justify-content:space-between;
margin-bottom:20px;
}

.title{
font-size:28px;
font-weight:600;
color:#ff4f87;
}

.profile{
background:white;
padding:10px 18px;
border-radius:12px;
box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.search-form{
display:flex;
gap:10px;
margin-bottom:20px;
}

.search-form input{
flex:1;
padding:12px;
border:2px solid #ffd1dc;
border-radius:10px;
outline:none;
}

.search-form button{
padding:12px 20px;
border:none;
background:#ff4f87;
color:white;
border-radius:10px;
cursor:pointer;
}

.search-form button:hover{
background:#ff2f72;
}

.back-btn{
display:inline-block;
padding:10px 18px;
background:#5c7cfa;
color:white;
border-radius:10px;
text-decoration:none;
margin-bottom:15px;
}

.back-btn:hover{
background:#3b5bdb;
}

.table-container{
background:white;
padding:20px;
border-radius:18px;
box-shadow:0 4px 15px rgba(0,0,0,.08);
overflow:auto;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#ff4f87;
color:white;
padding:14px;
text-align:left;
}

td{
padding:14px;
border-bottom:1px solid #eee;
}

tr:hover{
background:#fff0f4;
}

.actions{
display:flex;
gap:8px;
align-items:center;
justify-content:center;
flex-wrap:wrap;
}

.restore-btn,
.delete-btn{
display:inline-flex;
align-items:center;
justify-content:center;
text-decoration:none;
padding:8px 14px;
border-radius:8px;
color:white;
font-size:12px;
font-weight:500;
min-width:80px;
transition:0.3s;
}

.restore-btn{
background:#20c997;
}

.restore-btn:hover{
background:#12b886;
}

.delete-btn{
background:#e63946;
}

.delete-btn:hover{
background:#c1121f;
}

.empty{
text-align:center;
padding:20px;
color:gray;
}

.delete-btn{
background:#e63946;
padding:8px 12px;
color:white;
text-decoration:none;
border-radius:8px;
font-size:12px;
margin-left:5px;
}

.delete-btn:hover{
background:#c1121f;
}

</style>

</head>

<body>

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

<div class="main">

<div class="topbar">

<div class="title">
Archived Patients
</div>

<div class="profile">
<?php echo $_SESSION['staff_firstname']; ?>
</div>

</div>

<form method="GET" class="search-form">

<input
type="text"
name="search"
placeholder="Search patient..."
value="<?php echo htmlspecialchars($search); ?>"
>

<button type="submit">
Search
</button>

</form>

<a href="patient.php" class="back-btn">
← Back to Patients
</a>

<div class="table-container">

<table>

<tr>
<th>ID</th>
<th>Patient Name</th>
<th>Gender</th>
<th>Type</th>
<th>Age</th>
<th>Owner</th>
<th>Contact</th>
<th>Archived Date</th>
<th>Action</th>
</tr>

<?php

if($result->num_rows>0){

while($row=$result->fetch_assoc()){

?>

<tr>

<td><?php echo $row['patient_id']; ?></td>

<td><?php echo $row['patient_name']; ?></td>

<td><?php echo $row['gender']; ?></td>

<td><?php echo $row['patient_type']; ?></td>

<td><?php echo $row['patient_age']; ?></td>

<td>
<?php
echo $row['owners_firstname']." ".$row['owners_lastname'];
?>
</td>

<td><?php echo $row['contact_number']; ?></td>

<td><?php echo $row['archived_at']; ?></td>

<td class="actions">

<a
href="restore_patient.php?id=<?php echo $row['archive_id']; ?>"
class="restore-btn"
onclick="return confirm('Restore this patient?')"
>
Restore
</a>

<a
href="permanent_delete_patient.php?id=<?php echo $row['archive_id']; ?>"
class="delete-btn"
onclick="return confirm('Permanently delete this patient? This cannot be undone.')"
>
Delete
</a>

</td>

</tr>

<?php
}
}
else{
echo "
<tr>
<td colspan='9' class='empty'>
No archived patients found
</td>
</tr>
";
}
?>

</table>

</div>

</div>

</body>
</html>