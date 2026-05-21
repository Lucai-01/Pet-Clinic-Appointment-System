<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

if(!isset($_GET['id'])){
    die("Invalid request");
}

$id=$_GET['id'];

$stmt=$conn->prepare("
DELETE FROM ArchivePatient
WHERE archive_id=?
");

$stmt->bind_param("i",$id);

if($stmt->execute()){

    header("Location: archive_patient.php");
    exit();

}else{

    die("Delete failed");

}
?>