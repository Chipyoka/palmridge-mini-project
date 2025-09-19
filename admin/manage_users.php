<?php
// admin/manage_users.php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only admin may view
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
 header("Location: /odl_mini_projects/zambezi-mini-project/index.php");
    exit;
}

$user = $_SESSION['user'];

// Handle user actions (make/remove staff, make/remove admin, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $targetUserId = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        // Prevent self-modification
        if ($targetUserId === (int)$user['id']) {
            $_SESSION['error'] = "You cannot modify your own role.";
            header('Location: manage_users.php');
            exit;
        }
        
        // Check if user exists and get current role
        $checkUser = $conn->prepare("SELECT id, is_staff, is_admin FROM users WHERE id = ?");
        $checkUser->bind_param('i', $targetUserId);
        $checkUser->execute();
        $userData = $checkUser->get_result()->fetch_assoc();
        $checkUser->close();
        
        if (!$userData) {
            $_SESSION['error'] = "User not found.";
            header('Location: manage_users.php');
            exit;
        }
        
        // Perform the requested action
        switch ($action) {
            case 'make_staff':
                $stmt = $conn->prepare("UPDATE users SET is_staff = 1 WHERE id = ?");
                $stmt->bind_param('i', $targetUserId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User promoted to staff successfully.";
                } else {
                    $_SESSION['error'] = "Failed to promote user to staff.";
                }
                $stmt->close();
                break;
                
            case 'remove_staff':
                // If user is admin, don't allow removing staff status
                if ($userData['is_admin']) {
                    $_SESSION['error'] = "Cannot remove staff status from an admin user.";
                    break;
                }
                
                $stmt = $conn->prepare("UPDATE users SET is_staff = 0 WHERE id = ?");
                $stmt->bind_param('i', $targetUserId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Staff status removed successfully.";
                } else {
                    $_SESSION['error'] = "Failed to remove staff status.";
                }
                $stmt->close();
                break;
                
            case 'make_admin':
                // First make user staff if not already
                if (!$userData['is_staff']) {
                    $makeStaff = $conn->prepare("UPDATE users SET is_staff = 1 WHERE id = ?");
                    $makeStaff->bind_param('i', $targetUserId);
                    $makeStaff->execute();
                    $makeStaff->close();
                }
                
                $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->bind_param('i', $targetUserId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User promoted to admin successfully.";
                } else {
                    $_SESSION['error'] = "Failed to promote user to admin.";
                }
                $stmt->close();
                break;
                
            case 'remove_admin':
                $stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                $stmt->bind_param('i', $targetUserId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Admin privileges removed successfully.";
                } else {
                    $_SESSION['error'] = "Failed to remove admin privileges.";
                }
                $stmt->close();
                break;
                
            case 'delete_user':
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $targetUserId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User deleted successfully.";
                } else {
                    $_SESSION['error'] = "Failed to delete user.";
                }
                $stmt->close();
                break;
                
            default:
                $_SESSION['error'] = "Invalid action.";
                break;
        }
        
        header('Location: manage_users.php');
        exit;
    }
}

// Search user implementation
$searchTerm = $_GET['search'] ?? '';

// Fetch all users with search filter
$sql = "SELECT id, name, phone, email, is_staff, is_admin, created_at FROM users WHERE 1";
$params = [];
$types = "";

if ($searchTerm) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $searchWildcard = "%$searchTerm%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – Zambezi Diamond ARPLSS</title>
    <link rel="stylesheet" href="/odl_mini_projects/zambezi-mini-project/assets/css/style.css">
</head>
<body>

    <header>
        <div class="top-bar">
            <div class="logo">
                <button id="menuBtn" class="primary-btn-sm sm-only">=</button>
                <img src="/odl_mini_projects/zambezi-mini-project/assets/images/logo.png" alt="Logo" height="36">
                <h2 class="sm-none"><?= (int)$user['is_admin'] === 1 ? 'Admin' : 'Staff' ?> Dashboard - Manage Users</h2>
                <div class="alert-container">
                    <?php
                    if (isset($_SESSION['success'])) {
                        echo '<div id="alertBox" class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                        unset($_SESSION['success']);
                    }

                    if (isset($_SESSION['error'])) {
                        echo '<div id="alertBox" class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>
                </div>
                <a class="logout sm-only" href="logout.php">Logout</a>
            </div>
        
            <div class="user-actions sm-none">
                <span class="username"><?= htmlspecialchars($user['name']); ?> |</span>
                <span class="email"><?= htmlspecialchars($user['email']); ?></span>
                <a class="logout" href="logout.php">Logout</a>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <!-- side navigation -->
        <aside class="sidebar sm-none" id="sidebar">
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li class="li-active"><a href="manage_users.php">Users</a></li>
                </ul>
            </nav>
        </aside>

        <div class="overlay" id="overlay"></div>

        <!-- main -->
        <aside class="main-content">

            <!-- search user -->
            <form action="" method="get" class="search-filters">
                <div class="search-input"> 
                    <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($searchTerm) ?>"> 
                    <button class="secondary-btn" type="submit">Search</button> 
                    <?php if ($searchTerm): ?>
                    <button class="logout" type="button" id="clearFilters">Clear</button>
                    <?php endif; ?>
                </div>
            </form>

            <!-- render all users as row records with buttons to make admin, make staff, and delete -->
            <div class="users-table-container mt-3">
                <p class="caption-warning sm-only"><strong>Warning:</strong> Use a laptop or desktop to see full user actions.</p>
                <?php if ($users): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th class="sm-none">Email</th>
                                <th >Role</th>
                                <th class="sm-none">Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $userRow): ?>
                                <?php if ($userRow['id'] === $user['id']) continue; ?>
                                <tr>
                                    <td><?= htmlspecialchars($userRow['name']) ?></td>
                                    <td><?= htmlspecialchars($userRow['phone']) ?></td>
                                    <td class="sm-none"><?= htmlspecialchars($userRow['email']) ?></td>
                                    <td>
                                        <span class="role-badge <?= $userRow['is_admin'] ? 'admin' : ($userRow['is_staff'] ? 'staff' : 'user') ?>">
                                            <?= $userRow['is_admin'] ? 'Admin' : ($userRow['is_staff'] ? 'Staff' : 'User') ?>
                                        </span>
                                    </td>
                                    <td class="sm-none"><?= date('M j, Y', strtotime($userRow['created_at'])) ?></td>
                                    <td class="actions">
                                        <form method="post" class="action-form">
                                            <input type="hidden" name="user_id" value="<?= $userRow['id'] ?>">
                                            
                                            <!-- Staff Actions -->
                                            <?php if (!$userRow['is_staff']): ?>
                                                <button type="submit" name="action" value="make_staff" class="badge-primary-outlined sm-none" 
                                                        onclick="return confirm('Make this user a staff member?')">
                                                    Make Staff
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="remove_staff" class="badge-primary-outlined sm-none"
                                                        onclick="return confirm('Remove staff status from this user?')"
                                                        <?= $userRow['is_admin'] ? 'disabled title="Cannot remove staff status from admin"' : '' ?>>
                                                    Remove Staff
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Admin Actions -->
                                            <?php if (!$userRow['is_admin']): ?>
                                                <button type="submit" name="action" value="make_admin" class="badge-primary sm-none"
                                                        onclick="return confirm('Make this user an admin? They will automatically become staff as well.')">
                                                    Make Admin
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="remove_admin" class="badge-primary sm-none"
                                                        onclick="return confirm('Remove admin privileges from this user?')">
                                                    Remove Admin
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Delete Action -->
                                            <button type="submit" name="action" value="delete_user" class="badge-danger"
                                                    onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-users">No users found<?= $searchTerm ? ' matching your search' : '' ?>.</p>
                <?php endif; ?>
            </div>
        </aside>
    </main>

<!-- add js script -->
<script type = "module" src="/odl_mini_projects/zambezi-mini-project/assets/js/main.js"></script>
</body>
</html>