<?php
session_start();
require("db_connect.php");

if(isset($_POST['update'])){

    $id = $_POST['staff_id'];
    $firstname = $_POST['staff_firstname'];
    $lastname = $_POST['staff_lastname'];
    $suffix = $_POST['staff_suffix'];
    $email = $_POST['email'];
    $contact = $_POST['contact_number'];
    $password = $_POST['passw'];

    // Update without changing password
    if(empty($password)){

        $stmt = $conn->prepare("
            UPDATE Staff
            SET staff_firstname=?,
                staff_lastname=?,
                staff_suffix=?,
                email=?,
                contact_number=?
            WHERE staff_id=?
        ");

        $stmt->bind_param(
            "sssssi",
            $firstname,
            $lastname,
            $suffix,
            $email,
            $contact,
            $id
        );
    }

    // Update with new password
    else{

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare("
            UPDATE Staff
            SET staff_firstname=?,
                staff_lastname=?,
                staff_suffix=?,
                email=?,
                contact_number=?,
                passw=?
            WHERE staff_id=?
        ");

        $stmt->bind_param(
            "ssssssi",
            $firstname,
            $lastname,
            $suffix,
            $email,
            $contact,
            $hashedPassword,
            $id
        );
    }

    if($stmt->execute()){

        $_SESSION['staff_firstname']=$firstname;
        $_SESSION['staff_lastname']=$lastname;
        $_SESSION['staff_suffix']=$suffix;
        $_SESSION['email']=$email;
        $_SESSION['contact_number']=$contact;

        header("Location: dashboard.php");
        exit();
    }
}
?>