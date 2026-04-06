<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

header('Content-Type: application/json');

// ==========================================
// CONFIGURATION
// ==========================================

// 1. Email Destinations: Map form_type to target email addresses
// You can change these to different email addresses if needed.
$recipients = [
    'Cadet Application' => 'ajolley@309aircadets.co.uk',
    'Staff Application' => 'ajolley@309aircadets.co.uk',
    'Committee Application' => 'ajolley@309aircadets.co.uk',
    'default' => 'ajolley@309aircadets.co.uk'
];

// 2. Blocklist: Enter specific emails or domains to block submissions from
$blocked_emails = [
    'littlemixwilson035@gmail.com',
    'beckyhillwilson92@gmail.com'
];
$blocked_domains = [
    'baddomain.com',
    'spamdomain.net'
];

// 3. Cloudflare Turnstile Secret Key (Get this from Cloudflare Dashboard)
$turnstile_secret = '0x4AAAAAAC1Xj6Im9FJ-Df6jpPGQjcZdn1A';

// ==========================================
// FORM PROCESSING
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get the raw POST body (since we'll be sending JSON via fetch)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
    exit;
}

// 1. Honeypot check (anti-bot)
if (!empty($data['_honey'])) {
    // Silently reject if honeypot is filled out
    echo json_encode(['success' => true, 'message' => 'Thanks! Your application was submitted.']);
    exit;
}

// 2. Turnstile CAPTCHA Verification
$turnstile_response = $data['cf-turnstile-response'] ?? '';
if (empty($turnstile_response)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please pass the CAPTCHA check.']);
    exit;
}

$verify_url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
$verify_data = [
    'secret' => $turnstile_secret,
    'response' => $turnstile_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($verify_data)
    ]
];
$context  = stream_context_create($options);
$verify_result = file_get_contents($verify_url, false, $context);
$captcha_success = json_decode($verify_result);

if (!$captcha_success || !$captcha_success->success) {
    // For local testing without a real key, you might want to uncomment the line below to bypass this:
    // if ($turnstile_secret !== 'YOUR_TURNSTILE_SECRET_KEY') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'CAPTCHA failed. Please try again.']);
        exit;
    // }
}

// 3. Extract common data to perform blocking checks
$form_type = htmlspecialchars($data['form_type'] ?? 'Unknown Form');
// Find an email address in the submission (parent_email, email)
$submitting_email = '';
if (!empty($data['parent_email'])) {
    $submitting_email = strtolower(trim($data['parent_email']));
} elseif (!empty($data['email'])) {
    $submitting_email = strtolower(trim($data['email']));
}

// 4. Blocklist Validation
$is_blocked = false;
$block_reason = '';

if (!empty($submitting_email)) {
    // Check exact email blocklist
    if (in_array($submitting_email, array_map('strtolower', $blocked_emails))) {
        $is_blocked = true;
        $block_reason = "Email address ($submitting_email) is on the blocklist.";
    }

    // Check domain blocklist
    $domain = substr(strrchr($submitting_email, "@"), 1);
    if (in_array($domain, array_map('strtolower', $blocked_domains))) {
        $is_blocked = true;
        $block_reason = "Domain ($domain) is on the blocklist.";
    }
}

// 5. Construct Email
$to = $recipients[$form_type] ?? $recipients['default'];
$subject = "New Submission: $form_type";

$message = "<html><body style='font-family: Arial, sans-serif;'>";

if ($is_blocked) {
    $subject = "[BLOCKED] Attempted Submission: $form_type";
    $message .= "<div style='background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 12px; margin-bottom: 20px;'>";
    $message .= "<h2 style='color: #991b1b; margin-top: 0;'>Blocked Form Submission Intercepted</h2>";
    $message .= "<p style='color: #7f1d1d; margin-bottom: 0;'>A user attempted to submit a form, but they were flagged by your blocklist.</p>";
    $message .= "<p style='color: #7f1d1d; margin-bottom: 0;'><strong>Reason:</strong> $block_reason</p>";
    $message .= "</div>";
    $message .= "<h3 style='color: #334155;'>Original Submitted Data:</h3>";
} else {
    $message .= "<h2 style='color: #4D45AF;'>New $form_type Submission</h2>";
}

$message .= "<table border='0' cellpadding='8' cellspacing='0' style='width: 100%; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 8px;'>";

// Exclude internal keys
$exclude_keys = ['_honey', 'cf-turnstile-response', 'form_type'];

foreach ($data as $key => $value) {
    if (!in_array($key, $exclude_keys)) {
        // Clean up the key label
        $label = ucwords(str_replace(['_', '-'], ' ', $key));
        $val = htmlspecialchars($value);
        $message .= "<tr>";
        $message .= "<td style='border-bottom: 1px solid #e2e8f0; font-weight: bold; width: 35%; color: #334155; background-color: #f8fafc;'>$label</td>";
        $message .= "<td style='border-bottom: 1px solid #e2e8f0; color: #0f172a;'>$val</td>";
        $message .= "</tr>";
    }
}

$message .= "</table>";
$message .= "<p style='font-size: 12px; color: #64748b; margin-top: 20px;'>Submitted on: " . date('Y-m-d H:i:s') . "</p>";
$message .= "</body></html>";

// 6. Send the Email using PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = '309aircadets-co-uk.mail.protection.outlook.com'; // This routes directly to your Microsoft 365 tenant
    $mail->SMTPAuth   = false;                    // No password needed for direct delivery
    $mail->Username   = '';                       // Leave blank
    $mail->Password   = '';                       // Leave blank
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Still encrypt the text being sent
    $mail->Port       = 25;                       // Direct send uses port 25 (the standard mail port)

    // Sender details - Make sure this is a valid email address inside your 365 organization
    $mail->setFrom('website@309aircadets.co.uk', '309 Air Cadets Website');
    
    // Recipients
    $mail->addAddress($to);

    // Reply-to (so if you hit Reply, it replies to the person who filled out the form)
    if (!empty($submitting_email)) {
        $mail->addReplyTo($submitting_email);
    }

    // Content
    $mail->isHTML(true);                          // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $message;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Thanks! Your application was submitted successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    // Logging the PHPMailer error here for debugging. You can remove $mail->ErrorInfo in production
    echo json_encode(['success' => false, 'message' => 'There was a problem sending the email. Server Error: ' . $mail->ErrorInfo]);
}
?>