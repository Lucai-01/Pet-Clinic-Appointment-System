<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_GET['id'])) {
    die("Invalid request: No archive ID provided.");
}

$id = $_GET['id'];

$conn->begin_transaction();

try {

    /* 1. RESTORE TO MAIN TABLE (FIXED COLUMN MAPPING) */
    $stmt = $conn->prepare("
        INSERT INTO Appointments (
            appoint_id,
            patient_id,
            doctor_id,
            appoint_sched,
            appoint_status
        )
        SELECT 
            appoint_id,
            patient_id,
            doctor_id,
            appoint_sched,
            status
        FROM ArchiveAppointments
        WHERE archive_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();


    /* 2. DELETE FROM ARCHIVE */
    $stmt = $conn->prepare("
        DELETE FROM ArchiveAppointments
        WHERE archive_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();


    $conn->commit();

    header("Location: archive_booking.php");
    exit();

} catch (Exception $e) {

    $conn->rollback();
    die("Restore failed: " . $e->getMessage());
}
?>