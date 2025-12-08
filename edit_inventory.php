<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id <= 0) {
    header("Location: inventory.php");
    exit();
}

$message = '';

$conn = getDBConnection();

// Get item details
$stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: inventory.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $quantity = (int)$_POST['quantity'];
    $unit = trim($_POST['unit']);
    $expiration_date = $_POST['expiration_date'];

    if (!empty($item_name) && !empty($category) && !empty($unit)) {
        $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, category = ?, quantity = ?, unit = ?, expiration_date = ? WHERE item_id = ?");
        $stmt->bind_param("ssissi", $item_name, $category, $quantity, $unit, $expiration_date, $item_id);

        if ($stmt->execute()) {
            $message = "Inventory item updated successfully!";
            // Refresh item data
            $stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id = ?");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
        } else {
            $message = "Error updating item: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Please fill in all required fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Edit Inventory Item</title>
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
        <h2>Edit Inventory Item</h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="item_name">Item Name *</label>
                        <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="Medicine" <?php echo $item['category'] === 'Medicine' ? 'selected' : ''; ?>>Medicine</option>
                            <option value="First Aid" <?php echo $item['category'] === 'First Aid' ? 'selected' : ''; ?>>First Aid</option>
                            <option value="Equipment" <?php echo $item['category'] === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($item['unit']); ?>" placeholder="e.g., tablets, bottles, pieces" required>
                    </div>
                    <div class="form-group">
                        <label for="expiration_date">Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date" value="<?php echo $item['expiration_date']; ?>">
                    </div>
                </div>
                <button type="submit" class="submit-btn">Update Item</button>
                <a href="inventory.php" class="action-btn">Back to Inventory</a>
            </form>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
