<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR CONCAT(first_name, ' ', last_name) LIKE '%$search%'";
}

// Get total patients count
$total_patients = $conn->query("SELECT COUNT(*) as count FROM patients $search_condition")->fetch_assoc()['count'];
$total_pages = ceil($total_patients / $per_page);

// Get patients for current page
$patients = $conn->query("SELECT patient_id, first_name, last_name, age, sex, contact_no, created_at FROM patients $search_condition ORDER BY created_at DESC LIMIT $offset, $per_page");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Patients List</title>
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
                <li><a href="patients_list.php" class="active">Patients List</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <h2>Patients List</h2>

        <div class="search-section">
            <form class= "form-group" method="GET" action="">
                <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="submit-btn" type="submit">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="patients_list.php">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Contact</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($patients->num_rows > 0): ?>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $patient['patient_id']; ?></td>
                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                <td><?php echo $patient['age']; ?></td>
                                <td><?php echo $patient['sex']; ?></td>
                                <td><?php echo htmlspecialchars($patient['contact_no']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                                <td>
                                    <a href="patient_record.php?id=<?php echo $patient['patient_id']; ?>" class="action-btn">View Record</a>
                                    <?php if (getUserRole() === 'Admin'): ?>
                                        <a href="add_report.php?patient_id=<?php echo $patient['patient_id']; ?>" class="action-btn">Add Report</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No patients found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
