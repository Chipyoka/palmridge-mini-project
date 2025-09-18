<?php
// Start sessions for both types
// Determine "back" URL based on session type
if (isset($_COOKIE['ADMINSESSID'])) {
    session_name('ADMINSESSID');
    session_start();
    $returnUrl = 'admin/dashboard.php';
} elseif (isset($_COOKIE['USERSESSID'])) {
    session_name('USERSESSID');
    session_start();
    $returnUrl = 'index.php';
} else {
    $returnUrl = 'index.php'; // fallback if no session exists
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



// Check if logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// Determine "back" URL
$returnUrl = $_GET['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

// Initialize flash messages
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = ['success'=>'', 'error'=>''];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = trim($_POST['current_password']);
    $new     = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if (!$current || !$new || !$confirm) {
        $_SESSION['flash']['error'] = "All fields are required.";
    } elseif ($new !== $confirm) {
        $_SESSION['flash']['error'] = "New password and confirmation do not match.";
    } else {
        // Fetch current hashed password from DB
        $stmt = $conn->prepare("SELECT password, email, name FROM users WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $dbUser = $stmt->get_result()->fetch_assoc();

        if (!$dbUser || !password_verify($current, $dbUser['password'])) {
            $_SESSION['flash']['error'] = "Current password is incorrect.";
        } else {
            // Update password
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param('si', $hashed, $user['id']);
            $update->execute();

            // Send email notification
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = 'tls';
                $mail->Port       = SMTP_PORT;

                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($dbUser['email'], $dbUser['name']);

                $mail->isHTML(true);
                $mail->Subject = 'Your Zambezi ARPLSS Password Was Changed';
                $mail->Body = "
                    <p>Hi {$dbUser['name']},</p>
                    <p>This is a confirmation that your password was successfully changed.</p>
                    <p>If you did not initiate this change, please contact support immediately.</p>
                    <p>– Zambezi ARPLSS</p>
                ";

                $mail->send();
                $_SESSION['flash']['success'] = "Password changed successfully. Confirmation email sent.";
            } catch (Exception $e) {
                $_SESSION['flash']['success'] = "Password changed successfully. Failed to send email: {$mail->ErrorInfo}";
            }
        }
    }

    // PRG: redirect to avoid form resubmission
    header("Location: change-password.php?return_to=" . urlencode($returnUrl));
    exit;
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
<title>Change Password – Zambezi ARPLSS</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="auth-form">
    <h2 class="text-center">Change Password</h2>
    <p class="text-center">Please enter your current password and the new password you wish to set.</p>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3">
        <label>Current Password:
            <input type="password" name="current_password" required>
        </label>

        <label>New Password:
            <input type="password" name="new_password" required>
        </label>

        <label>Confirm New Password:
            <input type="password" name="confirm_password" required>
        </label>

        <button type="submit" class="primary-btn w-full mt-2">Change Password</button>
    </form>

    <p class="hyperlink mt-3 text-center">
        <a href="<?= htmlspecialchars($returnUrl) ?>">Back</a>
    </p>
</main>
<script src="/assets/js/main.js"></script>
</body>
</html>
