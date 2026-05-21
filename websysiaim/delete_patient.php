<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = $_GET['id'];

$conn->begin_transaction();

try{

    /* COPY TO ARCHIVE */

    $stmt = $conn->prepare("
    INSERT INTO ArchivePatient(
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

    FROM Patient
    WHERE patient_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();


    /* DELETE CHILD RECORDS FIRST */

    $stmt=$conn->prepare("
    DELETE FROM Payment
    WHERE patient_id=?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();


    $stmt=$conn->prepare("
    DELETE FROM Appointments
    WHERE patient_id=?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();


    $stmt=$conn->prepare("
    DELETE FROM Orders
    WHERE patient_id=?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();


    $stmt=$conn->prepare("
    DELETE FROM patientHistory
    WHERE patient_id=?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();


    /* DELETE PATIENT */

    $stmt=$conn->prepare("
    DELETE FROM Patient
    WHERE patient_id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();


    $conn->commit();

    header("Location: patient.php");
    exit();

}
catch(Exception $e){

    $conn->rollback();

    die("Error: ".$e->getMessage());

}
?>