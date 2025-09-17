<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// Capture property ID either from POST or an existing sessio

$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propertyId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch property details
$stmt = $conn->prepare("
    SELECT p.*, u.name AS agent_name, u.email AS agent_email
    FROM properties p
    LEFT JOIN users u ON p.agent_id = u.id
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
    SELECT * FROM properties
    WHERE action = ? AND price <= ? AND id <> ?
    ORDER BY price DESC
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Property Details – Zambezi Diamond ARPLSS</title>
<link rel="stylesheet" href="/assets/css/style.css">
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
           <img src="<?= htmlspecialchars($property['image_path'] ?? '/assets/images/default-property.png') ?>" alt="Property Image">
    </aside>
    <aside>
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

        <div class="mt-4">
            <button 
                class="primary-btn" 
                onclick="window.location.href='edit-property.php?id=<?= (int)$property['id']; ?>'"
            >
            Edit Property
        </button>
        </div>
    </aside>
    </section>

    <!-- Bids Section -->
    <section class="bids">
        <h3>Bids</h3>
        <?php if ($bids): ?>
            <ul>
                <?php foreach ($bids as $bid): ?>
                    <li class="bid-record bold bg-gray sm-none">
                        <p>Name</p>
                        <p>Phone</p>
                        <p>Offer Amount</p>
                        <p>Date</p>
                    </li>
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

</main>

</body>
</html>
<?php
// Clear selection when returning to dashboard
if (isset($_GET['return'])) {
    unset($_SESSION['selectedProperty']);
}
?>
