<?php
session_name('USERSESSID');
session_start();
require_once __DIR__ . '/includes/db.php'; // central DB connection

// Redirect if already logged in
if (isset($_SESSION['user']) && !$_SESSION['user']['is_admin']) {
    header("Location: index.php");
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
            // Ensure the user has no admin rights
            if ((int)$user['is_admin'] === 0 || (int)$user['is_staff'] === 0) {
                // Store entire user record (minus password) in session
                unset($user['password']);
                $_SESSION['user'] = $user;

                // Optional: regenerate session ID to prevent fixation
                session_regenerate_id(true);

                header("Location: user-profile.php");
                exit;
            } else {
                $error = "Kindly use staff login.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Zambezi Property</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <div class="top-bar-user">
            <div class="logo">
                <h2 >Advanced Real-Time Property Listing and Search System</h2>
            </div>

        </div>
    </header>

    <div class="auth-form">
        <h2 class="text-center">Login</h2>
        <p class="desc">Please enter your credentials to continue.</p>

        <!-- error message -->
        <?php if ($error): ?>
            <div class="error">
                <p ><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- form -->
        <form method="POST" action="" class="mt-2">
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>
            <p class="hyperlink"><a href="../reset-password.php">Forgot password? Reset</a></p>


            <div class="login-btn">
                <button id="loginBtn" class="primary-btn w-full" type="submit">Login</button>
            </div>
        </form>
        <p class="hyperlink mt-2 text-center"><a href="register.php">Don't have an account? <b>Register</b></a></p>
        <p class="hyperlink mt-3 text-center"><a href="index.php">Return Home</a></p>
    </div>

</body>

<!-- add js script -->
<script src="/assets/js/main.js"></script>
</html>
