<?php
session_start();
require("db_connect.php");

if (isset($_POST['register'])) {

    $firstname = $_POST['staff_firstname'];
    $lastname = $_POST['staff_lastname'];
    $suffix = $_POST['staff_suffix'] ?? "";

    $contact = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = $_POST['password'];

/* ENCRYPT PASSWORD */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = "Receptionist"; // default role

    // CHECK IF EMAIL EXISTS
    $check = $conn->prepare("SELECT staff_id FROM Staff WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Email already exists!";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO Staff (staff_firstname, staff_lastname, staff_suffix, contact_number, email, passw, role)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
        "sssssss",
        $firstname,
        $lastname,
        $suffix,
        $contact,
        $email,
        $hashed_password,
        $role
    );

        if ($stmt->execute()) {
            include 'telegram_notify.php';
            require_once 'functions.php';

            /*Para Dika Mag libog boi*/
//==============================================================
            $message = "
            🐾New Staff Registered

             Name: $firstname $lastname
             Role: $role
             Email: $email
             Contact: $contact
             ";
             sendStaffNotification($message);
//============================================================
            /* GOOGLE SHEETS LOGGING */
            sendToGoogleSheet(
            "Staff Registration",
            "$firstname $lastname",
            "Role: $role | Email: $email",
            "Success"
            );
//============================================================            
            header("Location: login.php?registered=1");
            exit();
        } else {
            $error = "Registration failed!";
        }

        $stmt->close();
    }

    $check->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

<style>
body{
    margin:0;
    font-family:Poppins, sans-serif;
    background:#fff5f7;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box{
    width:420px;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(255,79,135,0.2);
}

h2{
    text-align:center;
    color:#ff4f87;
    margin-bottom:20px;
}

label{
    font-weight:600;
    color:#d63384;
}

input{
    width:100%;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
    border:2px solid #ffd1dc;
    border-radius:10px;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:12px;
    background:#ff4f87;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

button:hover{
    background:#ff2f72;
}

.error{
    color:red;
    text-align:center;
    margin-bottom:10px;
}

.back{
    display:block;
    text-align:center;
    margin-top:10px;
    color:#5c7cfa;
    text-decoration:none;
}
</style>

</head>

<body>

<div class="box">

    <h2>Register Staff</h2>

    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">

        <label>First Name</label>
        <input type="text" name="staff_firstname" required>

        <label>Last Name</label>
        <input type="text" name="staff_lastname" required>

        <label>Suffix(If applicable)</label>
        <input type="text" name="staff_suffix">

        <label>Contact Number</label>
        <input type="text" name="contact_number" required>

        <label>Email</label>
        <input type="text" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" name="register">REGISTER</button>

    </form>

    <a href="login.php" class="back">← Back to Login</a>

</div>

</body>
</html>