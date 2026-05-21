<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* CHECK ID */
if (!isset($_GET['id'])) {
    die("Invalid request: No archive ID provided.");
}

$id = $_GET['id'];

$conn->begin_transaction();

try {

    /* DELETE FROM ARCHIVE TABLE */
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
    die("Permanent delete failed: " . $e->getMessage());

}
?>