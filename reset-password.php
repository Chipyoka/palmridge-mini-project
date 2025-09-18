<?php
session_name('USERSESSID');
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize messages in session
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = ['success'=>'', 'error'=>''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash']['error'] = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $tempPassword = bin2hex(random_bytes(4)); // 8 chars
            $hashed = password_hash($tempPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param('si', $hashed, $user['id']);
            $update->execute();

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
                $mail->addAddress($email, $user['name']);

                $mail->isHTML(true);
                $mail->Subject = 'Zambezi ARPLSS Password Reset';
                $mail->Body    = "
                    <p>Hi {$user['name']},</p>
                    <p>Your temporary password is: <strong>{$tempPassword}</strong></p>
                    <p>Please log in using this password and change it immediately for security.</p>
                    <p><a href='http://yourdomain.com/login.php'>Login Here</a></p>
                    <p>– Zambezi ARPLSS</p>
                ";

                $mail->send();
                $_SESSION['flash']['success'] = "A temporary password has been sent to your email.";
            } catch (Exception $e) {
                $_SESSION['flash']['error'] = "Failed to send email. Try again later.";
            }
        } else {
            $_SESSION['flash']['error'] = "No account found with this email.";
        }
    }

    // Redirect to the same page to prevent form resubmission
    header("Location: reset-password.php");
    exit;
}

// Capture flash messages and clear them
$success = $_SESSION['flash']['success'] ?? '';
$error   = $_SESSION['flash']['error'] ?? '';
$_SESSION['flash'] = ['success'=>'', 'error'=>''];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password – Zambezi ARPLSS</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="auth-form">
    <h2 class="text-center">Reset Password</h2>
    <p class="text-center">Please enter your registered email address to receive a temporary password.</p>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3">
        <label>Email:
            <input type="email" name="email" required>
        </label>
        <button type="submit" class="primary-btn w-full mt-2">Send Temporary Password</button>
    </form>

    <p class="hyperlink mt-3 text-center"><a  href="index.php">Back to Home</a></p>
</main>
<script src="/assets/js/main.js"></script>
</body>
</html>
