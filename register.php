<?php
session_name('USERSESSID');
session_start();
require_once __DIR__ . '/config/config.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // --- Basic Validation ---
    if ($name === '' || $phone === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
        $error = "Invalid phone number format.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Check for existing email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = "An account with this email already exists.";
        } else {
            // Insert new user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare(
                "INSERT INTO users (name, phone, email, password, created_at) VALUES (?, ?, ?, ?, NOW())"
            );
            $insert->bind_param('ssss', $name, $phone, $email, $hashed);

            if ($insert->execute()) {
                // PRG pattern: store success in session and redirect
                $_SESSION['register_success'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// Retrieve success message if redirected back
if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Registration – Zambezi ARPLSS</title>
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
    <main class="auth-form">
        <h2 class="text-center">Create an Account</h2>
        <p class="desc">Please enter your credentials to create account.</p>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3" autocomplete="off">
            <label>Name:
                <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
            </label>
            <label>Phone:
                <input type="text" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required>
            </label>
            <label>Email:
                <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </label>

            <hr>
            <div class="mt-3"></div>
            <label>Password:
                <input type="password" name="password" required>
            </label>
            <label>Confirm Password:
                <input type="password" name="confirm_password" required>
            </label>
            <button type="submit" class="primary-btn w-full mt-2">Register</button>
        </form>

        <p class="hyperlink mt-2 text-center"><a href="login.php">Already have an account? <b>Login</b></a></p>
        <p class="hyperlink mt-3 text-center"><a href="index.php">Return Home</a></p>
    </main>
</body>
</html>
