<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';

$conn = getDBConnection();

// Handle administer item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['administer_item'])) {
    $patient_id = (int)$_POST['patient_id'];
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) {
        $message = "Quantity must be greater than 0.";
    } else {
        // Check if item exists and has sufficient quantity
        $stmt = $conn->prepare("SELECT item_name, quantity FROM inventory WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            if ($item['quantity'] >= $quantity) {
                // Update inventory quantity
                $new_quantity = $item['quantity'] - $quantity;
                $status = calculateInventoryStatus($new_quantity);
                $update_stmt = $conn->prepare("UPDATE inventory SET quantity = ?, status = ? WHERE item_id = ?");
                $update_stmt->bind_param("isi", $new_quantity, $status, $item_id);
                $update_stmt->execute();
                $update_stmt->close();

                // Log dispensation
                $log_stmt = $conn->prepare("INSERT INTO dispensation_log (patient_id, item_id, quantity_disbursed) VALUES (?, ?, ?)");
                $log_stmt->bind_param("iii", $patient_id, $item_id, $quantity);
                $log_stmt->execute();
                $log_stmt->close();

                $message = "Successfully administered $quantity " . $item['item_name'] . " to patient.";
            } else {
                $message = "Insufficient quantity in inventory. Available: " . $item['quantity'];
            }
        } else {
            $message = "Item not found.";
        }
        $stmt->close();
    }
}

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

// Get inventory items for administration modal
$inventory_items = $conn->query("SELECT item_id, item_name FROM inventory ORDER BY item_name");

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
        style="width: auto; height: 60px; display: flex; margin-left: 15px; border-radius: 60px;"
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

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 || strpos($message, 'Insufficient') === 0 || strpos($message, 'Patient not found') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

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
                                    <div class="action-buttons">
                                        <a href="patient_record.php?id=<?php echo $patient['patient_id']; ?>" class="action-btn">View Record</a>
                                        <?php if (getUserRole() === 'Admin'): ?>
                                            <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="action-btn">Edit</a>
                                            <a href="add_report.php?patient_id=<?php echo $patient['patient_id']; ?>" class="action-btn">Add Report</a>
                                            <button type="button" class="action-btn" onclick="openAdministerModal(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>')">Administer</button>
                                            <a href="delete_patient.php?id=<?php echo $patient['patient_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this patient?')">Delete</a>
                                        <?php endif; ?>
                                    </div>
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

    <!-- Administer Modal -->
    <div id="administerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdministerModal()">&times;</span>
            <h3>Administer Item to Patient</h3>
            <p id="patientInfo"></p>
            <form method="POST" action="">
                <input type="hidden" id="modalPatientId" name="patient_id">
                <div class="form-group">
                    <label for="item_id">Select Item:</label>
                    <select id="item_id" name="item_id" required>
                        <option value="">Choose an item...</option>
                        <?php if ($inventory_items->num_rows > 0): ?>
                            <?php while ($item = $inventory_items->fetch_assoc()): ?>
                                <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <button type="submit" name="administer_item" class="submit-btn">Administer</button>
            </form>
        </div>
    </div>

    <script>
        function openAdministerModal(patientId, patientName) {
            document.getElementById('administerModal').style.display = 'block';
            document.getElementById('modalPatientId').value = patientId;
            document.getElementById('patientInfo').textContent = 'Patient: ' + patientName;
        }

        function closeAdministerModal() {
            document.getElementById('administerModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('administerModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
