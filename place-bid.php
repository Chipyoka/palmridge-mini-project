<?php
session_name('USERSESSID');
session_start();
require_once __DIR__ . '/config/config.php';

// --- Guard: normal users only ---
if (!isset($_SESSION['user'])) {
    $_SESSION['flash'] = ['error' => 'You must be logged in to place a bid.'];
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$userId = (int)$user['id'];

// Get property ID
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
if ($propertyId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch property details
$stmt = $conn->prepare("SELECT id, title, price FROM properties WHERE id=? LIMIT 1");
$stmt->bind_param('i', $propertyId);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    echo "Property not found.";
    exit;
}

// Count existing bids for this user on this property
$stmt = $conn->prepare("SELECT COUNT(*) as bid_count FROM bids WHERE property_id=? AND user_id=?");
$stmt->bind_param('ii', $propertyId, $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$bidCount = (int)$result['bid_count'];
$maxBidsReached = ($bidCount >= 2);

// Handle POST (place bid)
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$maxBidsReached) {
    $amount  = (float)$_POST['amount'];
    $message = trim($_POST['message'] ?? '');

    if ($amount <= 0) {
        $error = "Please enter a valid bid amount.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO bids (property_id, user_id, amount, message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('iids', $propertyId, $userId, $amount, $message);
        if ($stmt->execute()) {
            $stmt->close();
            // PRG redirect to avoid resubmission
            $_SESSION['flash'] = ['success' => 'Your bid has been placed successfully!'];
            header("Location: place-bid.php?property_id={$propertyId}");
            exit;
        } else {
            $error = "Failed to place bid. Try again.";
        }
    }
}

// Capture flash messages
$success = $_SESSION['flash']['success'] ?? '';
$error   = $_SESSION['flash']['error'] ?? '';
$_SESSION['flash'] = ['success'=>'', 'error'=>''];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Place Bid – <?= htmlspecialchars($property['title']) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
    <div class="top-bar-user">
        <div class="logo">
            <h2 >Advanced Real-Time Property Listing and Search System</h2>
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
    

              <!-- if user is logged in to display a button to profile page -->

            <!-- If user is logged in, display a button to profile page -->
            <?php if (!isset($_SESSION['user'])): ?>
                <div class="user-actions">
                    <a class="logout" href="admin/login.php">Staff Login</a> |
                    <a class="logout sm-none" href="login.php">Login</a>
                    <a class="primary-btn-sm" href="register.php">Register</a>
                </div>
            <?php else: ?>
                <div class="user-actions">
                    <a class="primary-btn-sm" href="user-profile.php">Visit Profile</a>
                </div>
            <?php endif; ?>

    </div>
</header>
<main class="auth-form">
    <h2 class="text-center">Place a Bid</h2>
    <p class="text-center"><strong>Property:</strong> <?= htmlspecialchars($property['title']); ?></p>
    <p class="text-center"><strong>Current Price:</strong> K<?= number_format($property['price'],2); ?></p>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($maxBidsReached): ?>
        <div class="mt-2 error">
            <hr>
            <p class="text-center">You have already placed the maximum of 2 bids for this property.</p>
            <!-- <p class="text-center"><a class="primary-btn mt-3" href="index.php">Back to Listings</a></p> -->
        </div>
    <?php else: ?>
        <form method="POST" class="mt-3">
            <label>Bid Amount:
                <input type="number" name="amount" step="0.01" min="0" required>
            </label>

            <label>Message (optional):
                <textarea name="message" rows="3"></textarea>
            </label>

            <button type="submit" class="primary-btn w-full mt-2">Place Bid</button>
        </form>
    <?php endif; ?>

    <p class="hyperlink mt-3 text-center">
        <a href="index.php">Back to Listings</a>
    </p>
</main>
<script src="/assets/js/main.js"></script>
</body>
</html>
