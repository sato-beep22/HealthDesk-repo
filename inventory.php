<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = '';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

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

// Get dispensation history
$dispensation_query = "SELECT dl.log_id, dl.quantity_disbursed, dl.date,
                             p.last_name, p.first_name, p.middle_name,
                             i.item_name
                      FROM dispensation_log dl
                      JOIN patients p ON dl.patient_id = p.patient_id
                      JOIN inventory i ON dl.item_id = i.item_id
                      ORDER BY dl.date DESC";

$dispensation_stmt = $conn->prepare($dispensation_query);
$dispensation_stmt->execute();
$dispensation_history = $dispensation_stmt->get_result();
$dispensation_stmt->close();

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
                                    <a href="edit_inventory.php?id=<?php echo $item['item_id']; ?>" class="action-btn">Edit</a>
                                    <a href="dispense_inventory.php?id=<?php echo $item['item_id']; ?>" class="action-btn">Dispense</a>
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

        <!-- Dispensation History -->
        <div class="table-container">
            <h3>Dispensation History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Patient Name</th>
                        <th>Item Name</th>
                        <th>Quantity Dispensed</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dispensation_history->num_rows > 0): ?>
                        <?php while ($log = $dispensation_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo htmlspecialchars($log['last_name'] . ', ' . $log['first_name'] . ' ' . $log['middle_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['item_name']); ?></td>
                                <td><?php echo $log['quantity_disbursed']; ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($log['date'])); ?></td>
                                <td>
                                    <a href="delete_dispensation.php?id=<?php echo $log['log_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this dispensation log?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No dispensation history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>


    <?php include 'includes/footer.php'; ?>
</body>
</html>
