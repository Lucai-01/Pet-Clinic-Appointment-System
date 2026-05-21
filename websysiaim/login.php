<?php
session_start();

require("db_connect.php");

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare(
    "SELECT * FROM Staff
     WHERE email = ?"
);

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $staff = $result->fetch_assoc();

    /* CHECK HASHED PASSWORD */
    if (password_verify($password, $staff['passw'])) {

        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['staff_firstname'] = $staff['staff_firstname'];
        $_SESSION['staff_lastname'] = $staff['staff_lastname'];
        $_SESSION['staff_suffix'] = $staff['staff_suffix'];
        $_SESSION['role'] = $staff['role'];

        header("Location: dashboard.php");
        exit();

    } else {
        $error = "Invalid email or password!";
    }

} else {
    $error = "Invalid email or password!";
}

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $staff = $result->fetch_assoc();

        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['staff_firstname'] = $staff['staff_firstname'];
        $_SESSION['staff_lastname'] = $staff['staff_lastname'];
        $_SESSION['staff_suffix'] = $staff['staff_suffix'];
        $_SESSION['role'] = $staff['role'];

        header("Location: dashboard.php");
        exit();

    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <title>Pet Shop Login</title>

    <style>

        /* REPLACE YOUR CURRENT CSS WITH THIS */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: Arial, sans-serif;
}

body{
    height:100vh;
    overflow:hidden;
    position:relative;
}

/* BACKGROUND IMAGE */

.background{
    position:absolute;
    width:100%;
    height:100%;
    background:url("dog.jpg");
    background-size:cover;
    background-position:center;
    transform:scale(1.05);
}

/* DARK OVERLAY */

.overlay{
    position:absolute;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.25);
}

/* CENTER LOGIN */

.login-container{
    position:relative;
    z-index:2;

    width:100%;
    height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;
    flex-direction:column;
}

/* TITLE */

.title{
    color:whitesmoke;
    font-size:70px;
    margin-bottom:20px;
    text-shadow:0 4px 10px rgba(0,0,0,0.4);
    font-family: 'Great Vibes', cursive;
}

/* LOGIN BOX */

.login-box{
    width:420px;

    background:rgba(255,255,255,0.18);

    backdrop-filter:blur(12px);
    -webkit-backdrop-filter:blur(12px);

    border:2px solid rgba(255,255,255,0.3);

    border-radius:25px;

    padding:45px;

    box-shadow:0 8px 25px rgba(0,0,0,0.25);
}

/* LABELS */

label{
    color:white;
    font-size:18px;
    font-weight:bold;
    display:block;
    margin-bottom:8px;
}

/* INPUTS */

input[type="text"],
input[type="password"]{
    width:100%;
    padding:14px;

    border:none;
    border-radius:14px;

    outline:none;

    font-size:16px;

    background:rgba(255,255,255,0.9);
}

/* INPUT FOCUS */

input:focus{
    box-shadow:0 0 10px rgba(255,255,255,0.6);
}

/* FORM GROUP */

.form-group{
    margin-bottom:25px;
}

/* PASSWORD ROW */

.password-row{
    display:flex;
    align-items:center;
    gap:10px;
}

/* SHOW PASSWORD */

.show-pass{
    display:flex;
    align-items:center;
    gap:5px;

    color:white;
    font-size:14px;

    white-space:nowrap;
}

.show-pass input{
    width:auto;
}

/* BUTTON */

button{
    width:100%;
    padding:15px;

    border:none;
    border-radius:14px;

    background:#ff4f81;
    color:white;

    font-size:24px;
    font-weight:bold;

    cursor:pointer;

    transition:0.3s;
}

button:hover{
    background:#ff2f6d;
    transform:scale(1.02);
}

/* ERROR */

.error{
    color:#ffe5ec;
    text-align:center;
    margin-top:15px;
    font-weight:bold;
}

/* MOBILE */

@media(max-width:500px){

    .login-box{
        width:90%;
        padding:30px;
    }

    .title{
        font-size:45px;
    }

}

.register-btn{
    display:block;
    text-align:center;
    margin-top:15px;
    padding:15px;
    border-radius:14px;
    background:#5c7cfa;
    color:white;
    font-size:18px;
    font-weight:bold;
    text-decoration:none;
    transition:0.3s;
}

.register-btn:hover{
    background:#3b5bdb;
    transform:scale(1.02);
}

    </style>

</head>

<body>

    <!-- BACKGROUND IMAGE -->
    <div class="background"></div>

    <!-- DARK OVERLAY -->
    <div class="overlay"></div>

    <!-- LOGIN CONTAINER -->
    <div class="login-container">

        <h1 class="title">Pet Clinic Login</h1>

        <div class="login-box">

            <form method="POST">

                <!-- EMAIL -->

                <div class="form-group">
                    <label>Email</label>

                    <input 
                        type="text" 
                        name="email" 
                        required
                    >
                </div>

                <!-- PASSWORD -->

                <div class="form-group">

                    <label>Password</label>

                    <div class="password-row">

                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            required
                        >

                        <div class="show-pass">
                            <input type="checkbox" onclick="showPass()">
                            Show
                        </div>

                    </div>

                </div>

                <!-- BUTTON -->
                <button type="submit" name="login">
                    LOGIN
                </button>

                <!-- REGISTER BUTTON -->
                <a href="register.php" class="register-btn">
                    REGISTER
                </a>

            </form>

            <?php
            if(isset($error)){
                echo "<p class='error'>$error</p>";
            }
            ?>

        </div>

    </div>

<script>

function showPass(){

    let password = document.getElementById("password");

    if(password.type === "password"){
        password.type = "text";
    }
    else{
        password.type = "password";
    }

}

</script>

</body>
</html>