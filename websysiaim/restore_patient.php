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

$conn->begin_transaction();

try{

    /* RESTORE TO PATIENT TABLE */

    $stmt=$conn->prepare("
    INSERT INTO Patient(
        patient_id,
        patient_name,
        gender,
        patient_type,
        patient_age,
        patient_bod,
        owners_firstname,
        owners_lastname,
        owners_suffix,
        contact_number
    )

    SELECT
        patient_id,
        patient_name,
        gender,
        patient_type,
        patient_age,
        patient_bod,
        owners_firstname,
        owners_lastname,
        owners_suffix,
        contact_number

    FROM ArchivePatient
    WHERE archive_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();


    /* REMOVE FROM ARCHIVE */

    $stmt=$conn->prepare("
    DELETE FROM ArchivePatient
    WHERE archive_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();

    $conn->commit();

    header("Location: archive_patient.php");
    exit();

}
catch(Exception $e){

    $conn->rollback();

    die("Restore failed: ".$e->getMessage());

}
?>