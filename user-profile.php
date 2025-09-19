<?php
session_name('USERSESSID');
session_start();
require_once __DIR__ . '/config/config.php';

// --- Guard: Must be logged in as a normal user ---
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// Fetch basic user info
$stmt = $conn->prepare("SELECT name, email, phone, created_at FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch all bids the user has placed
$bidsStmt = $conn->prepare("
    SELECT 
        b.amount,
        b.created_at AS bid_date,
        p.title AS property_name,
        p.location AS property_location,
        p.status AS property_status
    FROM bids b
    JOIN properties p ON b.property_id = p.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$bidsStmt->bind_param('i', $userId);
$bidsStmt->execute();
$bids = $bidsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$bidsStmt->close();

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('Y-m-d', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile – Zambezi ARPLSS</title>
<link rel="stylesheet" href="/odl_mini_projects/zambezi-mini-project/assets/css/style.css">
</head>
<body>
<header class="top-bar">
    <div class="logo">
        <h1>User Profile</h1>
          <button class="badge-primary-outlined sm-none">
            <a  href="index.php">Return Home</a>

        </button>
    </div>
    <div class="user-actions">
        <a class="logout" href="logout.php">Logout</a>
      
    </div>
</header>

<main class="profile-view">
    <!-- User Info Card -->
    <section class="profile-card">
        <div class="top-bar-user">
            <h2><?= htmlspecialchars($user['name']); ?></h2>
        </div>
        <p class="mt-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']); ?></p>
        <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])); ?></p>

        <hr>

        <div class="row-2 mt-3">
            <button 
                class="badge-primary"
                onclick="window.location.href='edit-profile.php'"
            >Edit Profile</button>
            <button class="badge-primary-outlined"><a href="change-password.php">Change Password</a></button>
        </div>
    </section>

    <!-- User Bids Section -->
     <div class="bids-card">
         <section class="bids mt-3">
             <h3>Your Bids</h3>
             <?php if ($bids): ?>
                 <ul>
                     <li class="bid-record bold bg-gray sm-none">
                         <p>Date</p>
                         <p>Property</p>
                         <p>Location</p>
                         <p>Offer</p>
                         <p>Status</p>
                     </li>
                     <?php foreach ($bids as $bid): ?>
                         <li class="bid-record">
                             <p><?= timeAgo($bid['bid_date']); ?></p>
                             <p><?= htmlspecialchars($bid['property_name']); ?></p>
                             <p><?= htmlspecialchars($bid['property_location']); ?></p>
                             <p>K<?= number_format($bid['amount'], 2); ?></p>
                             <p class=" <?= ($bid['property_status'] === 'sold')
                                     ? 'text-danger'
                                     : 'text-warning'; ?>"
                                >
                                <b>
                                    
                                 <?= ($bid['property_status'] === 'sold')
                                     ? 'Closed'
                                     : 'Under Review'; ?>
                                </b>

                             </p>
                         </li>
                     <?php endforeach; ?>
                 </ul>
             <?php else: ?>
                 <p>You have not placed any bids yet.</p>
             <?php endif; ?>
         </section>
     </div>
</main>
</body>
</html>
