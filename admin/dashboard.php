<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
    header('Location: /admin/login.php');
    exit;
}

$user = $_SESSION['user'];

// --- Handle Advanced Search & Filters ---
$searchTerm = $_GET['search'] ?? '';
$category   = $_GET['category'] ?? '';
$action     = $_GET['action'] ?? '';
$status     = $_GET['status'] ?? '';
$location   = $_GET['location'] ?? '';
$minPrice   = $_GET['min_price'] ?? '';
$maxPrice   = $_GET['max_price'] ?? '';
$rooms   = $_GET['rooms'] ?? '';
$datePosted = $_GET['date_posted'] ?? ''; // e.g., last 7 days

$sql = "SELECT * FROM properties WHERE 1 ";
$params = [];
$types = "";

// Search term
if ($searchTerm) {
    $sql .= " AND (title LIKE ? OR description LIKE ?) ";
    $searchWildcard = "%$searchTerm%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

// Filters
if ($category) { $sql .= " AND category = ? "; $params[] = $category; $types .= "s"; }
if ($action) { $sql .= " AND action = ? "; $params[] = $action; $types .= "s"; }
if ($status) { $sql .= " AND status = ? "; $params[] = $status; $types .= "s"; }
if ($location) { $sql .= " AND location = ? "; $params[] = $location; $types .= "s"; }
if ($minPrice) { $sql .= " AND price >= ? "; $params[] = $minPrice; $types .= "d"; }
if ($maxPrice) { $sql .= " AND price <= ? "; $params[] = $maxPrice; $types .= "d"; }
if ($rooms) { $sql .= " AND rooms <= ? "; $params[] = $rooms; $types .= "i"; }
if ($datePosted) {
    $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) ";
    $params[] = $datePosted;
    $types .= "i";
}

// Order newest first
$sql .= " ORDER BY created_at DESC ";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$properties = $result->fetch_all(MYSQLI_ASSOC);

// --- Fetch distinct values for dropdowns ---
$categories = $conn->query("SELECT DISTINCT category FROM properties")->fetch_all(MYSQLI_ASSOC);
$actions    = $conn->query("SELECT DISTINCT action FROM properties")->fetch_all(MYSQLI_ASSOC);
$statuses   = $conn->query("SELECT DISTINCT status FROM properties")->fetch_all(MYSQLI_ASSOC);
$locations  = $conn->query("SELECT DISTINCT location FROM properties")->fetch_all(MYSQLI_ASSOC);



/**
 * Returns a human-readable "time ago" string
 * @param string $datetime The date string from DB (e.g., '2025-09-16 12:34:56')
 * @return string
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) { // < 7 days
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) { // < 30 days
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return 'old';
    }
}
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
                <img src="/assets/images/logo.png" alt="Zambezi Diamond ARPLSS Logo" height="30px">
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
                    <li class="li-active"><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_users.php">Users</a></li>
                </ul>
            </nav>

        </aside>

        <!-- main -->


        <aside class="main-content">

            <!-- Advanced Search & Filters -->
            <form method="GET" class="search-filters" >

                <div class="search-input">
                    <!-- Search Input -->
                    <input type="text" name="search" placeholder="Search by title or description" value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="secondary-btn" type="submit">Search</button>
                </div>

                <div class="filter-section">
                    <h5>Advanced Filters</h5>
                    <div class="all-filters">
                        <!-- Category Filter -->
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['category']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
        
                        <!-- Action Filter -->
                        <select name="action">
                            <option value="">Any Action</option>
                            <?php foreach($actions as $a): ?>
                                <option value="<?= htmlspecialchars($a['action']) ?>" <?= $action === $a['action'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['action']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
        
                        <!-- Status Filter -->
                        <select name="status">
                            <option value="">Any Status</option>
                            <?php foreach($statuses as $s): ?>
                                <option value="<?= htmlspecialchars($s['status']) ?>" <?= $status === $s['status'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['status']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
        
                        <!-- Location Filter -->
                        <select name="location">
                            <option value="">All Locations</option>
                            <?php foreach($locations as $l): ?>
                                <option value="<?= htmlspecialchars($l['location']) ?>" <?= $location === $l['location'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['location']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
        
                        <!-- Price Range -->
                        <input type="number" name="min_price" placeholder="Min Price" value="<?= htmlspecialchars($minPrice) ?>" step="0.01">
                        <input type="number" name="max_price" placeholder="Max Price" value="<?= htmlspecialchars($maxPrice) ?>" step="0.01">
        
                        <!-- Number of Rooms -->
                        <input type="number" name="rooms" placeholder="Max Rooms" value="<?= htmlspecialchars($rooms) ?>">
        
                        <!-- Date Posted -->
                        <select name="date_posted">
                            <option value="">Any Date</option>
                            <option value="1" <?= $datePosted==='1'?'selected':'' ?>>Last 1 Day</option>
                            <option value="7" <?= $datePosted==='7'?'selected':'' ?>>Last 7 Days</option>
                            <option value="30" <?= $datePosted==='30'?'selected':'' ?>>Last 30 Days</option>
                        </select>
                    </div>
                </div>

                
            </form>

            <!-- section head -->
             <div class="row">
                <!-- total properties found -->
                <h4>Properties Found: <?= count($properties) ?></h4>

                <div>
                    <button class="primary-btn-sm">+ Add Property</button>
                </div>
             </div>

            <!-- Property Cards -->
            <div class="property-cards mt-2">
                <?php if($properties): ?>
                    <?php foreach($properties as $prop): ?>
                        <div class="card">
                            <img src="<?= htmlspecialchars($prop['image_path'] ?? '/assets/images/default-property.png') ?>" alt="Property Image">
                            <div class="row">
                                <p class="text-sm"><?= timeAgo($prop['created_at'])  ?></p>
                                <!-- check status and then style green, red or gray -->
                                <p class="text-sm capitalize <?= htmlspecialchars($prop['status']) === 'available' ? 'text-success' : (htmlspecialchars($prop['status']) === 'pending' ? 'text-warning' : 'text-danger') ?>">
                                   <strong> <?= htmlspecialchars($prop['status']) ?></strong>
                                </p>

                            </div>
                            <hr>
                            <div class="row">

                                <p class="action">For <?= htmlspecialchars($prop['action']) ?></p>
                                <h4> K<?= number_format($prop['price'],2) ?></h4>

                            </div>
                            <h3 
                                title="<?= htmlspecialchars($prop['title']) ?>"
                            ><?= htmlspecialchars(substr($prop['title'],0,24)) ?></h3>

                          
                            <hr>

                            <div class="row-2 text-sm">
                               <p><?= $prop['rooms'] ?> rooms</p>
                                <p> in <strong><?= htmlspecialchars($prop['location']) ?></strong> </p>
                            </div>
                        

                             <div class="mt-2"><button class="primary-btn w-full">View Details</button></div>
                           
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No properties found matching your criteria.</p>
                <?php endif; ?>
            </div>

        </aside>




    </main>

</body>

<!-- add js script -->
<script src="/assets/js/main.js"></script>
</html>
