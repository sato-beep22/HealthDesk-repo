<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_name = trim($_POST['middle_name']);
    $age = (int)$_POST['age'];
    $sex = $_POST['sex'];
    $date_of_birth = $_POST['date_of_birth'];
    $place_of_birth = trim($_POST['place_of_birth']);
    $home_address = trim($_POST['home_address']);
    $citizenship = trim($_POST['citizenship']);
    $civil_status = $_POST['civil_status'];
    $parent_guardian = trim($_POST['parent_guardian']);
    $contact_no = trim($_POST['contact_no']);

    // Physical Examination
    $height = (float)$_POST['height'];
    $weight = (float)$_POST['weight'];
    $bmi = (float)$_POST['bmi'];
    $bp = trim($_POST['bp']);
    $temp = (float)$_POST['temp'];
    $pr = (int)$_POST['pr'];
    $rr = (int)$_POST['rr'];
    $heent = trim($_POST['heent']);
    $chest = trim($_POST['chest']);
    $heart = trim($_POST['heart']);
    $lungs = trim($_POST['lungs']);
    $abdomen = trim($_POST['abdomen']);
    $genital = trim($_POST['genital']);
    $skin = trim($_POST['skin']);

    // Past Medical History
    $chronic_illness = trim($_POST['chronic_illness']);
    $allergies = trim($_POST['allergies']);
    $operations = trim($_POST['operations']);
    $accidents_injuries = trim($_POST['accidents_injuries']);
    $medicines_regular = trim($_POST['medicines_regular']);

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($age) || empty($sex)) {
        $errors[] = "Please fill in all required fields.";
    }

    if (empty($errors)) {
        $conn = getDBConnection();

        $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, middle_name, age, sex, date_of_birth, place_of_birth, home_address, citizenship, civil_status, parent_guardian, contact_no, height, weight, bmi, bp, temp, pr, rr, heent, chest, heart, lungs, abdomen, genital, skin, chronic_illness, allergies, operations, accidents_injuries, medicines_regular) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssisssssssssddddiissssssssssss", $first_name, $last_name, $middle_name, $age, $sex, $date_of_birth, $place_of_birth, $home_address, $citizenship, $civil_status, $parent_guardian, $contact_no, $height, $weight, $bmi, $bp, $temp, $pr, $rr, $heent, $chest, $heart, $lungs, $abdomen, $genital, $skin, $chronic_illness, $allergies, $operations, $accidents_injuries, $medicines_regular);

        if ($stmt->execute()) {
            $message = "Patient record added successfully!";
        } else {
            $message = "Error adding patient record: " . $conn->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Add Patient Record</title>
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
            <li><a href="add_record.php" class="active">Add Records</a></li>
            <?php if (getUserRole() === 'Admin'): ?>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="patients_list.php">Patients List</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <h2>Add Patient Record</h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form class="form-container" method="POST" action="">
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name">
                    </div>
                    <div class="form-group">
                        <label for="age">Age *</label>
                        <input type="number" id="age" name="age" required>
                    </div>
                    <div class="form-group">
                        <label for="sex">Sex *</label>
                        <select id="sex" name="sex" required>
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label for="place_of_birth">Place of Birth</label>
                        <input type="text" id="place_of_birth" name="place_of_birth">
                    </div>
                    <div class="form-group">
                        <label for="home_address">Home Address</label>
                        <input type="text" id="home_address" name="home_address">
                    </div>
                    <div class="form-group">
                        <label for="citizenship">Citizenship</label>
                        <input type="text" id="citizenship" name="citizenship">
                    </div>
                    <div class="form-group">
                        <label for="civil_status">Civil Status</label>
                        <select id="civil_status" name="civil_status">
                            <option value="">Select Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="parent_guardian">Parent/Guardian</label>
                        <input type="text" id="parent_guardian" name="parent_guardian">
                    </div>
                    <div class="form-group">
                        <label for="contact_no">Contact No.</label>
                        <input type="text" id="contact_no" name="contact_no">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Physical Examination</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <input type="number" step="0.1" id="height" name="height">
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" step="0.1" id="weight" name="weight">
                    </div>
                    <div class="form-group">
                        <label for="bmi">BMI</label>
                        <input type="number" step="0.1" id="bmi" name="bmi">
                    </div>
                    <div class="form-group">
                        <label for="bp">Blood Pressure</label>
                        <input type="text" id="bp" name="bp" placeholder="e.g., 120/80">
                    </div>
                    <div class="form-group">
                        <label for="temp">Temperature (°C)</label>
                        <input type="number" step="0.1" id="temp" name="temp">
                    </div>
                    <div class="form-group">
                        <label for="pr">Pulse Rate</label>
                        <input type="number" id="pr" name="pr">
                    </div>
                    <div class="form-group">
                        <label for="rr">Respiratory Rate</label>
                        <input type="number" id="rr" name="rr">
                    </div>
                    <div class="form-group">
                        <label for="heent">HEENT</label>
                        <textarea id="heent" name="heent"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="chest">Chest</label>
                        <textarea id="chest" name="chest"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="heart">Heart</label>
                        <textarea id="heart" name="heart"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="lungs">Lungs</label>
                        <textarea id="lungs" name="lungs"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="abdomen">Abdomen</label>
                        <textarea id="abdomen" name="abdomen"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="genital">Genital</label>
                        <textarea id="genital" name="genital"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="skin">Skin</label>
                        <textarea id="skin" name="skin"></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Past Medical History</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="chronic_illness">Chronic Illness</label>
                        <textarea id="chronic_illness" name="chronic_illness"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="allergies">Allergies</label>
                        <textarea id="allergies" name="allergies"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="operations">Operations Experienced</label>
                        <textarea id="operations" name="operations"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="accidents_injuries">Accidents or Injuries</label>
                        <textarea id="accidents_injuries" name="accidents_injuries"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="medicines_regular">Medicines Taken Regularly</label>
                        <textarea id="medicines_regular" name="medicines_regular"></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Patient Record</button>
        </form>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
