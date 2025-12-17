<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$message = '';


if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}


$current_time = time();
$cooldown_time = 120; 
if ($_SESSION['login_attempts'] >= 5) {
    if (($current_time - $_SESSION['last_attempt_time']) < $cooldown_time) {
        $remaining_time = $cooldown_time - ($current_time - $_SESSION['last_attempt_time']);
        $message = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_staff_id = trim($_POST['admin_staff_id']);
    $password = $_POST['password'];

    if (!empty($admin_staff_id) && !empty($password)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, password_hash, role, name FROM users WHERE admin_staff_id = ?");
        $stmt->bind_param("s", $admin_staff_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                
                $_SESSION['login_attempts'] = 0;
                $_SESSION['last_attempt_time'] = 0;

                if ($user['role'] === 'Admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: add_record.php");
                }
                exit();
            } else {
                $message = "Invalid password.";
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
            }
        } else {
            $message = "User not found.";
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        }

        $stmt->close();
        $conn->close();
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
    <title>HealthDesk - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div style="text-align: center;"> 
        <img
        src="isu_logo.png"
        alt="ISU Logo"
        style="width: 150px; height: 150px; display: block; margin: 0 auto; border-radius: 50%; object-fit: cover;"
        >
        </div>
        <div class="login-header">
            <h1>HealthDesk</h1>
            <p>Student Clinic Management System</p>
        </div>
        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="admin_staff_id">Admin ID / Staff ID</label>
                <input type="text" id="admin_staff_id" name="admin_staff_id" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <?php if ($message): ?>
                <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="forgot-password">
            <a href="register.php">Register as Staff?</a>
        </div>
    </div>
    
</body>
</html>
