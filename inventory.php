<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = '';

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        // Add new inventory item
        $item_name = trim($_POST['item_name']);
        $category = $_POST['category'];
        $quantity = (int)$_POST['quantity'];
        $unit = trim($_POST['unit']);
        $expiration_date = $_POST['expiration_date'];

        if (!empty($item_name) && !empty($category) && !empty($unit)) {
            $status = calculateInventoryStatus($quantity);
            $stmt = $conn->prepare("INSERT INTO inventory (item_name, category, quantity, unit, expiration_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisss", $item_name, $category, $quantity, $unit, $expiration_date, $status);

            if ($stmt->execute()) {
                $message = "Inventory item added successfully!";
            } else {
                $message = "Error adding item: " . $conn->error;
            }
            $stmt->close();
        } else {
            $message = "Please fill in all required fields.";
        }
    } elseif (isset($_POST['administer_item'])) {
        // Administer item to patient
        $patient_id = (int)$_POST['patient_id'];
        $item_id = (int)$_POST['item_id'];
        $quantity_dispensed = (int)$_POST['quantity_dispensed'];

        if ($patient_id > 0 && $item_id > 0 && $quantity_dispensed > 0) {
            // Check if patient exists
            $patient_check = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
            $patient_check->bind_param("i", $patient_id);
            $patient_check->execute();
            $patient_exists = $patient_check->get_result()->num_rows > 0;
            $patient_check->close();

            if ($patient_exists) {
                // Check if item has enough quantity
                $item_check = $conn->prepare("SELECT quantity FROM inventory WHERE item_id = ?");
                $item_check->bind_param("i", $item_id);
                $item_check->execute();
                $current_quantity = $item_check->get_result()->fetch_assoc()['quantity'];
                $item_check->close();

                if ($current_quantity >= $quantity_dispensed) {
                    // Update inventory
                    $new_quantity = $current_quantity - $quantity_dispensed;
                    $status = calculateInventoryStatus($new_quantity);
                    $update_stmt = $conn->prepare("UPDATE inventory SET quantity = ?, status = ? WHERE item_id = ?");
                    $update_stmt->bind_param("isi", $new_quantity, $status, $item_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    // Log dispensation
                    $log_stmt = $conn->prepare("INSERT INTO dispensation_log (patient_id, item_id, quantity_disbursed) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("iii", $patient_id, $item_id, $quantity_dispensed);
                    $log_stmt->execute();
                    $log_stmt->close();

                    $message = "Item administered successfully!";
                } else {
                    $message = "Insufficient quantity in inventory.";
                }
            } else {
                $message = "Patient not found.";
            }
        } else {
            $message = "Please fill in all fields correctly.";
        }
    }
}

// Get inventory items with filters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT * FROM inventory WHERE 1=1";
$params = [];
$types = '';

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $query .= " AND item_name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$query .= " ORDER BY item_name";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$inventory_items = $stmt->get_result();
$stmt->close();

// Get patients for administration modal
$patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name, first_name");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Inventory Management</title>
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
            <li><a href="inventory.php" class="active">Inventory</a></li>
            <li><a href="patients_list.php">Patients List</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
        </ul>
    </nav>

    <main class="container">
        <h2>Inventory Management</h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 || strpos($message, 'Insufficient') === 0 || strpos($message, 'Patient not found') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Item Form -->
        <div class="form-container">
            <h3>Add New Inventory Item</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="item_name">Item Name *</label>
                        <input type="text" id="item_name" name="item_name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Medicine">Medicine</option>
                            <option value="First Aid">First Aid</option>
                            <option value="Equipment">Equipment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <input type="text" id="unit" name="unit" placeholder="e.g., tablets, bottles, pieces" required>
                    </div>
                    <div class="form-group">
                        <label for="expiration_date">Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date">
                    </div>
                </div>
                <button type="submit" name="add_item" class="submit-btn">Add Item</button>
            </form>
        </div>

        <!-- Filters and Search -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="search">Search Items</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by item name">
                    </div>
                    <div class="form-group">
                        <label for="category_filter">Category</label>
                        <select id="category_filter" name="category">
                            <option value="">All Categories</option>
                            <option value="Medicine" <?php echo $category_filter === 'Medicine' ? 'selected' : ''; ?>>Medicine</option>
                            <option value="First Aid" <?php echo $category_filter === 'First Aid' ? 'selected' : ''; ?>>First Aid</option>
                            <option value="Equipment" <?php echo $category_filter === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status_filter">Status</label>
                        <select id="status_filter" name="status">
                            <option value="">All Status</option>
                            <option value="In Stock" <?php echo $status_filter === 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                            <option value="Low Stock" <?php echo $status_filter === 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="Critical" <?php echo $status_filter === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Filter</button>
                <a href="inventory.php">Clear Filters</a>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inventory_items->num_rows > 0): ?>
                        <?php while ($item = $inventory_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $item['item_id']; ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo $item['category']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td><?php echo $item['expiration_date'] ? date('M j, Y', strtotime($item['expiration_date'])) : 'N/A'; ?></td>
                                <td class="status-<?php echo strtolower(str_replace(' ', '-', $item['status'])); ?>"><?php echo $item['status']; ?></td>
                                <td>
                                    <button onclick="openAdministerModal(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" class="action-btn">Administer</button>
                                    <a href="edit_inventory.php?id=<?php echo $item['item_id']; ?>" class="action-btn">Edit</a>
                                    <a href="delete_inventory.php?id=<?php echo $item['item_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No inventory items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Administer Item Modal -->
        <div id="administerModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAdministerModal()">&times;</span>
                <h3>Administer Item to Patient</h3>
                <form method="POST" action="">
                    <input type="hidden" id="administer_item_id" name="item_id">
                    <div class="form-group">
                        <label for="patient_id">Select Patient</label>
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
                        <label for="quantity_dispensed">Quantity to Dispense</label>
                        <input type="number" id="quantity_dispensed" name="quantity_dispensed" min="1" required>
                    </div>
                    <button type="submit" name="administer_item" class="submit-btn">Administer Item</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openAdministerModal(itemId, itemName) {
            document.getElementById('administer_item_id').value = itemId;
            document.getElementById('administerModal').style.display = 'block';
            // You could set the modal title to include the item name
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
