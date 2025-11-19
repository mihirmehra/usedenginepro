<?php
// send_mail.php
// Accepts JSON POST with reservation details, validates, and sends an email using PHPMailer (Gmail SMTP).
// Uses local PHPMailer files.

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

// Load PHPMailer from local files
require __DIR__ . '/PHPMailer/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Configuration (environment-friendly) ---
// The script will read configuration from environment variables when available.
// Recommended environment variables:
// MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_PORT, MAIL_SECURE, MAIL_TO, MAIL_DRY_RUN
$MAIL_HOST = getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
$MAIL_USERNAME = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? 'motortigerusa@gmail.com');
$MAIL_PASSWORD = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? 'qwer tyui nhgf ihsd');
$MAIL_PORT = getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 587); // 587 for STARTTLS, 465 for SMTPS
$MAIL_SECURE = getenv('MAIL_SECURE') ?: ($_ENV['MAIL_SECURE'] ?? 'tls'); // 'tls' or 'ssl'
$MAIL_TO = getenv('MAIL_TO') ?: ($_ENV['MAIL_TO'] ?? 'motortigerusa@gmail.com');
$MAIL_DRY_RUN = getenv('MAIL_DRY_RUN') ?: ($_ENV['MAIL_DRY_RUN'] ?? false); // if truthy, do not actually send mail (useful for testing)

// Read raw JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    // Fall back to form-encoded (in case client posts form data)
    $data = $_POST;
}

// Map expected fields (front-end uses these names)
$pickup = trim($data['pickupLocation'] ?? ($data['pickup_location'] ?? ''));
$dropoff = trim($data['dropoffLocation'] ?? ($data['dropoff_location'] ?? ''));
$pickupDate = trim($data['pickupDate'] ?? ($data['pickup_date'] ?? ''));
$pickupTime = trim($data['pickupTime'] ?? ($data['pickup_time'] ?? ''));
$returnDate = trim($data['returnDate'] ?? ($data['return_date'] ?? ''));
$returnTime = trim($data['returnTime'] ?? ($data['return_time'] ?? ''));
$renterAge = trim($data['renterAge'] ?? ($data['renter_age'] ?? ''));
$corporateID = trim($data['corporateID'] ?? ($data['corporate_id'] ?? ''));
$carType = trim($data['carType'] ?? ($data['car_type'] ?? ''));
$phoneNumber = trim($data['phoneNumber'] ?? ($data['phone_number'] ?? ''));
// Anti-spam honeypot and timestamp
$hp = trim($data['hp_field'] ?? ($data['hp'] ?? ''));
$ts = trim($data['ts'] ?? '');

// Basic validation
$required = [$pickup, $dropoff, $pickupDate, $pickupTime, $returnDate, $returnTime, $renterAge];
foreach ($required as $v) {
    if ($v === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
}

// Honeypot: field should be empty; if filled, likely a bot
if ($hp !== '') {
    error_log('send_mail.php: honeypot triggered, rejecting submission');
    echo json_encode(['success' => false, 'message' => 'Spam detected']);
    exit;
}

// Timestamp check: require at least 3 seconds between form render and submit to avoid super-fast bots
if ($ts !== '') {
    $submittedAt = (int)$ts;
    $now = time();
    if (($now - $submittedAt) < 3) {
        error_log('send_mail.php: submission too fast, rejecting');
        echo json_encode(['success' => false, 'message' => 'Submission too fast']);
        exit;
    }
    // Optional: reject form older than 24 hours
    if (($now - $submittedAt) > 86400) {
        error_log('send_mail.php: submission too old, rejecting');
        echo json_encode(['success' => false, 'message' => 'Submission expired']);
        exit;
    }
}

// Build email body
$bodyHtml = "<h2>New Used Engine Pro Reservation</h2>\n";
$bodyHtml .= "<p>A reservation was submitted via the landing page:</p>\n";
$bodyHtml .= "<ul>\n";
if ($carType !== '') $bodyHtml .= "<li><strong>Car Type:</strong> " . htmlspecialchars($carType) . "</li>\n";
$bodyHtml .= "<li><strong>Pick-up Location:</strong> " . htmlspecialchars($pickup) . "</li>\n";
$bodyHtml .= "<li><strong>Drop-off Location:</strong> " . htmlspecialchars($dropoff) . "</li>\n";
$bodyHtml .= "<li><strong>Pick-up:</strong> " . htmlspecialchars($pickupDate . ' ' . $pickupTime) . "</li>\n";
$bodyHtml .= "<li><strong>Return:</strong> " . htmlspecialchars($returnDate . ' ' . $returnTime) . "</li>\n";
$bodyHtml .= "<li><strong>Renter Age:</strong> " . htmlspecialchars($renterAge) . "</li>\n";
if ($phoneNumber !== '') $bodyHtml .= "<li><strong>Phone Number:</strong> " . htmlspecialchars($phoneNumber) . "</li>\n";
if ($corporateID !== '') $bodyHtml .= "<li><strong>Corporate Account #:</strong> " . htmlspecialchars($corporateID) . "</li>\n";
$bodyHtml .= "</ul>\n";

$bodyText = "New Used Engine Pro Reservation\n";
if ($carType !== '') $bodyText .= "Car Type: {$carType}\n";
$bodyText .= "Pick-up Location: {$pickup}\n";
$bodyText .= "Drop-off Location: {$dropoff}\n";
$bodyText .= "Pick-up: {$pickupDate} {$pickupTime}\n";
$bodyText .= "Return: {$returnDate} {$returnTime}\n";
$bodyText .= "Renter Age: {$renterAge}\n";
if ($phoneNumber !== '') $bodyText .= "Phone Number: {$phoneNumber}\n";
if ($corporateID !== '') $bodyText .= "Corporate Account #: {$corporateID}\n";

$mail = new PHPMailer(true);
try {
    // If running in dry-run mode, skip actual SMTP send and return success for testing
    if ($MAIL_DRY_RUN && ($MAIL_DRY_RUN === '1' || $MAIL_DRY_RUN === 'true')) {
        error_log('send_mail.php: DRY RUN mode enabled - skipping actual send');
        // Log lead to CSV as 'not sent' (but keep for records)
        $logPath = __DIR__ . '/leads.csv';
        $isNew = !file_exists($logPath);
        $fp = fopen($logPath, 'a');
        if ($fp) {
            if ($isNew) {
                fputcsv($fp, ['timestamp','pickup','dropoff','pickup_datetime','return_datetime','renter_age','corporate_id','ip','user_agent','sent']);
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $row = [date('c'), $pickup, $dropoff, $pickupDate . ' ' . $pickupTime, $returnDate . ' ' . $returnTime, $renterAge, $corporateID, $ip, $ua, '0'];
            fputcsv($fp, $row);
            fclose($fp);
        }
        echo json_encode(['success' => true, 'dry_run' => true]);
        exit;
    }

    // Configure and send via SMTP
    $mail->isSMTP();
    $mail->Host = $MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $MAIL_USERNAME;
    $mail->Password = $MAIL_PASSWORD;
    $mail->SMTPSecure = $MAIL_SECURE;
    $mail->Port = (int)$MAIL_PORT;

    $mail->setFrom($MAIL_USERNAME, 'Used Engine Pro Lead');
    $mail->addAddress($MAIL_TO);

    $mail->isHTML(true);
    $mail->Subject = 'Used Engine Pro Reservation - ' . $pickupDate . ' -> ' . $returnDate;
    $mail->Body = $bodyHtml;
    $mail->AltBody = $bodyText;

    $mail->send();

    // Log successful lead to CSV
    $logPath = __DIR__ . '/leads.csv';
    $isNew = !file_exists($logPath);
    $fp = fopen($logPath, 'a');
    if ($fp) {
        if ($isNew) {
            fputcsv($fp, ['timestamp','pickup','dropoff','pickup_datetime','return_datetime','renter_age','corporate_id','ip','user_agent','sent']);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $row = [date('c'), $pickup, $dropoff, $pickupDate . ' ' . $pickupTime, $returnDate . ' ' . $returnTime, $renterAge, $corporateID, $ip, $ua, '1'];
        fputcsv($fp, $row);
        fclose($fp);
    }

    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    // Log error server-side for debugging
    error_log('Mail error: ' . $mail->ErrorInfo . ' Exception: ' . $e->getMessage());
    // Log failed lead to CSV for manual follow-up
    $logPath = __DIR__ . '/leads.csv';
    $isNew = !file_exists($logPath);
    $fp = fopen($logPath, 'a');
    if ($fp) {
        if ($isNew) {
            fputcsv($fp, ['timestamp','pickup','dropoff','pickup_datetime','return_datetime','renter_age','corporate_id','ip','user_agent','sent']);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $row = [date('c'), $pickup, $dropoff, $pickupDate . ' ' . $pickupTime, $returnDate . ' ' . $returnTime, $renterAge, $corporateID, $ip, $ua, '0'];
        fputcsv($fp, $row);
        fclose($fp);
    }
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    exit;
}

?>