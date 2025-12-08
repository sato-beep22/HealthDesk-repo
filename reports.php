<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = '';

$conn = getDBConnection();

// Get patients for dropdown
$patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name, first_name");

// Get recent reports
$recent_reports = $conn->query("
    SELECT r.report_id, r.date, p.first_name, p.last_name, LEFT(r.report_content, 100) as summary
    FROM reports r
    JOIN patients p ON r.patient_id = p.patient_id
    ORDER BY r.date DESC LIMIT 10
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Reports</title>
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
            <li><a href="reports.php" class="active">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </nav>

    <main class="container">
        <h2>Reports</h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Generate Patient Report</h3>
            <form method="POST" action="generate_report.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="patient_id">Select Patient *</label>
                        <select id="patient_id" name="patient_id" required>
                            <option value="">Choose a patient</option>
                            <?php while ($patient = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $patient['patient_id']; ?>">
                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> (ID: <?php echo $patient['patient_id']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report_type">Report Type *</label>
                        <select id="report_type" name="report_type" required>
                            <option value="">Select report type</option>
                            <option value="full_record">Full Patient Record</option>
                            <option value="health_summary">Health Summary</option>
                            <option value="medical_history">Medical History</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Generate Report</button>
            </form>
        </div>

        <div class="table-container">
            <h3>Recent Health Reports</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Report Preview</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_reports->num_rows > 0): ?>
                        <?php while ($report = $recent_reports->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($report['date'])); ?></td>
                                <td><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['summary']); ?>...</td>
                                <td>
                                    <a href="view_report.php?id=<?php echo $report['report_id']; ?>" class="action-btn">View Full Report</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No reports available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
