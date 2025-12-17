<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$report_id = (int)$_GET['id'];

$conn = getDBConnection();


$stmt = $conn->prepare("SELECT report_id FROM reports WHERE report_id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header("Location: reports.php");
    exit();
}


$conn->query("DELETE FROM reports WHERE report_id = $report_id");

$conn->close();

header("Location: reports.php");
exit();
?>
