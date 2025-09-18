<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Load PHPMailer classes
require '../vendor/autoload.php'; // adjust path if needed

$mail = new PHPMailer(true);

try {
    // 2. SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'maillinkzm@gmail.com';         // your Gmail address
    $mail->Password   = 'niic amoo clhb eymn';  // the app password you generated
    $mail->SMTPSecure = 'tls';                         // or 'ssl' on port 465
    $mail->Port       = 587;

    // 3. Email headers
    $mail->setFrom('maillinkzm@gmail.com', 'Zambezi Test Mailer');
    $mail->addAddress('mwapechipyoka18@gmail.com', 'Test Recipient'); // change to a valid address

    // 4. Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test from Zambezi';
    $mail->Body    = '<h3>Hello!</h3><p>This is a test email from your PHP/Gmail setup.</p>';
    $mail->AltBody = 'Hello! This is a plain-text test email from your PHP/Gmail setup.';

    // 5. Send it
    $mail->send();
    echo '✅ Test email sent successfully. Check the inbox of recipient@example.com';
} catch (Exception $e) {
    echo '❌ Email could not be sent. PHPMailer Error: ', $mail->ErrorInfo;
}
