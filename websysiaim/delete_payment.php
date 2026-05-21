<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require("db_connect.php");

/* CHECK IF ID EXISTS */
if (!isset($_GET['id'])) {
    die("Invalid request: No payment ID provided.");
}

$id = $_GET['id'];

/* OPTIONAL: CHECK IF PAYMENT EXISTS */
$check = $conn->prepare("
    SELECT payment_id
    FROM Payment
    WHERE payment_id = ?
");

$check->bind_param("i", $id);
$check->execute();

$result = $check->get_result();

if ($result->num_rows == 0) {
    die("Payment not found.");
}

/* DELETE PAYMENT */
$delete = $conn->prepare("
    DELETE FROM Payment
    WHERE payment_id = ?
");

if (!$delete) {
    die("Prepare failed: " . $conn->error);
}

$delete->bind_param("i", $id);

if ($delete->execute()) {
    header("Location: payment.php");
    exit();
} else {
    die("Delete failed: " . $conn->error);
}
?>