<?php
// admin/dashboard.php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only admin/staff may view ---
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
    header('Location: /admin/login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard – Zambezi Property</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header>
    <div class="top-bar">
        <div class="logo">ARPLSS</div>
        <div class="page-title">Admin Dashboard</div>
        <div class="user-actions">
            <span class="user">
                <?php echo htmlspecialchars($user['email']); ?>
            </span>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</header>

<main class="container">
    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
    <p>Your role: <?php echo htmlspecialchars($user['role']); ?></p>
    <p>Created at: <?php echo htmlspecialchars($user['created_at']); ?></p>

    <!-- Additional admin content and property management UI goes here -->
</main>

</body>
</html>
