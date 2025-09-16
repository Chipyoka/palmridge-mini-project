<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php'; // central DB connection

// Redirect if already logged in
if (isset($_SESSION['user']) && $_SESSION['user']['is_admin']) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Ensure the user has admin rights
            if ((int)$user['is_admin'] === 1 || (int)$user['is_staff'] === 1) {
                // Store entire user record (minus password) in session
                unset($user['password']);
                $_SESSION['user'] = $user;

                // Optional: regenerate session ID to prevent fixation
                session_regenerate_id(true);

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Access denied. Admin/staff privileges required.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please provide both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login – Zambezi Property</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container card" style="max-width:400px;margin-top:60px;">
    <h2>Admin Login</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
