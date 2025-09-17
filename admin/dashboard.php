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
$rooms      = $_GET['rooms'] ?? '';
$datePosted = $_GET['date_posted'] ?? ''; // e.g., last 7 days

// Join properties with their first image
$sql = "
    SELECT p.*, COALESCE(i.image_path, '') AS image_path
    FROM properties p
    LEFT JOIN (
        SELECT property_id, MIN(id) AS first_image_id
        FROM images
        GROUP BY property_id
    ) fi ON p.id = fi.property_id
    LEFT JOIN images i ON i.id = fi.first_image_id
    WHERE 1
";
$params = [];
$types  = "";

// Search term
if ($searchTerm) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?) ";
    $searchWildcard = "%$searchTerm%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

// Filters
if ($category) { $sql .= " AND p.category = ? "; $params[] = $category; $types .= "s"; }
if ($action)   { $sql .= " AND p.action = ? ";   $params[] = $action;   $types .= "s"; }
if ($status)   { $sql .= " AND p.status = ? ";   $params[] = $status;   $types .= "s"; }
if ($location) { $sql .= " AND p.location = ? "; $params[] = $location; $types .= "s"; }
if ($minPrice) { $sql .= " AND p.price >= ? ";   $params[] = $minPrice; $types .= "d"; }
if ($maxPrice) { $sql .= " AND p.price <= ? ";   $params[] = $maxPrice; $types .= "d"; }
if ($rooms)    { $sql .= " AND p.rooms <= ? ";   $params[] = $rooms;    $types .= "i"; }
if ($datePosted) {
    $sql .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) ";
    $params[] = $datePosted;
    $types .= "i";
}

$sql .= " ORDER BY p.created_at DESC ";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$properties = $result->fetch_all(MYSQLI_ASSOC);

// Dropdown options
$categories = $conn->query("SELECT DISTINCT category FROM properties")->fetch_all(MYSQLI_ASSOC);
$actions    = $conn->query("SELECT DISTINCT action FROM properties")->fetch_all(MYSQLI_ASSOC);
$statuses   = $conn->query("SELECT DISTINCT status FROM properties")->fetch_all(MYSQLI_ASSOC);
$locations  = $conn->query("SELECT DISTINCT location FROM properties")->fetch_all(MYSQLI_ASSOC);

/**
 * Return a human-readable relative time string.
 *
 * @param string $datetime e.g. '2025-09-17 23:41:47'
 * @return string
 */
function timeAgo(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return 'Invalid date';
    }

    $now  = time();
    $diff = $now - $timestamp;
    $future = $diff < 0;
    $diff = abs($diff);

    // Time units in seconds
    $units = [
        'year'   => 365 * 24 * 60 * 60,
        'month'  => 30 * 24 * 60 * 60,
        'week'   => 7 * 24 * 60 * 60,
        'day'    => 24 * 60 * 60,
        'hour'   => 60 * 60,
        'minute' => 60,
        'second' => 1,
    ];

    foreach ($units as $name => $seconds) {
        if ($diff >= $seconds) {
            $value = floor($diff / $seconds);
            $label = $value === 1 ? $name : $name . 's';
            return $future
                ? "in {$value} {$label}"
                : "{$value} {$label} ago";
        }
    }

    return 'just now';
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
            <img src="/assets/images/logo.png" alt="Logo" height="36">
            <h2><?= (int)$user['is_admin'] === 1 ? 'Admin' : 'Staff' ?> Dashboard</h2>
            <div>
                <?php
                if (isset($_SESSION['success'])) {
                    echo '<div id="alertBox"  class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                    unset($_SESSION['success']);
                }

                if (isset($_SESSION['error'])) {
                    echo '<div id="alertBox" class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                    unset($_SESSION['error']);
                }
                ?>
            </div>
        </div>
    
        <div class="user-actions">
            <span class="username"><?= htmlspecialchars($user['name']); ?></span> |
            <span class="email"><?= htmlspecialchars($user['email']); ?></span>
            <a class="logout" href="logout.php">Logout</a>
        </div>
    </div>
</header>

<main class="dashboard">
    <aside class="sidebar">
        <nav>
            <ul>
                <li class="li-active"><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Users</a></li>
            </ul>
        </nav>
    </aside>

    <aside class="main-content">
        <!-- Advanced Search & Filters --> 
         <form method="GET" class="search-filters" > 
            <div class="search-input"> 
                <!-- Search Input --> 
                <input type="text" name="search" placeholder="Search by title or description" value="<?= htmlspecialchars($searchTerm) ?>"> 
                <button class="secondary-btn" type="submit">Search</button> 
                <?php
                // Clear all filters button
                if ($searchTerm || $category || $action || $status || $location || $minPrice || $maxPrice || $rooms || $datePosted): ?>
                <button class="logout" type="button" id="clearFilters">Clear</button>
                <?php endif; ?>
            </div>
            <div class="filter-section">
                <h5>Advanced Filters</h5>
                <div class="all-filters"> 
                    <!-- Category Filter --> 
                    <select name="category"> 
                        <option value="">All Categories</option> 
                        <?php foreach($categories as $c): ?> 
                            <option value="<?= htmlspecialchars($c['category']) ?>" 
                                <?= $category === $c['category'] ? 'selected' : '' ?>> 
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
                        <option value="">Any Status</option> <?php foreach($statuses as $s): ?> <option value="<?= htmlspecialchars($s['status']) ?>" <?= $status === $s['status'] ? 'selected' : '' ?>> 
                            <?= htmlspecialchars($s['status']) ?> 
                        </option> <?php endforeach; ?> </select> 
                        <!-- Location Filter --> 
                         <select name="location"> <option value="">All Locations</option> 
                         <?php foreach($locations as $l): ?> <option value="<?= htmlspecialchars($l['location']) ?>" <?= $location === $l['location'] ? 'selected' : '' ?>> <?= htmlspecialchars($l['location']) ?> </option> <?php endforeach; ?> </select> 
                            <!-- Price Range --> 
                            <input type="number" name="min_price" placeholder="Min Price" value="<?= htmlspecialchars($minPrice) ?>" step="0.01"> <input type="number" name="max_price" placeholder="Max Price" value="<?= htmlspecialchars($maxPrice) ?>" step="0.01"> 
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

        <div class="row">
            <h4>Properties Found: <?= count($properties) ?></h4>
            <div>
                <button onclick="window.location.href='add-property.php'" class="primary-btn-sm">+ Add Property</button>
            </div>
        </div>

        <div class="property-cards mt-2">
            <?php if($properties): ?>
                <?php foreach($properties as $prop): ?>
                    <?php
                        $imgSrc = $prop['image_path'] && file_exists(__DIR__.'/../'.$prop['image_path'])
                                  ? '/'.$prop['image_path']
                                  : '/assets/images/default-property.png';
                    ?>
                    <div class="card">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Property Image">
                        <div class="row">
                            <p class="text-sm"><?= timeAgo($prop['created_at']) ?></p>
                            <p class="text-sm capitalize 
                               <?= $prop['status']==='available'?'text-success':($prop['status']==='pending'?'text-warning':'text-danger') ?>">
                                <strong><?= htmlspecialchars($prop['status']) ?></strong>
                            </p>
                        </div>
                        <hr>
                        <div class="row">
                            <p class="action">For <?= htmlspecialchars($prop['action']) ?></p>
                            <h4 class="price">K<?= number_format($prop['price'],2) ?></h4>
                        </div>
                        <h4 title="<?= htmlspecialchars($prop['title']) ?>">
                            <?= htmlspecialchars(substr($prop['title'],0,24)) ?>
                        </h4>
                        <hr>
                        <div class="row-2 text-sm">
                            <p><?= (int)$prop['rooms'] ?> rooms</p>
                            <p>in <strong><?= htmlspecialchars($prop['location']) ?></strong></p>
                        </div>
                        <div class="mt-2">
                            <button onclick="window.location.href='view-property.php?id=<?= (int)$prop['id'] ?>'"
                                    class="primary-btn-sm w-full">View Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No properties found matching your criteria.</p>
            <?php endif; ?>
        </div>
    </aside>
</main>
<script src="/assets/js/main.js"></script>
</body>
</html>
