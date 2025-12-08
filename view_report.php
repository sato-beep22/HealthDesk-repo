<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($report_id <= 0) {
    header("Location: reports.php");
    exit();
}

$conn = getDBConnection();

// Get report details
$stmt = $conn->prepare("
    SELECT r.*, p.first_name, p.last_name, p.patient_id
    FROM reports r
    JOIN patients p ON r.patient_id = p.patient_id
    WHERE r.report_id = ?
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header("Location: reports.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - View Report</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <h1>HealthDesk <img 
        src="isu_logo.png" 
        alt="ISU Logo" 
        style="width: auto; height: 60px; display: flex; margin-left: 15px;"
        >
        <img  src="first_aider.jpeg" alt="fa Logo" 
        style="width: auto; height: 60px; display: flex; margin-left: 15px;"
        >
        </h1>
        <div class="user-info">
            <span>Welcome <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </header>

    <nav class="nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="add_record.php">Add Records</a></li>
            <li><a href="inventory.php">Inventory</a></li>
            <li><a href="patients_list.php">Patients List</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </nav>

    <main class="container">
        <h2>Health Report</h2>

        <div class="report-details">
            <div class="report-header">
                <h3>Patient: <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?> (ID: <?php echo $report['patient_id']; ?>)</h3>
                <p><strong>Report Date:</strong> <?php echo date('F j, Y', strtotime($report['date'])); ?></p>
            </div>

            <div class="report-content">
                <h4>Report Content:</h4>
                <div class="report-text">
                    <?php echo nl2br(htmlspecialchars($report['report_content'])); ?>
                </div>
            </div>

            <div class="report-actions">
                <a href="patient_record.php?id=<?php echo $report['patient_id']; ?>" class="action-btn">View Patient Record</a>
                <a href="add_report.php?patient_id=<?php echo $report['patient_id']; ?>" class="action-btn">Add Another Report</a>
                <a href="reports.php" class="action-btn">Back to Reports</a>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
