<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

$output = [];

$mail = new PHPMailer(true);

try {
    // Collect raw output from PHPMailer debugging
    $mail->SMTPDebug = 3; // Extremely detailed debug
    $mail->Debugoutput = function($str, $level) use (&$output) {
        $output[] = trim($str);
    };

    $mail->isSMTP();
    $mail->Host       = '309aircadets-co-uk.mail.protection.outlook.com';
    $mail->SMTPAuth   = false;
    $mail->Username   = '';
    $mail->Password   = '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 25;
    $mail->Timeout    = 10; // Timeout after 10 sec to prevent hanging

    $mail->setFrom('website@309aircadets.co.uk', '309 Air Cadets Website');
    $mail->addAddress('ajolley@309aircadets.co.uk');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email - SMTP Direct Delivery';
    $mail->Body    = 'This is a test email sent from the debug button.';

    if($mail->send()) {
        $output[] = "\n===============================";
        $output[] = "MAIL SENT SUCCESSFULLY!";
        $output[] = "===============================";
    }
} catch (Exception $e) {
    $output[] = "\n===============================";
    $output[] = "ERROR CAUGHT: " . $e->getMessage();
    $output[] = "MAIL FAILED: " . $mail->ErrorInfo;
    $output[] = "===============================";
}

// Return as plain text for the pre block
header('Content-Type: text/plain');
echo implode("\n", $output);
?>