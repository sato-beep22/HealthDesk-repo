<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id <= 0) {
    header("Location: patients_list.php");
    exit();
}

$conn = getDBConnection();

// Get patient details
$stmt = $conn->prepare("SELECT first_name, last_name FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: patients_list.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_content = trim($_POST['report_content']);

    if (!empty($report_content)) {
        $stmt = $conn->prepare("INSERT INTO reports (patient_id, report_content) VALUES (?, ?)");
        $stmt->bind_param("is", $patient_id, $report_content);

        if ($stmt->execute()) {
            $message = "Report added successfully!";
        } else {
            $message = "Error adding report: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Please enter report content.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Add Health Report</title>
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
        <h2>Add Health Report for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="report_content">Health Report Content *</label>
                    <textarea id="report_content" name="report_content" rows="15" required placeholder="Enter detailed health report including diagnosis, treatment plan, recommendations, etc."></textarea>
                </div>
                <button type="submit" class="submit-btn">Save Report</button>
                <a href="patient_record.php?id=<?php echo $patient_id; ?>" class="action-btn">Back to Patient Record</a>
            </form>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
