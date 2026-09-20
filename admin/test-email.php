<?php
/**
 * admin/test-email.php — Sends a test email via SendGrid
 * Called by the settings page "Send Test Email" button (AJAX POST).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json');

// Admin only
if (!Auth::check() || Auth::role() !== 'admin') {
    echo json_encode(['ok' => false, 'message' => 'Unauthorised']);
    exit;
}

$toEmail = Auth::user()['email'];
$toName  = Auth::user()['name'];

$html = mailTemplate("
  <h2 style='color:#1A1A1A;margin-top:0'>✅ Test Email Working!</h2>
  <p>This is a test email from <strong>" . SITE_NAME . "</strong>.</p>
  <p>Your SendGrid integration is configured correctly. All transactional emails
     (order confirmations, tracking links, merchant notifications) will be sent
     from this account.</p>
  <p style='margin-top:20px'>
    <a href='" . BASE_URL . "/admin/settings.php'
       style='display:inline-block;padding:10px 22px;background:#F68B1E;color:#fff;
              border-radius:6px;text-decoration:none;font-weight:700;font-size:.88rem'>
      Back to Settings
    </a>
  </p>
  <p style='color:#999;font-size:.8rem;margin-top:16px'>
    Sent to: {$toEmail}<br>
    Time: " . date('Y-m-d H:i:s') . " WAT
  </p>
", 'Your African Attire email is working!');

$result = sendMail($toEmail, $toName, '✅ Test Email — ' . SITE_NAME, $html);

if ($result === true) {
    echo json_encode([
        'ok'      => true,
        'message' => "✓ Test email sent to {$toEmail}. Check your inbox.",
    ]);
} else {
    echo json_encode([
        'ok'      => false,
        'message' => "✗ Failed: {$result}",
    ]);
}
