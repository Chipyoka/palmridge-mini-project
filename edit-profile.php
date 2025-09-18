<?php
session_name('USERSESSID');
session_start();
require_once __DIR__ . '/config/config.php';

// Ensure the user is logged in (normal user)
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// Fetch current user details
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = '';
$error   = '';

// Handle form submission with PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid name and email.";
    } else {
        $update = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
        $update->bind_param('sssi', $name, $email, $phone, $userId);
        if ($update->execute()) {
            // Use PRG pattern
            header("Location: edit-profile.php?success=1");
            exit;
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// If redirected after success
if (isset($_GET['success'])) {
    $success = "Profile updated successfully.";
    // Refresh user details after update
    $stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile – <?= htmlspecialchars($user['name']); ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="top-bar">
    <div class="logo"><h1>User Profile Management</h1></div>
    <div class="user-actions">
        <a class="logout" href="user-profile.php">Return</a>
    </div>
</header>

<main class="auth-form">
    <h2 class="text-center">Edit Profile</h2>
    <p class="text-center">Update details below and click save</p>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="profile-form mt-2">
        <label>Name
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
        </label>

        <label>Email
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        </label>

        <label>Phone
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']); ?>">
        </label>

        <button class="primary-btn w-full mt-3" type="submit">Save Changes</button>
    </form>
</main>

</body>

<script src="assets/js/main.js"></script>
</html>
