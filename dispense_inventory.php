<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$item = null;
$patients = [];

$conn = getDBConnection();

// Get item details
if (isset($_GET['id'])) {
    $item_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        $message = "Item not found.";
    }
    $stmt->close();
} else {
    $message = "No item specified.";
}

// Get patients for dropdown
$stmt = $conn->prepare("SELECT patient_id, CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, '')) AS full_name FROM patients ORDER BY last_name");
$stmt->execute();
$patients_result = $stmt->get_result();
while ($patient = $patients_result->fetch_assoc()) {
    $patients[] = $patient;
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispense_item'])) {
    $patient_id = (int)$_POST['patient_id'];
    $quantity = (int)$_POST['quantity'];

    if ($item && $quantity > 0 && $quantity <= $item['quantity']) {
        // Insert into dispensation_log
        $stmt = $conn->prepare("INSERT INTO dispensation_log (patient_id, item_id, quantity_disbursed) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $patient_id, $item_id, $quantity);
        if ($stmt->execute()) {
            // Update inventory quantity
            $new_quantity = $item['quantity'] - $quantity;
            $new_status = calculateInventoryStatus($new_quantity);
            $update_stmt = $conn->prepare("UPDATE inventory SET quantity = ?, status = ? WHERE item_id = ?");
            $update_stmt->bind_param("isi", $new_quantity, $new_status, $item_id);
            $update_stmt->execute();
            $update_stmt->close();

            $message = "Item dispensed successfully!";
            header("Location: inventory.php?message=" . urlencode($message));
            exit();
        } else {
            $message = "Error dispensing item: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Invalid quantity or insufficient stock.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Dispense Inventory Item</title>
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
        <h2>Dispense Inventory Item</h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 || strpos($message, 'Invalid') === 0 || strpos($message, 'Item not found') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($item): ?>
            <div class="form-container">
                <h3>Dispensing: <?php echo htmlspecialchars($item['item_name']); ?> (Available: <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>)</h3>
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="patient_id">Patient *</label>
                            <select id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['patient_id']; ?>"><?php echo htmlspecialchars($patient['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity to Dispense *</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="<?php echo $item['quantity']; ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="dispense_item" class="submit-btn">Dispense Item</button>
                    <a href="inventory.php" class="cancel-btn">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <p>Invalid item selected. <a href="inventory.php">Return to Inventory</a></p>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
