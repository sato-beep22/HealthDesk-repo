<?php
require_once 'includes/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_staff_id = trim($_POST['admin_staff_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($admin_staff_id) && !empty($name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $message = "Passwords do not match.";
        } else {
            $conn = getDBConnection();

            // Check if admin_staff_id already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE admin_staff_id = ?");
            $stmt->bind_param("s", $admin_staff_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "Admin/Staff ID already exists.";
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (admin_staff_id, password_hash, role, name, email) VALUES (?, ?, 'Staff', ?, ?)");
                $stmt->bind_param("ssss", $admin_staff_id, $password_hash, $name, $email);

                if ($stmt->execute()) {
                    $message = "Registration successful! You can now log in.";
                    // Optionally redirect to login.php
                    // header("Location: login.php");
                    // exit();
                } else {
                    $message = "Registration failed. Please try again.";
                }
            }

            $stmt->close();
            $conn->close();
        }
    } else {
        $message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthDesk - Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <img 
        src="isu_logo.png" 
        alt="ISU Logo" 
        style="width: 150px; height: auto; display: block; margin: 0 auto;"
        >
        <div class="login-header">
            <h1>HealthDesk</h1>
            <p>Student Clinic Management System</p>
        </div>
        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="admin_staff_id">Staff ID</label>
                <input type="text" id="admin_staff_id" name="admin_staff_id" required>
            </div>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <?php if ($message): ?>
                <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <button type="submit" class="login-btn">Register</button>
        </form>
        <div class="forgot-password">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>