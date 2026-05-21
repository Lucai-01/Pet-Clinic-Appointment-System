<?php

include "db_connect.php";

$patient_id = $_GET['patient_id'];

/* GET APPOINTMENTS + SERVICES */
$stmt = $conn->prepare("
SELECT
    appointments.appoint_id,
    appointments.appoint_sched,
    services.service_id,
    services.service_type,
    services.service_fee

FROM appointments

JOIN services
    ON appointments.service_id = services.service_id

WHERE appointments.patient_id = ?

ORDER BY appointments.appoint_sched DESC
");

$stmt->bind_param("i", $patient_id);

$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

/* RETURN JSON ONLY */
header("Content-Type: application/json");
echo json_encode($data);

?>