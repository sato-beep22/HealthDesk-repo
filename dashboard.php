<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Get current date and patient count for today
$current_date = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as today_patients FROM patients WHERE DATE(created_at) = ?");
$stmt->bind_param("s", $current_date);
$stmt->execute();
$today_patients = $stmt->get_result()->fetch_assoc()['today_patients'];

// Get recently added patients (last 5)
$recent_patients = $conn->query("SELECT patient_id, first_name, last_name, age FROM patients ORDER BY created_at DESC LIMIT 5");

// Get recent reports (last 3)
$recent_reports = $conn->query("
    SELECT r.report_id, r.date, p.first_name, p.last_name, LEFT(r.report_content, 100) as summary
    FROM reports r
    JOIN patients p ON r.patient_id = p.patient_id
    ORDER BY r.date DESC LIMIT 3
");

// Get inventory summary
$inventory_summary = $conn->query("
    SELECT category, COUNT(*) as total_items,
           SUM(CASE WHEN status = 'In Stock' THEN 1 ELSE 0 END) as in_stock,
           ROUND((SUM(CASE WHEN status = 'In Stock' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as percentage
    FROM inventory
    GROUP BY category
");

// Get total counts
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$active_reports = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row()[0];
$total_inventory = $conn->query("SELECT COUNT(*) FROM inventory")->fetch_row()[0];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <h1>HealthDesk 
        <img  src="isu_logo.png" alt="ISU Logo" 
        style="width: auto; height: 60px; display: flex; margin-left: 15px;"
        >
        <img  src="first_aider.jpeg" alt="fa Logo" 
        style="width: auto; height: 60px; display: flex; margin-left: 15px;"
        ></h1>
        <div class="user-info">
            <span>Welcome <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </header>

    <nav class="nav">
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="add_record.php">Add Records</a></li>
            <li><a href="inventory.php">Inventory</a></li>
            <li><a href="patients_list.php">Patients List</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </nav>

    <main class="container dashboard">
        <div class="welcome-section">
            <h2>Dashboard</h2>
            <p>Today's Date: <?php echo date('F j, Y'); ?> | Patients Seen Today: <?php echo $today_patients; ?></p>
        </div>

        <div class="search-section">
            <h3>Search Patient</h3>
            <form class="search-form" method="GET" action="patient_record.php">
                <input type="text" name="student_id" placeholder="Enter Patient ID" required>
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="number"><?php echo $total_patients; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Reports</h3>
                <div class="number"><?php echo $active_reports; ?></div>
            </div>
            <div class="stat-card">
                <h3>Inventory Items</h3>
                <div class="number"><?php echo $total_inventory; ?></div>
            </div>
        </div>

        <div class="recent-patients">
            <h3>Recently Added Patients</h3>
            <?php if ($recent_patients->num_rows > 0): ?>
                <?php while ($patient = $recent_patients->fetch_assoc()): ?>
                    <div class="patient-item">
                        <div>
                            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                            <span>Age: <?php echo $patient['age']; ?></span>
                        </div>
                        <div class="patient-actions">
                            <a href="patient_record.php?id=<?php echo $patient['patient_id']; ?>">View</a>
                            <a href="add_report.php?patient_id=<?php echo $patient['patient_id']; ?>">Add Report</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No patients added yet.</p>
            <?php endif; ?>
        </div>

        <div class="recent-reports">
            <h3>Recent Health Reports</h3>
            <?php if ($recent_reports->num_rows > 0): ?>
                <?php while ($report = $recent_reports->fetch_assoc()): ?>
                    <div class="report-item">
                        <div>
                            <strong><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></strong>
                            <p><?php echo htmlspecialchars($report['summary']); ?>...</p>
                            <small><?php echo date('M j, Y', strtotime($report['date'])); ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No reports available.</p>
            <?php endif; ?>
        </div>

        <div class="inventory-summary">
            <h3>Inventory Summary</h3>
            <?php if ($inventory_summary->num_rows > 0): ?>
                <?php while ($category = $inventory_summary->fetch_assoc()): ?>
                    <div class="stat-card">
                        <h4><?php echo htmlspecialchars($category['category']); ?></h4>
                        <div class="number"><?php echo $category['percentage']; ?>%</div>
                        <p>In Stock: <?php echo $category['in_stock']; ?>/<?php echo $category['total_items']; ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No inventory data available.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
