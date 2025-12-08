<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0);

if ($patient_id <= 0) {
    header("Location: patients_list.php");
    exit();
}

$conn = getDBConnection();

// Get patient details
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: patients_list.php");
    exit();
}

// Get patient's reports
$reports = $conn->query("SELECT * FROM reports WHERE patient_id = $patient_id ORDER BY date DESC");

// Get dispensation history
$dispensation_history = $conn->query("
    SELECT dl.quantity_disbursed, dl.date, i.item_name, i.category
    FROM dispensation_log dl
    JOIN inventory i ON dl.item_id = i.item_id
    WHERE dl.patient_id = $patient_id
    ORDER BY dl.date DESC
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Patient Record</title>
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
            <?php if (getUserRole() === 'Admin'): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a href="add_record.php">Add Records</a></li>
            <?php if (getUserRole() === 'Admin'): ?>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="patients_list.php">Patients List</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <h2>Patient Record - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>

        <div class="patient-details">
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-grid">
                    <div class="detail-item">
                        <strong>Patient ID:</strong> <?php echo $patient['patient_id']; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['middle_name'] . ' ' . $patient['last_name']); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Age:</strong> <?php echo $patient['age']; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Sex:</strong> <?php echo $patient['sex']; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Date of Birth:</strong> <?php echo $patient['date_of_birth'] ? date('M j, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Place of Birth:</strong> <?php echo htmlspecialchars($patient['place_of_birth'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Home Address:</strong> <?php echo htmlspecialchars($patient['home_address'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Citizenship:</strong> <?php echo htmlspecialchars($patient['citizenship'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Civil Status:</strong> <?php echo $patient['civil_status'] ?: 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Parent/Guardian:</strong> <?php echo htmlspecialchars($patient['parent_guardian'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Contact No:</strong> <?php echo htmlspecialchars($patient['contact_no'] ?: 'N/A'); ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Physical Examination</h3>
                <div class="form-grid">
                    <div class="detail-item">
                        <strong>Height:</strong> <?php echo $patient['height'] ? $patient['height'] . ' cm' : 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Weight:</strong> <?php echo $patient['weight'] ? $patient['weight'] . ' kg' : 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>BMI:</strong> <?php echo $patient['bmi'] ?: 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Blood Pressure:</strong> <?php echo htmlspecialchars($patient['bp'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Temperature:</strong> <?php echo $patient['temp'] ? $patient['temp'] . ' °C' : 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Pulse Rate:</strong> <?php echo $patient['pr'] ?: 'N/A'; ?>
                    </div>
                    <div class="detail-item">
                        <strong>Respiratory Rate:</strong> <?php echo $patient['rr'] ?: 'N/A'; ?>
                    </div>
                </div>
                <div class="exam-details">
                    <div class="detail-item">
                        <strong>HEENT:</strong> <?php echo htmlspecialchars($patient['heent'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Chest:</strong> <?php echo htmlspecialchars($patient['chest'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Heart:</strong> <?php echo htmlspecialchars($patient['heart'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Lungs:</strong> <?php echo htmlspecialchars($patient['lungs'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Abdomen:</strong> <?php echo htmlspecialchars($patient['abdomen'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Genital:</strong> <?php echo htmlspecialchars($patient['genital'] ?: 'N/A'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Skin:</strong> <?php echo htmlspecialchars($patient['skin'] ?: 'N/A'); ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Past Medical History</h3>
                <div class="form-grid">
                    <div class="detail-item">
                        <strong>Chronic Illness:</strong> <?php echo htmlspecialchars($patient['chronic_illness'] ?: 'None'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Allergies:</strong> <?php echo htmlspecialchars($patient['allergies'] ?: 'None'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Operations:</strong> <?php echo htmlspecialchars($patient['operations'] ?: 'None'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Accidents/Injuries:</strong> <?php echo htmlspecialchars($patient['accidents_injuries'] ?: 'None'); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Regular Medicines:</strong> <?php echo htmlspecialchars($patient['medicines_regular'] ?: 'None'); ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Health Reports</h3>
                <?php if (getUserRole() === 'Admin'): ?>
                    <a href="add_report.php?patient_id=<?php echo $patient['patient_id']; ?>" class="action-btn">Add New Report</a>
                <?php endif; ?>
                <?php if ($reports->num_rows > 0): ?>
                    <?php while ($report = $reports->fetch_assoc()): ?>
                        <div class="report-item">
                            <h4>Report Date: <?php echo date('M j, Y', strtotime($report['date'])); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($report['report_content'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No reports available for this patient.</p>
                <?php endif; ?>
            </div>

            <div class="form-section">
                <h3>Dispensation History</h3>
                <?php if ($dispensation_history->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($dispensation = $dispensation_history->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($dispensation['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($dispensation['item_name']); ?></td>
                                        <td><?php echo $dispensation['category']; ?></td>
                                        <td><?php echo $dispensation['quantity_disbursed']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No dispensation history for this patient.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
