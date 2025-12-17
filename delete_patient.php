<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: patients_list.php");
    exit();
}

$patient_id = (int)$_GET['id'];

$conn = getDBConnection();


$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header("Location: patients_list.php");
    exit();
}


$conn->query("DELETE FROM reports WHERE patient_id = $patient_id");


$conn->query("DELETE FROM patients WHERE patient_id = $patient_id");

$conn->close();

header("Location: patients_list.php");
exit();
?>
