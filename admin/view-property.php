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
    SELECT b.*, u.name AS bidder_name, u.email AS bidder_email
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
        <h2><?= htmlspecialchars($property['title']); ?></h2>
        <p><?= nl2br(htmlspecialchars($property['description'])); ?></p>
        <p><strong>Price:</strong> $<?= number_format($property['price'],2); ?></p>
        <p><strong>Rooms:</strong> <?= (int)$property['rooms']; ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($property['location']); ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($property['status']); ?></p>
        <p><strong>Posted:</strong> <?= timeAgo($property['created_at']); ?></p>
        <button onclick="window.location.href='mailto:<?= urlencode($property['agent_email']); ?>'">
            Call Agent
        </button>
        <button onclick="alert('Bid placement flow to be implemented')">Place Bid</button>
    </section>

    <!-- Bids Section -->
    <section class="bids">
        <h3>Bids</h3>
        <?php if ($bids): ?>
            <ul>
                <?php foreach ($bids as $bid): ?>
                    <li>
                        <strong><?= htmlspecialchars($bid['bidder_name']); ?></strong>
                        (<?= htmlspecialchars($bid['bidder_email']); ?>)
                        – $<?= number_format($bid['amount'],2); ?>
                        – <?= timeAgo($bid['created_at']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No bids yet.</p>
        <?php endif; ?>
    </section>

    <!-- Similar Properties -->
    <section class="similar">
        <h3>Similar Properties</h3>
        <div class="similar-grid">
            <?php foreach ($similar as $sim): ?>
                <div class="similar-card">
                    <h4><?= htmlspecialchars($sim['title']); ?></h4>
                    <p>$<?= number_format($sim['price'],2); ?></p>
                    <p><?= htmlspecialchars($sim['location']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
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
