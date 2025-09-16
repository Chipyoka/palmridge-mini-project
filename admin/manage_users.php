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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Zambezi Diamond ARPLSS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

    <header>
        <div class="top-bar">
            <div class="logo">
                <img src="/assets/images/logo.png" alt="Zambezi Diamond ARPLSS Logo" height="48px">
                <?php
                if ((int)$user['is_admin'] === 1) {
                    echo '<h1>Admin Dashboard</h1>';
                } else {
                    echo '<h1>Staff Dashboard</h1>';
                }
                ?>
            </div>
        
            <div class="user-actions">
            
                <!-- name -->
                <span class="username">
                    <?php echo htmlspecialchars($user['name']); ?>
                </span>
                <span> | </span>
                <!-- email -->
                <span class="email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </span>
                <a class="logout" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <!-- side navigation -->
        <aside class="sidebar">
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li class="li-active"><a href="manage_users.php">Users</a></li>
                </ul>
            </nav>

        </aside>

        <!-- main -->
        <aside></aside>
    </main>

</body>

<!-- add js script -->
<script src="/assets/js/main.js"></script>
</html>
