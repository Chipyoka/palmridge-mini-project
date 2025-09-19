<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// Capture property ID either from POST or an existing session
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propertyId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch property details with image_path using the same logic as dashboard.php
$stmt = $conn->prepare("
    SELECT p.*, u.name AS agent_name, u.email AS agent_email, COALESCE(i.image_path, '') AS image_path
    FROM properties p
    LEFT JOIN users u ON p.agent_id = u.id
    LEFT JOIN (
        SELECT property_id, MIN(id) AS first_image_id
        FROM images
        GROUP BY property_id
    ) fi ON p.id = fi.property_id
    LEFT JOIN images i ON i.id = fi.first_image_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $propertyId);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
if (!$property) {
    header('Location: dashboard.php');
    exit;
}

// Fetch bids newest first
$bidsStmt = $conn->prepare("
    SELECT b.*, u.name AS bidder_name, u.phone AS bidder_phone
    FROM bids b
    JOIN users u ON b.user_id = u.id
    WHERE b.property_id = ?
    ORDER BY b.created_at DESC
");
$bidsStmt->bind_param('i', $propertyId);
$bidsStmt->execute();
$bids = $bidsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch up to 4 similar properties (same action & price <= target)
$similarStmt = $conn->prepare("
    SELECT p.*, COALESCE(i.image_path, '') AS image_path
    FROM properties p
    LEFT JOIN (
        SELECT property_id, MIN(id) AS first_image_id
        FROM images
        GROUP BY property_id
    ) fi ON p.id = fi.property_id
    LEFT JOIN images i ON i.id = fi.first_image_id
    WHERE p.action = ? AND p.price <= ? AND p.id <> ?
    ORDER BY p.price DESC
    LIMIT 4
");
$similarStmt->bind_param('sdi', $property['action'], $property['price'], $propertyId);
$similarStmt->execute();
$similar = $similarStmt->get_result()->fetch_all(MYSQLI_ASSOC);

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return $diff.'s ago';
    if ($diff < 3600) return floor($diff/60).' mins ago';
    if ($diff < 86400) return floor($diff/3600).' hours ago';
    if ($diff < 604800) return floor($diff/86400).' days ago';
    return 'old';
}

// Determine image source with fallback logic (same as dashboard.php)
$imgSrc = $property['image_path'] && file_exists(__DIR__.'/../'.$property['image_path'])
          ? '/odl_mini_projects/zambezi-mini-project/'.$property['image_path']
          : '/odl_mini_projects/zambezi-mini-project/assets/images/default-property.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Property Details – Zambezi Diamond ARPLSS</title>
<link rel="stylesheet" href="/odl_mini_projects/zambezi-mini-project/assets/css/style.css">
</head>
<body>
<header class="top-bar">
    <div class="logo"><h1>Property Details</h1></div>
    <div class="user-actions">
        <a class="logout" href="dashboard.php?return=1">Return</a>
    </div>
</header>

<main class="property-view">
    <!-- Main Property Card -->
    <section class="property-card">
        <aside class="property-image">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Property Image">
        </aside>
        <aside class="property-details">
            <div class="row">
                <h2><?= htmlspecialchars($property['title']); ?></h2>
                <p class="action">For <?= htmlspecialchars($property['action']); ?></p>
            </div>
            <p><?= htmlspecialchars($property['description']); ?></p>
           
            <hr>

            <div class="row">
                <div>
                    <p><strong>Price:</strong> K<?= number_format($property['price'],2); ?></p>
                    <p><strong>Rooms:</strong> <?= (int)$property['rooms']; ?></p>
                </div>
                <div>
                    <p><strong>Location:</strong> <?= htmlspecialchars($property['location']); ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($property['status']); ?></p>
                </div>
            </div>

            <hr>

            <p><strong>Posted:</strong> <?= timeAgo($property['created_at']); ?></p>

            <hr>

            <div class="mt-4 flex">
                <button 
                    class="primary-btn" 
                    onclick="window.location.href='edit-property.php?id=<?= (int)$property['id']; ?>'"
                >
                Edit Property
            </button>
            <button 
                class="danger-btn" 
                onclick="confirmDelete(<?= (int)$property['id']; ?>)"
            >
                Delete Property
            </button>
            </div>
        </aside>
    </section>

    <!-- Bids Section -->
    <section class="bids">
        <h3>Bids</h3>
        <?php if ($bids): ?>
            <ul>
                <li class="bid-record bold bg-gray sm-none">
                    <p>Name</p>
                    <p>Phone</p>
                    <p>Offer Amount</p>
                    <p>Date</p>
                </li>
                <?php foreach ($bids as $bid): ?>
                    <li class="bid-record">
                        <p><strong><?= htmlspecialchars($bid['bidder_name']); ?></strong></p>
                        <p><?= htmlspecialchars($bid['bidder_phone']); ?></p>
                        <p>K<?= number_format($bid['amount'],2); ?></p>
                        <p><?= timeAgo($bid['created_at']); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No bids yet.</p>
        <?php endif; ?>
    </section>

    <!-- Similar Properties Section -->
    <?php if ($similar): ?>
    <section class="similar-properties">
        <h3>Similar Properties</h3>
        <div class="property-cards">
            <?php foreach($similar as $similarProp): ?>
                <?php
                    $similarImgSrc = $similarProp['image_path'] && file_exists(__DIR__.'/../uploads/'.$similarProp['image_path'])
                                  ? '/'.$similarProp['image_path']
                                  : '/odl_mini_projects/zambezi-mini-project/assets/images/default-property.png';
                ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($similarImgSrc) ?>" alt="Similar Property Image">
                    <div class="row">
                        <p class="text-sm"><?= timeAgo($similarProp['created_at']) ?></p>
                        <p class="text-sm capitalize 
                           <?= $similarProp['status']==='available'?'text-success':($similarProp['status']==='pending'?'text-warning':'text-danger') ?>">
                            <strong><?= htmlspecialchars($similarProp['status']) ?></strong>
                        </p>
                    </div>
                    <hr>
                    <div class="row">
                        <p class="action">For <?= htmlspecialchars($similarProp['action']) ?></p>
                        <h4 class="price">K<?= number_format($similarProp['price'],2) ?></h4>
                    </div>
                    <h4 title="<?= htmlspecialchars($similarProp['title']) ?>">
                        <?= htmlspecialchars(substr($similarProp['title'],0,24)) ?>
                    </h4>
                    <hr>
                    <div class="row-2 text-sm">
                        <p><?= (int)$similarProp['rooms'] ?> rooms</p>
                        <p>in <strong><?= htmlspecialchars($similarProp['location']) ?></strong></p>
                    </div>
                    <div class="mt-2">
                        <button onclick="window.location.href='view-property.php?id=<?= (int)$similarProp['id'] ?>'"
                                class="primary-btn-sm w-full">View Details</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

</body>
    <script>
        function confirmDelete(propertyId) {
            if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
                window.location.href = 'delete-property.php?id=' + propertyId;
            }
        }
    </script>
</html>
<?php
// Clear selection when returning to dashboard
if (isset($_GET['return'])) {
    unset($_SESSION['selectedProperty']);
}
?>