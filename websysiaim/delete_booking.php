<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_GET['id'])) {
    die("Invalid request: No booking ID provided.");
}

$id = $_GET['id'];

$conn->begin_transaction();

try {

    /* COPY TO ARCHIVE (FIXED COLUMN NAMES) */
    $stmt = $conn->prepare("
        INSERT INTO ArchiveAppointments(
            appoint_id,
            patient_id,
            doctor_id,
            service_id,
            appoint_sched,
            status
        )
        SELECT
            appoint_id,
            patient_id,
            doctor_id,
            service_id,
            appoint_sched,
            appoint_status
        FROM Appointments
        WHERE appoint_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();


    /* DELETE FROM MAIN TABLE */
    $stmt = $conn->prepare("
        DELETE FROM Appointments
        WHERE appoint_id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();


    $conn->commit();

    header("Location: bookings.php");
    exit();

} catch (Exception $e) {

    $conn->rollback();
    die("Archive failed: " . $e->getMessage());
}
?>