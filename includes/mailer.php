<?php
/**
 * includes/mailer.php — SendGrid email helper
 *
 * Usage:
 *   sendMail('to@email.com', 'Recipient Name', 'Subject', '<p>HTML body</p>');
 *
 * Returns true on success, string error message on failure.
 * Reads sendgrid_api_key, sendgrid_from_email, sendgrid_from_name from settings.
 */

/**
 * Send an email via the SendGrid v3 Mail Send API.
 *
 * @param string $toEmail    Recipient email address
 * @param string $toName     Recipient display name
 * @param string $subject    Email subject line
 * @param string $htmlBody   Full HTML body (plain-text version auto-stripped)
 * @return true|string       true on success, error string on failure
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody) {
    $apiKey   = getSetting('sendgrid_api_key',    '');
    $fromEmail= getSetting('sendgrid_from_email', '');
    $fromName = getSetting('sendgrid_from_name',  SITE_NAME);

    if (!$apiKey) {
        return 'SendGrid API key is not configured. Go to Admin → Settings to add it.';
    }
    if (!$fromEmail) {
        return 'SendGrid sender email is not configured. Go to Admin → Settings to add it.';
    }
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return "Invalid recipient email address: $toEmail";
    }

    // Strip tags for plain-text fallback
    $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $htmlBody));
    $textBody = preg_replace("/\n{3,}/", "\n\n", trim($textBody));

    $payload = json_encode([
        'personalizations' => [[
            'to' => [['email' => $toEmail, 'name' => $toName]],
        ]],
        'from'    => ['email' => $fromEmail, 'name' => $fromName],
        'subject' => $subject,
        'content' => [
            ['type' => 'text/plain', 'value' => $textBody],
            ['type' => 'text/html',  'value' => $htmlBody],
        ],
    ]);

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return "cURL error: $curlErr";
    }

    // SendGrid returns 202 on success, no body
    if ($httpCode === 202) {
        return true;
    }

    // Parse error body
    $body = json_decode($response, true);
    $msg  = $body['errors'][0]['message'] ?? "HTTP $httpCode";
    return "SendGrid error: $msg";
}

/**
 * Build a branded HTML email wrapper around content.
 *
 * @param string $content  Inner HTML content (headings, paragraphs, buttons)
 * @param string $preview  Optional preview text (shown in inbox before opening)
 */
function mailTemplate(string $content, string $preview = ''): string {
    $siteName = SITE_NAME;
    $siteUrl  = BASE_URL;
    $year     = date('Y');

    return "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>{$siteName}</title>
  " . ($preview ? "<div style='display:none;max-height:0;overflow:hidden;font-size:1px;color:#fff'>{$preview}</div>" : '') . "
</head>
<body style='margin:0;padding:0;background:#F2F2F2;font-family:system-ui,-apple-system,sans-serif'>
  <div style='max-width:600px;margin:24px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1)'>

    <!-- Header -->
    <div style='background:linear-gradient(135deg,#F68B1E,#D4720A);padding:22px 28px;text-align:center'>
      <a href='{$siteUrl}' style='text-decoration:none'>
        <span style='font-family:Georgia,serif;font-size:1.4rem;font-weight:800;color:#fff;letter-spacing:-.01em'>{$siteName}</span>
      </a>
      <div style='font-size:.7rem;color:rgba(255,255,255,.75);margin-top:3px;text-transform:uppercase;letter-spacing:.1em'>Africa's #1 Fashion Marketplace</div>
    </div>

    <!-- Body -->
    <div style='padding:28px 32px;color:#333;line-height:1.65;font-size:.9rem'>
      {$content}
    </div>

    <!-- Footer -->
    <div style='background:#F2F2F2;border-top:1px solid #E0E0E0;padding:16px 28px;text-align:center;font-size:.73rem;color:#999'>
      <p style='margin:0 0 5px'>© {$year} {$siteName}. All rights reserved.</p>
      <p style='margin:0'>
        <a href='{$siteUrl}' style='color:#F68B1E;text-decoration:none'>Visit Store</a>
        &nbsp;·&nbsp;
        <a href='{$siteUrl}/customer/orders.php' style='color:#F68B1E;text-decoration:none'>My Orders</a>
        &nbsp;·&nbsp;
        <a href='mailto:" . getSetting('site_email','hello@africanattire.com') . "' style='color:#F68B1E;text-decoration:none'>Contact Us</a>
      </p>
    </div>
  </div>
</body>
</html>";
}

/**
 * Send an order confirmation email to the customer.
 *
 * Works for both registered users and guests.
 * Called from pay-callback.php after payment is verified and order created.
 *
 * @param array  $order      Full orders row from DB (including guest_* fields)
 * @param array  $items      Array of order_items rows (with shop_name)
 * @param string $toEmail    Recipient email
 * @param string $toName     Recipient name
 * @param string|null $trackUrl  Full tracking URL (guests only, null for members)
 * @return true|string
 */
function sendOrderConfirmationEmail(
    array  $order,
    array  $items,
    string $toEmail,
    string $toName,
    string $trackUrl = null
) {
    $orderNo  = htmlspecialchars($order['order_number']);
    $total    = money((float)$order['total_amount']);
    $address  = nl2br(htmlspecialchars($order['delivery_address']));
    $date     = date('F j, Y \a\t g:i A', strtotime($order['created_at']));
    $ref      = htmlspecialchars($order['payment_reference'] ?? '—');
    $siteUrl  = BASE_URL;

    // ── Items table ───────────────────────────────────────────
    $itemRows = '';
    foreach ($items as $it) {
        $name  = htmlspecialchars($it['product_name']);
        $shop  = htmlspecialchars($it['shop_name'] ?? '');
        $qty   = (int)$it['quantity'];
        $price = money((float)$it['price']);
        $line  = money((float)$it['price'] * $qty);
        $size  = !empty($it['size']) ? ' <span style="color:#999;font-size:.82em">(' . htmlspecialchars($it['size']) . ')</span>' : '';

        $itemRows .= "
        <tr>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;vertical-align:top'>
            <div style='font-weight:600;color:#1A1A1A;font-size:.88rem'>{$name}{$size}</div>
            <div style='font-size:.78rem;color:#999;margin-top:2px'>{$shop}</div>
          </td>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;text-align:center;
                     font-size:.88rem;color:#555;white-space:nowrap'>{$qty}</td>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;text-align:right;
                     font-size:.88rem;color:#555;white-space:nowrap'>{$price}</td>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;text-align:right;
                     font-weight:700;color:#1A1A1A;white-space:nowrap'>{$line}</td>
        </tr>";
    }

    // ── Track/view button ─────────────────────────────────────
    if ($trackUrl) {
        $actionBtn = "
        <a href='{$trackUrl}'
           style='display:inline-block;padding:12px 28px;background:#F68B1E;color:#fff;
                  border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none;
                  margin-top:6px'>
          📦 Track Your Order
        </a>";
    } else {
        $actionBtn = "
        <a href='{$siteUrl}/customer/orders.php'
           style='display:inline-block;padding:12px 28px;background:#1565C0;color:#fff;
                  border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none;
                  margin-top:6px'>
          📦 View My Orders
        </a>";
    }

    // ── Email body ────────────────────────────────────────────
    $content = "
    <!-- Greeting -->
    <h2 style='margin:0 0 6px;color:#1A1A1A;font-size:1.2rem'>
      ✅ Order Confirmed — Thank You!
    </h2>
    <p style='margin:0 0 20px;color:#555'>
      Hi <strong>" . htmlspecialchars($toName) . "</strong>, your payment was successful and your
      order has been placed. We'll start processing it right away.
    </p>

    <!-- Order summary box -->
    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:16px 18px;margin-bottom:20px'>
      <div style='display:grid;grid-template-columns:1fr 1fr;gap:10px'>
        <div>
          <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                      color:#999;margin-bottom:3px'>Order Number</div>
          <div style='font-weight:800;color:#1A1A1A;font-size:.95rem'>{$orderNo}</div>
        </div>
        <div>
          <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                      color:#999;margin-bottom:3px'>Date Placed</div>
          <div style='font-weight:600;color:#1A1A1A;font-size:.85rem'>{$date}</div>
        </div>
        <div>
          <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                      color:#999;margin-bottom:3px'>Payment</div>
          <div style='font-weight:600;color:#2E7D32;font-size:.85rem'>✓ Paid via Paystack</div>
        </div>
        <div>
          <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                      color:#999;margin-bottom:3px'>Reference</div>
          <div style='font-weight:600;color:#555;font-size:.8rem'>{$ref}</div>
        </div>
      </div>
    </div>

    <!-- Items table -->
    <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                color:#999;margin-bottom:8px'>Items Ordered</div>
    <table style='width:100%;border-collapse:collapse;border:1px solid #E8E8E8;
                  border-radius:8px;overflow:hidden;margin-bottom:20px'>
      <thead>
        <tr style='background:#F0F0F0'>
          <th style='padding:9px 12px;text-align:left;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Product</th>
          <th style='padding:9px 12px;text-align:center;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Qty</th>
          <th style='padding:9px 12px;text-align:right;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Unit</th>
          <th style='padding:9px 12px;text-align:right;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Total</th>
        </tr>
      </thead>
      <tbody>{$itemRows}</tbody>
      <tfoot>
        <tr style='background:#FAFAFA'>
          <td colspan='3' style='padding:11px 12px;text-align:right;font-weight:700;
                                  color:#555;font-size:.88rem'>Delivery</td>
          <td style='padding:11px 12px;text-align:right;font-weight:700;
                     color:#2E7D32;font-size:.88rem'>FREE</td>
        </tr>
        <tr style='background:#FFF8F0'>
          <td colspan='3' style='padding:12px;text-align:right;font-weight:800;
                                  color:#1A1A1A;font-size:.95rem;border-top:2px solid #F68B1E'>
            Order Total
          </td>
          <td style='padding:12px;text-align:right;font-weight:800;
                     color:#F68B1E;font-size:1.05rem;border-top:2px solid #F68B1E'>
            {$total}
          </td>
        </tr>
      </tfoot>
    </table>

    <!-- Delivery address -->
    <div style='background:#F0F4FF;border:1px solid #C5D8F5;border-radius:8px;
                padding:14px 16px;margin-bottom:20px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#1565C0;font-weight:700;margin-bottom:6px'>📍 Delivery Address</div>
      <div style='font-size:.86rem;color:#333;line-height:1.6'>{$address}</div>
    </div>

    <!-- CTA -->
    <div style='text-align:center;margin-bottom:16px'>
      {$actionBtn}
    </div>

    <!-- Support note -->
    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Questions about your order? Reply to this email or contact us at
      <a href='mailto:" . getSetting('site_email', 'hello@africanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email', 'hello@africanattire.com') . "
      </a>
      with your order number.
    </p>";

    $html = mailTemplate($content, "Your order {$orderNo} is confirmed — " . SITE_NAME);

    return sendMail(
        $toEmail,
        $toName,
        "✅ Order {$orderNo} Confirmed — " . SITE_NAME,
        $html
    );
}

/**
 * Send a welcome email to a newly registered customer.
 */
function sendWelcomeEmail(string $toEmail, string $toName): void {
    $name    = htmlspecialchars($toName);
    $siteUrl = BASE_URL;

    $content = "
    <h2 style='margin:0 0 8px;color:#1A1A1A;font-size:1.2rem'>
      🎉 Welcome to African Attire, {$name}!
    </h2>
    <p style='margin:0 0 16px;color:#555'>
      Your account is ready. You're now part of Africa's #1 fashion marketplace —
      where authentic Ankara, Kente, Agbada, Kaftan &amp; Adire meet you at your door.
    </p>

    <!-- What you can do -->
    <div style='background:#FFF8F0;border:1px solid #FFE0B2;border-radius:8px;
                padding:16px 18px;margin-bottom:20px'>
      <div style='font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#F68B1E;font-weight:700;margin-bottom:10px'>What you can do</div>
      <table style='width:100%;border-collapse:collapse'>
        <tr>
          <td style='padding:6px 0;vertical-align:top;width:28px;font-size:1rem'>🛍</td>
          <td style='padding:6px 0;font-size:.88rem;color:#333'>
            <strong>Browse &amp; Shop</strong> — thousands of authentic African fashion pieces
          </td>
        </tr>
        <tr>
          <td style='padding:6px 0;vertical-align:top;font-size:1rem'>♡</td>
          <td style='padding:6px 0;font-size:.88rem;color:#333'>
            <strong>Save to Wishlist</strong> — bookmark items you love
          </td>
        </tr>
        <tr>
          <td style='padding:6px 0;vertical-align:top;font-size:1rem'>📦</td>
          <td style='padding:6px 0;font-size:.88rem;color:#333'>
            <strong>Track Orders</strong> — real-time status on every purchase
          </td>
        </tr>
        <tr>
          <td style='padding:6px 0;vertical-align:top;font-size:1rem'>✨</td>
          <td style='padding:6px 0;font-size:.88rem;color:#333'>
            <strong>Follow Designers</strong> — shop directly from verified merchants
          </td>
        </tr>
      </table>
    </div>

    <!-- CTA -->
    <div style='text-align:center;margin-bottom:20px'>
      <a href='{$siteUrl}/customer/shop.php'
         style='display:inline-block;padding:13px 32px;background:#F68B1E;color:#fff;
                border-radius:6px;font-weight:700;font-size:.95rem;text-decoration:none;
                box-shadow:0 4px 12px rgba(246,139,30,.35)'>
        Start Shopping →
      </a>
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Questions? We're here at
      <a href='mailto:" . getSetting('site_email','hello@africanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email','hello@africanattire.com') . "
      </a>
    </p>";

    $html = mailTemplate($content, "Welcome to African Attire, {$name}! Your account is ready.");
    sendMail($toEmail, $toName, "🎉 Welcome to African Attire, {$name}!", $html);
}

/**
 * Send the merchant an email notifying them of a new order for their products.
 *
 * @param array  $order       Full orders row
 * @param array  $items       Items belonging to THIS merchant only
 * @param string $merchantEmail
 * @param string $merchantName
 * @param string $customerName
 */
function sendMerchantOrderEmail(
    array  $order,
    array  $items,
    string $merchantEmail,
    string $merchantName,
    string $customerName
): void {
    $orderNo  = htmlspecialchars($order['order_number']);
    $date     = date('F j, Y \a\t g:i A', strtotime($order['created_at']));

    // Show only city & region to merchant — never the full delivery address
    $rawLines   = array_values(array_filter(
        array_map('trim', preg_split('/\r?\n|,/', $order['delivery_address'] ?? ''))
    ));
    $total      = count($rawLines);
    $city       = $total >= 2 ? $rawLines[$total - 2] : ($rawLines[0] ?? '');
    $region     = $total >= 1 ? $rawLines[$total - 1] : '';
    $destination = htmlspecialchars(implode(', ', array_filter([$city, $region])));

    $siteUrl  = BASE_URL;
    $mName    = htmlspecialchars($merchantName);
    $cName    = htmlspecialchars($customerName);

    // Build items rows for THIS merchant only
    $itemRows = '';
    $subtotal = 0.0;
    foreach ($items as $it) {
        $name      = htmlspecialchars($it['product_name']);
        $qty       = (int)$it['quantity'];
        $price     = money((float)$it['price']);
        $line      = money((float)$it['price'] * $qty);
        $subtotal += (float)$it['price'] * $qty;
        $size      = !empty($it['size'])
            ? ' <span style="color:#999;font-size:.82em">(' . htmlspecialchars($it['size']) . ')</span>'
            : '';
        $itemRows .= "
        <tr>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;
                     font-weight:600;font-size:.88rem;color:#1A1A1A'>{$name}{$size}</td>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;
                     text-align:center;font-size:.88rem;color:#555'>{$qty}</td>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;
                     text-align:right;font-size:.88rem;color:#555'>{$price}</td>
          <td style='padding:10px 12px;border-bottom:1px solid #F0F0F0;
                     text-align:right;font-weight:700;color:#1A1A1A'>{$line}</td>
        </tr>";
    }

    $subtotalFmt = money($subtotal);

    $content = "
    <h2 style='margin:0 0 8px;color:#1A1A1A;font-size:1.2rem'>
      🛍 New Order Received — Action Required
    </h2>
    <p style='margin:0 0 18px;color:#555'>
      Hi <strong>{$mName}</strong>, a customer just placed an order for your products.
      Please begin processing as soon as possible.
    </p>

    <!-- Alert banner -->
    <div style='background:#E8F5E9;border:1px solid #A5D6A7;border-radius:8px;
                padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px'>
      <span style='font-size:1.4rem'>📦</span>
      <div>
        <div style='font-weight:700;color:#2E7D32;font-size:.9rem'>Order #{$orderNo}</div>
        <div style='font-size:.8rem;color:#388E3C'>Placed on {$date}</div>
      </div>
    </div>

    <!-- Customer info — city/region only, full address is hidden from merchants -->
    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:14px 16px;margin-bottom:18px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#999;font-weight:700;margin-bottom:8px'>Customer Details</div>
      <div style='font-size:.88rem;color:#333'>
        <div style='margin-bottom:4px'>👤 <strong>{$cName}</strong></div>
        <div>📍 Delivery destination: <strong>{$destination}</strong></div>
        <div style='font-size:.76rem;color:#aaa;margin-top:4px'>
          Full delivery address is handled by your logistics partner.
        </div>
      </div>
    </div>

    <!-- Your items -->
    <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                color:#999;margin-bottom:8px;font-weight:700'>Your Items in This Order</div>
    <table style='width:100%;border-collapse:collapse;border:1px solid #E8E8E8;
                  border-radius:8px;overflow:hidden;margin-bottom:18px'>
      <thead>
        <tr style='background:#F0F0F0'>
          <th style='padding:9px 12px;text-align:left;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Product</th>
          <th style='padding:9px 12px;text-align:center;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Qty</th>
          <th style='padding:9px 12px;text-align:right;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Unit Price</th>
          <th style='padding:9px 12px;text-align:right;font-size:.72rem;text-transform:uppercase;
                     letter-spacing:.06em;color:#777;font-weight:700'>Total</th>
        </tr>
      </thead>
      <tbody>{$itemRows}</tbody>
      <tfoot>
        <tr style='background:#FFF8F0'>
          <td colspan='3' style='padding:11px 12px;text-align:right;font-weight:800;
                                  color:#1A1A1A;font-size:.92rem;border-top:2px solid #F68B1E'>
            Your Subtotal
          </td>
          <td style='padding:11px 12px;text-align:right;font-weight:800;
                     color:#F68B1E;font-size:1rem;border-top:2px solid #F68B1E'>
            {$subtotalFmt}
          </td>
        </tr>
      </tfoot>
    </table>

    <!-- Action steps -->
    <div style='background:#FFF8F0;border:1px solid #FFE0B2;border-radius:8px;
                padding:14px 16px;margin-bottom:20px'>
      <div style='font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#F68B1E;font-weight:700;margin-bottom:8px'>Next Steps</div>
      <ol style='margin:0;padding-left:18px;font-size:.86rem;color:#333;line-height:1.9'>
        <li><strong>Prepare</strong> the items listed above for dispatch</li>
        <li><strong>Package</strong> them securely with your branding</li>
        <li><strong>Ship</strong> via your assigned logistics partner — they hold the full delivery address</li>
        <li><strong>Update</strong> the order status in your Merchant Hub</li>
      </ol>
    </div>

    <!-- CTA -->
    <div style='text-align:center;margin-bottom:16px'>
      <a href='{$siteUrl}/merchant/orders.php'
         style='display:inline-block;padding:12px 28px;background:#1565C0;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        View in Merchant Hub →
      </a>
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Questions? Contact platform support at
      <a href='mailto:" . getSetting('site_email','hello@africanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email','hello@africanattire.com') . "
      </a>
    </p>";

    $html = mailTemplate($content, "New order #{$orderNo} — please start processing");
    sendMail(
        $merchantEmail,
        $merchantName,
        "🛍 New Order #{$orderNo} — Please Start Processing",
        $html
    );
}

/**
 * Send a merchant onboarding email after shop registration.
 * Explains how to set up their shop, upload products, etc.
 */
function sendMerchantWelcomeEmail(string $toEmail, string $toName, string $shopName): void {
    $name     = htmlspecialchars($toName);
    $shop     = htmlspecialchars($shopName);
    $siteUrl  = BASE_URL;
    $commRate = getSetting('platform_commission', '20');

    $content = "
    <h2 style='margin:0 0 6px;color:#1A1A1A;font-size:1.2rem'>
      🎉 Shop Application Received — Thank You, {$name}!
    </h2>
    <p style='margin:0 0 18px;color:#555;line-height:1.65'>
      We've received your application for <strong>\"{$shop}\"</strong> and our team
      will review it within <strong>24–48 hours</strong>. You'll get an email the
      moment it's approved and you can start selling.
    </p>
    <p style='margin:0 0 20px;color:#555;line-height:1.65'>
      While you wait, here's everything you need to know to hit the ground running
      the moment your shop goes live.
    </p>

    <!-- Step by step setup guide -->
    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:18px 20px;margin-bottom:20px'>
      <div style='font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;
                  color:#999;font-weight:700;margin-bottom:14px'>
        How to set up your shop (once approved)
      </div>

      <div style='display:flex;flex-direction:column;gap:14px'>

        <div style='display:flex;gap:12px;align-items:flex-start'>
          <div style='width:28px;height:28px;border-radius:50%;background:#F68B1E;color:#fff;
                      display:flex;align-items:center;justify-content:center;
                      font-size:.78rem;font-weight:800;flex-shrink:0'>1</div>
          <div>
            <div style='font-weight:700;font-size:.9rem;color:#1A1A1A;margin-bottom:3px'>
              Complete Your Shop Profile
            </div>
            <div style='font-size:.82rem;color:#666;line-height:1.5'>
              Add your shop banner, logo, description and city. A complete profile
              builds trust and gets you more sales. Go to
              <strong>Merchant Hub → Shop Setup</strong>.
            </div>
          </div>
        </div>

        <div style='display:flex;gap:12px;align-items:flex-start'>
          <div style='width:28px;height:28px;border-radius:50%;background:#1565C0;color:#fff;
                      display:flex;align-items:center;justify-content:center;
                      font-size:.78rem;font-weight:800;flex-shrink:0'>2</div>
          <div>
            <div style='font-weight:700;font-size:.9rem;color:#1A1A1A;margin-bottom:3px'>
              Upload Your First Products
            </div>
            <div style='font-size:.82rem;color:#666;line-height:1.5'>
              Go to <strong>Merchant Hub → Products → Add Product</strong>.
              Use clear photos (white or plain background works best), accurate
              descriptions, stock count, and competitive pricing.
              Each product is reviewed before going live.
            </div>
          </div>
        </div>

        <div style='display:flex;gap:12px;align-items:flex-start'>
          <div style='width:28px;height:28px;border-radius:50%;background:#2E7D32;color:#fff;
                      display:flex;align-items:center;justify-content:center;
                      font-size:.78rem;font-weight:800;flex-shrink:0'>3</div>
          <div>
            <div style='font-weight:700;font-size:.9rem;color:#1A1A1A;margin-bottom:3px'>
              Manage Orders from Your Dashboard
            </div>
            <div style='font-size:.82rem;color:#666;line-height:1.5'>
              When a customer buys from you, you'll receive an email notification.
              Go to <strong>Merchant Hub → Orders</strong> to view and update
              order status (Processing → Shipped → Delivered).
            </div>
          </div>
        </div>

        <div style='display:flex;gap:12px;align-items:flex-start'>
          <div style='width:28px;height:28px;border-radius:50%;background:#856404;color:#fff;
                      display:flex;align-items:center;justify-content:center;
                      font-size:.78rem;font-weight:800;flex-shrink:0'>4</div>
          <div>
            <div style='font-weight:700;font-size:.9rem;color:#1A1A1A;margin-bottom:3px'>
              Track Revenue &amp; Request Payouts
            </div>
            <div style='font-size:.82rem;color:#666;line-height:1.5'>
              Monitor sales in <strong>Analytics</strong>. African Attire takes a
              <strong>{$commRate}% commission</strong> on each sale.
              Your remaining earnings are paid to your registered bank account
              — request a payout any time from <strong>Merchant Hub → Payouts</strong>.
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Tips -->
    <div style='background:#E3F2FD;border:1px solid #90CAF9;border-radius:8px;
                padding:14px 18px;margin-bottom:20px'>
      <div style='font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;
                  color:#1565C0;font-weight:700;margin-bottom:10px'>
        💡 Tips for Success
      </div>
      <ul style='margin:0;padding-left:18px;font-size:.84rem;color:#333;line-height:1.9'>
        <li>Use <strong>high-quality, well-lit photos</strong> — multiple angles sell better</li>
        <li>Write <strong>detailed descriptions</strong> including fabric, fit and care instructions</li>
        <li>Keep your <strong>inventory accurate</strong> to avoid overselling</li>
        <li>Respond to orders quickly — fast processing earns 5-star reviews</li>
        <li>Share your shop link on social media to drive extra traffic</li>
      </ul>
    </div>

    <!-- CTA -->
    <div style='text-align:center;margin-bottom:20px'>
      <a href='{$siteUrl}/merchant/'
         style='display:inline-block;padding:12px 28px;background:#F68B1E;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        Go to Merchant Hub →
      </a>
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Questions? We're here to help at
      <a href='mailto:" . getSetting('site_email','hello@africanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email','hello@africanattire.com') . "
      </a>
    </p>";

    $html = mailTemplate($content, "Your African Attire merchant application is being reviewed");
    sendMail($toEmail, $toName, "🏪 Your Shop \"{$shopName}\" Application — African Attire", $html);
}

/**
 * Send a merchant approval email when admin approves their shop.
 */
function sendMerchantApprovalEmail(string $toEmail, string $toName, string $shopName): void {
    $name    = htmlspecialchars($toName);
    $shop    = htmlspecialchars($shopName);
    $siteUrl = BASE_URL;

    $content = "
    <h2 style='margin:0 0 8px;color:#1A1A1A;font-size:1.2rem'>
      🎉 Congratulations — Your Shop is Approved!
    </h2>
    <p style='margin:0 0 18px;color:#555;line-height:1.65'>
      Hi <strong>{$name}</strong>, great news! Your shop
      <strong>\"{$shop}\"</strong> has been reviewed and approved by our team.
      You're now a verified merchant on African Attire!
    </p>

    <!-- Approval status box -->
    <div style='background:#E8F5E9;border:1px solid #A5D6A7;border-radius:8px;
                padding:16px 18px;margin-bottom:20px;display:flex;align-items:center;gap:14px'>
      <span style='font-size:2.5rem'>✅</span>
      <div>
        <div style='font-weight:800;font-size:1rem;color:#2E7D32;margin-bottom:3px'>
          Shop Approved &amp; Live
        </div>
        <div style='font-size:.84rem;color:#388E3C'>
          \"{$shop}\" is now visible to thousands of customers on African Attire.
        </div>
      </div>
    </div>

    <!-- What to do now -->
    <div style='font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;
                color:#999;font-weight:700;margin-bottom:12px'>
      What to do next
    </div>
    <div style='display:flex;flex-direction:column;gap:10px;margin-bottom:22px'>
      <div style='display:flex;align-items:center;gap:10px;padding:11px 14px;
                  background:#F8F8F8;border-radius:8px;font-size:.86rem;color:#333'>
        <span style='font-size:1.1rem'>📸</span>
        <span><strong>Add your first products</strong> — Go to Merchant Hub → Products → Add Product</span>
      </div>
      <div style='display:flex;align-items:center;gap:10px;padding:11px 14px;
                  background:#F8F8F8;border-radius:8px;font-size:.86rem;color:#333'>
        <span style='font-size:1.1rem'>🏪</span>
        <span><strong>Complete your shop profile</strong> — Add banner, description and social links</span>
      </div>
      <div style='display:flex;align-items:center;gap:10px;padding:11px 14px;
                  background:#F8F8F8;border-radius:8px;font-size:.86rem;color:#333'>
        <span style='font-size:1.1rem'>📢</span>
        <span><strong>Spread the word</strong> — Share your shop link with customers and on social media</span>
      </div>
    </div>

    <!-- CTA buttons -->
    <div style='text-align:center;margin-bottom:20px;display:flex;gap:10px;
                justify-content:center;flex-wrap:wrap'>
      <a href='{$siteUrl}/merchant/'
         style='display:inline-block;padding:12px 24px;background:#F68B1E;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        Go to Merchant Hub →
      </a>
      <a href='{$siteUrl}/merchant/products.php'
         style='display:inline-block;padding:12px 24px;background:#1565C0;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        Upload Products →
      </a>
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Welcome to the African Attire merchant community! Questions?
      <a href='mailto:" . getSetting('site_email','hello@africanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        Contact our team
      </a>
    </p>";

    $html = mailTemplate($content, "Your shop \"{$shopName}\" is approved and live on African Attire!");
    sendMail(
        $toEmail, $toName,
        "✅ Your Shop \"{$shopName}\" is Approved — Start Selling on African Attire!",
        $html
    );
}

/**
 * Send order status update email to the customer.
 *
 * @param array       $order      Full orders row (with cust_name, cust_email)
 * @param array       $items      Order items for this order
 * @param string      $newStatus  The new status that was just set
 * @param string      $shopName   The merchant's shop name
 * @param string|null $trackUrl   Tracking URL (for guests); null for members
 */
function sendOrderStatusEmail(
    array  $order,
    array  $items,
    string $newStatus,
    string $shopName,
    string $trackUrl = null
): void {
    $toEmail  = $order['cust_email'] ?? '';
    $toName   = $order['cust_name']  ?? 'Valued Customer';

    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return;

    $orderNo  = htmlspecialchars($order['order_number']);
    $sName    = htmlspecialchars($shopName);
    $siteUrl  = BASE_URL;

    // Status-specific messaging
    $statusMap = [
        'processing' => [
            'icon'    => '🔄',
            'title'   => 'Your Order is Being Processed',
            'color'   => '#1565C0',
            'bg'      => '#E3F2FD',
            'message' => "Great news! <strong>{$sName}</strong> has confirmed your order and is now preparing your items. You'll hear from us again when your order ships.",
            'cta'     => 'Track Your Order',
        ],
        'shipped' => [
            'icon'    => '🚚',
            'title'   => 'Your Order Has Shipped!',
            'color'   => '#2E7D32',
            'bg'      => '#E8F5E9',
            'message' => "Your order is on its way! <strong>{$sName}</strong> has dispatched your items. Please allow 2–5 business days for delivery depending on your location.",
            'cta'     => 'Track Your Order',
        ],
        'delivered' => [
            'icon'    => '✅',
            'title'   => 'Order Delivered — Enjoy!',
            'color'   => '#2E7D32',
            'bg'      => '#E8F5E9',
            'message' => "Your order has been marked as delivered. We hope you love your African Attire purchase! If you have any issues, please contact us within 7 days.",
            'cta'     => 'View Order',
        ],
        'cancelled' => [
            'icon'    => '❌',
            'title'   => 'Order Cancelled',
            'color'   => '#C62828',
            'bg'      => '#FFEBEE',
            'message' => "Your order has been cancelled by the merchant. If a payment was made and you believe this is an error, please contact our support team immediately.",
            'cta'     => 'Contact Support',
        ],
    ];

    $info = $statusMap[$newStatus] ?? [
        'icon'    => '📦',
        'title'   => 'Order Update: ' . ucfirst($newStatus),
        'color'   => '#555',
        'bg'      => '#F8F8F8',
        'message' => "Your order status has been updated to <strong>" . ucfirst($newStatus) . "</strong>.",
        'cta'     => 'Track Your Order',
    ];

    // Items table
    $itemRows = '';
    $total    = 0.0;
    foreach ($items as $it) {
        $name   = htmlspecialchars($it['product_name']);
        $qty    = (int)$it['quantity'];
        $price  = money((float)$it['price']);
        $line   = money((float)$it['price'] * $qty);
        $total += (float)$it['price'] * $qty;
        $size   = !empty($it['size'])
            ? '<span style="color:#999;font-size:.82em"> ('.htmlspecialchars($it['size']).')</span>' : '';
        $itemRows .= "
        <tr>
          <td style='padding:9px 12px;border-bottom:1px solid #F0F0F0;font-weight:600;font-size:.86rem;color:#1A1A1A'>{$name}{$size}</td>
          <td style='padding:9px 12px;border-bottom:1px solid #F0F0F0;text-align:center;font-size:.86rem;color:#555'>{$qty}</td>
          <td style='padding:9px 12px;border-bottom:1px solid #F0F0F0;text-align:right;font-size:.86rem;color:#555'>{$price}</td>
          <td style='padding:9px 12px;border-bottom:1px solid #F0F0F0;text-align:right;font-weight:700;color:#1A1A1A'>{$line}</td>
        </tr>";
    }

    $ctaUrl = $trackUrl
        ? $trackUrl
        : ($newStatus === 'cancelled'
            ? 'mailto:'.getSetting('site_email','hello@africanattire.com')
            : $siteUrl.'/customer/orders.php');

    $content = "
    <!-- Status banner -->
    <div style='background:{$info['bg']};border-left:5px solid {$info['color']};
                border-radius:0 8px 8px 0;padding:14px 18px;margin-bottom:20px;
                display:flex;align-items:center;gap:12px'>
      <span style='font-size:2.2rem'>{$info['icon']}</span>
      <div>
        <div style='font-weight:800;font-size:1rem;color:{$info['color']};margin-bottom:3px'>
          {$info['title']}
        </div>
        <div style='font-size:.78rem;color:#666'>Order #{$orderNo}</div>
      </div>
    </div>

    <p style='margin:0 0 18px;color:#555;line-height:1.65'>
      Hi <strong>" . htmlspecialchars($toName) . "</strong>,
      {$info['message']}
    </p>

    <!-- Items ordered -->
    <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                color:#999;font-weight:700;margin-bottom:8px'>Items in Your Order</div>
    <table style='width:100%;border-collapse:collapse;border:1px solid #E8E8E8;
                  border-radius:8px;overflow:hidden;margin-bottom:20px'>
      <thead>
        <tr style='background:#F0F0F0'>
          <th style='padding:8px 12px;text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:#777;font-weight:700'>Product</th>
          <th style='padding:8px 12px;text-align:center;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:#777;font-weight:700'>Qty</th>
          <th style='padding:8px 12px;text-align:right;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:#777;font-weight:700'>Unit</th>
          <th style='padding:8px 12px;text-align:right;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:#777;font-weight:700'>Total</th>
        </tr>
      </thead>
      <tbody>{$itemRows}</tbody>
    </table>

    <!-- Delivery address -->
    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:14px 16px;margin-bottom:20px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#999;font-weight:700;margin-bottom:6px'>📍 Delivery Address</div>
      <div style='font-size:.86rem;color:#333;line-height:1.6'>
        " . nl2br(htmlspecialchars($order['delivery_address'] ?? '')) . "
      </div>
    </div>

    <!-- CTA -->
    <div style='text-align:center;margin-bottom:20px'>
      <a href='{$ctaUrl}'
         style='display:inline-block;padding:12px 28px;background:{$info['color']};color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        {$info['cta']} →
      </a>
    </div>

    <!-- Merchant info -->
    <div style='background:#F0F4FF;border:1px solid #C5D8F5;border-radius:8px;
                padding:12px 16px;margin-bottom:16px;font-size:.82rem;color:#333'>
      <strong>Sold by:</strong> {$sName}
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Questions? Contact us at
      <a href='mailto:" . getSetting('site_email','hello@africanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email','hello@africanattire.com') . "
      </a>
      with order number <strong>{$orderNo}</strong>.
    </p>";

    $statusLabel = ucfirst($newStatus);
    $html = mailTemplate(
        $content,
        "Your order {$orderNo} is now {$statusLabel} — " . SITE_NAME
    );

    sendMail(
        $toEmail,
        $toName,
        "{$info['icon']} Order {$orderNo} — {$statusLabel} | " . SITE_NAME,
        $html
    );
}

/**
 * Send welcome email to a new logistics portal user with temp credentials.
 */
function sendLogisticsWelcomeEmail(
    string $toEmail, string $toName, string $companyName,
    string $tempPassword, string $loginUrl
): void {
    $name = htmlspecialchars($toName);
    $co   = htmlspecialchars($companyName);
    $url  = htmlspecialchars($loginUrl);
    $pwd  = htmlspecialchars($tempPassword);

    $content = "
    <h2 style='margin:0 0 8px;color:#1A1A1A;font-size:1.15rem'>
      🚚 Welcome to the African Attire Logistics Portal, {$name}!
    </h2>
    <p style='margin:0 0 16px;color:#555'>
      You have been set up as a logistics partner for <strong>African Attire</strong>
      on behalf of <strong>{$co}</strong>. Below are your login credentials.
    </p>

    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:18px 20px;margin-bottom:20px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#999;font-weight:700;margin-bottom:12px'>Your Login Credentials</div>
      <div style='display:flex;flex-direction:column;gap:10px'>
        <div>
          <div style='font-size:.72rem;color:#999;margin-bottom:2px'>Portal URL</div>
          <a href='{$url}' style='font-weight:700;color:#1565C0;font-size:.9rem'>{$url}</a>
        </div>
        <div>
          <div style='font-size:.72rem;color:#999;margin-bottom:2px'>Email Address</div>
          <div style='font-weight:700;font-size:.9rem;color:#1A1A1A'>{$toEmail}</div>
        </div>
        <div>
          <div style='font-size:.72rem;color:#999;margin-bottom:2px'>Temporary Password</div>
          <div style='font-family:monospace;font-size:1.1rem;font-weight:800;
                      letter-spacing:.1em;color:#1A1A1A;background:#fff;
                      border:2px solid #1565C0;padding:6px 12px;
                      border-radius:6px;display:inline-block'>{$pwd}</div>
        </div>
      </div>
    </div>

    <div style='background:#FFF8F0;border:1px solid #FFE0B2;border-radius:8px;
                padding:12px 16px;margin-bottom:20px'>
      <div style='font-weight:700;font-size:.84rem;color:#E65100;margin-bottom:4px'>
        ⚠️ Important — Change Your Password
      </div>
      <div style='font-size:.82rem;color:#555'>
        This is a temporary password. You will be required to change it on first login.
        Keep your credentials secure and do not share them.
      </div>
    </div>

    <div style='text-align:center;margin-bottom:20px'>
      <a href='{$url}'
         style='display:inline-block;padding:12px 28px;background:#1565C0;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        Log In to Logistics Portal →
      </a>
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Questions? Contact
      <a href='mailto:" . getSetting('site_email','hello@shopafricanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email','hello@shopafricanattire.com') . "
      </a>
    </p>";

    $html = mailTemplate($content, "Your African Attire Logistics Portal access is ready");
    sendMail($toEmail, $toName, "🚚 Your Logistics Portal Access — African Attire", $html);
}

/**
 * Notify a logistics company that an order has been assigned to them.
 */
function sendLogisticsOrderAssignedEmail(
    array  $order,
    array  $items,
    string $companyEmail,
    string $companyName,
    string $shopName,
    string $loginUrl
): void {
    $orderNo = htmlspecialchars($order['order_number']);
    $co      = htmlspecialchars($companyName);
    $shop    = htmlspecialchars($shopName);
    $date    = date('F j, Y \a\t g:i A', strtotime($order['created_at']));

    $itemRows = '';
    foreach ($items as $it) {
        $name = htmlspecialchars($it['product_name']);
        $qty  = (int)$it['quantity'];
        $size = !empty($it['size']) ? ' ('.htmlspecialchars($it['size']).')' : '';
        $itemRows .= "<tr>
          <td style='padding:8px 12px;border-bottom:1px solid #F0F0F0;font-size:.86rem'>{$name}{$size}</td>
          <td style='padding:8px 12px;border-bottom:1px solid #F0F0F0;text-align:center;font-size:.86rem'>{$qty}</td>
        </tr>";
    }

    // Show delivery city/country only — not full address
    $rawLines   = array_values(array_filter(
        array_map('trim', preg_split('/\r?\n|,/', $order['delivery_address'] ?? ''))
    ));
    $total      = count($rawLines);
    $city       = $total >= 2 ? $rawLines[$total-2] : ($rawLines[0] ?? '');
    $region     = $total >= 1 ? $rawLines[$total-1] : '';
    $destination= implode(', ', array_filter([$city, $region]));

    $content = "
    <h2 style='margin:0 0 8px;color:#1A1A1A;font-size:1.15rem'>
      📦 New Order Assigned to {$co}
    </h2>
    <p style='margin:0 0 18px;color:#555'>
      <strong>{$shop}</strong> has assigned an order to your logistics company.
      Please log in to the portal to manage this delivery.
    </p>

    <div style='background:#E3F2FD;border:1px solid #90CAF9;border-radius:8px;
                padding:14px 16px;margin-bottom:18px'>
      <div style='font-weight:700;color:#0D47A1;font-size:.9rem;margin-bottom:2px'>
        Order #{$orderNo}
      </div>
      <div style='font-size:.8rem;color:#1565C0'>Assigned on {$date}</div>
    </div>

    <div style='margin-bottom:16px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#999;font-weight:700;margin-bottom:8px'>Items to Deliver</div>
      <table style='width:100%;border-collapse:collapse;border:1px solid #E8E8E8;border-radius:8px;overflow:hidden'>
        <thead>
          <tr style='background:#F0F0F0'>
            <th style='padding:8px 12px;text-align:left;font-size:.72rem;text-transform:uppercase;color:#777;font-weight:700'>Product</th>
            <th style='padding:8px 12px;text-align:center;font-size:.72rem;text-transform:uppercase;color:#777;font-weight:700'>Qty</th>
          </tr>
        </thead>
        <tbody>{$itemRows}</tbody>
      </table>
    </div>

    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:12px 16px;margin-bottom:20px'>
      <div style='font-size:.7rem;text-transform:uppercase;color:#999;font-weight:700;margin-bottom:6px'>
        📍 Delivery Destination
      </div>
      <div style='font-size:.9rem;font-weight:600;color:#1A1A1A'>{$destination}</div>
      <div style='font-size:.78rem;color:#999;margin-top:4px'>
        Full delivery details available after login
      </div>
    </div>

    <div style='text-align:center;margin-bottom:16px'>
      <a href='" . htmlspecialchars($loginUrl) . "'
         style='display:inline-block;padding:12px 28px;background:#1565C0;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        View Order in Logistics Portal →
      </a>
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      Contact " . getSetting('site_email','hello@shopafricanattire.com') . " for support.
    </p>";

    $html = mailTemplate($content, "New order #{$orderNo} assigned to {$co}");
    sendMail($companyEmail, $companyName, "📦 New Order #{$orderNo} Assigned — African Attire", $html);
}

/**
 * Send password reset email with a secure link.
 *
 * @param string $toEmail
 * @param string $toName
 * @param string $resetUrl   Full URL including token
 * @param string $portal     'customer'|'merchant'|'logistics'
 */
function sendPasswordResetEmail(
    string $toEmail,
    string $toName,
    string $resetUrl,
    string $portal = 'customer'
): void {
    $name     = htmlspecialchars($toName);
    $url      = htmlspecialchars($resetUrl);
    $portalLabel = ucfirst($portal);

    $content = "
    <h2 style='margin:0 0 8px;color:#1A1A1A;font-size:1.1rem'>
      🔑 Reset Your Password
    </h2>
    <p style='margin:0 0 18px;color:#555;line-height:1.65'>
      Hi <strong>{$name}</strong>, we received a request to reset the password
      for your African Attire <strong>{$portalLabel} account</strong>
      associated with this email address.
    </p>

    <div style='text-align:center;margin-bottom:22px'>
      <a href='{$url}'
         style='display:inline-block;padding:13px 32px;background:#1565C0;color:#fff;
                border-radius:6px;font-weight:700;font-size:.95rem;text-decoration:none;
                box-shadow:0 4px 12px rgba(21,101,192,.35)'>
        Reset My Password →
      </a>
    </div>

    <div style='background:#FFF8E1;border:1px solid #FFE082;border-radius:8px;
                padding:12px 16px;margin-bottom:20px;font-size:.82rem;color:#555'>
      <strong>⚠️ This link expires in 1 hour.</strong>
      If you did not request a password reset, you can safely ignore this email —
      your password will not change.
    </div>

    <p style='font-size:.8rem;color:#999;text-align:center;margin:0'>
      If the button above does not work, copy and paste this URL into your browser:<br>
      <a href='{$url}' style='color:#1565C0;word-break:break-all'>{$url}</a>
    </p>";

    $html = mailTemplate($content, "Reset your African Attire {$portalLabel} password");
    sendMail(
        $toEmail,
        $toName,
        "🔑 Reset Your Password — African Attire {$portalLabel}",
        $html
    );
}

/**
 * Send notification to one or more admin/staff email addresses.
 * The $settingKey holds a comma-separated list of recipient emails.
 *
 * @param string $settingKey  e.g. 'notify_new_dispute'
 * @param string $subject
 * @param string $htmlContent (already wrapped in mailTemplate if needed)
 * @param string $fallbackEmail  Use site_email if setting is empty
 */
function sendNotification(string $settingKey, string $subject, string $htmlContent, bool $useFallback = false): void {
    $raw       = getSetting($settingKey, '');
    $addresses = array_filter(array_map('trim', explode(',', $raw)));

    if (empty($addresses) && $useFallback) {
        $fallback = getSetting('site_email', '');
        if ($fallback) $addresses[] = $fallback;
    }

    if (empty($addresses)) return;

    $html = mailTemplate($htmlContent, $subject);
    foreach ($addresses as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendMail($email, 'African Attire Admin', $subject, $html);
        }
    }
}

/**
 * Notify admins of a new dispute.
 */
function sendDisputeNotification(array $dispute): void {
    $orderNo = htmlspecialchars($dispute['order_number'] ?? '');
    $name    = htmlspecialchars($dispute['cust_name']    ?? ($dispute['guest_name'] ?? 'Guest'));
    $email   = htmlspecialchars($dispute['cust_email']   ?? ($dispute['guest_email'] ?? ''));
    $reason  = htmlspecialchars($dispute['reason']       ?? '');
    $desc    = htmlspecialchars($dispute['description']  ?? '');
    $url     = BASE_URL . '/admin/disputes.php';

    $content = "
    <h2 style='margin:0 0 8px;font-size:1.1rem'>⚖️ New Dispute Raised</h2>
    <p style='margin:0 0 16px;color:#555'>A dispute has been submitted and requires your review.</p>
    <div style='background:#FFF8E1;border:1px solid #FFE082;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.86rem'>
      <div><strong>Order:</strong> {$orderNo}</div>
      <div><strong>Customer:</strong> {$name} ({$email})</div>
      <div><strong>Reason:</strong> {$reason}</div>
      " . ($desc ? "<div style='margin-top:6px'><strong>Details:</strong> {$desc}</div>" : '') . "
    </div>
    <div style='text-align:center'>
      <a href='{$url}' style='display:inline-block;padding:11px 24px;background:#C62828;color:#fff;border-radius:6px;font-weight:700;text-decoration:none;font-size:.9rem'>
        Review Dispute →
      </a>
    </div>";

    sendNotification('notify_new_dispute', '⚖️ New Dispute — Order ' . $orderNo, $content, true);
}

/**
 * Send email notification to customer when their dispute is updated.
 */
function sendDisputeUpdateEmail(
    string $toEmail,
    string $toName,
    string $orderNumber,
    string $newStatus,
    string $resolution = '',
    int    $disputeId  = 0
): void {
    $statusLabels = [
        'open'         => '📋 Open',
        'under_review' => '🔍 Under Review',
        'resolved'     => '✅ Resolved',
        'closed'       => '🔒 Closed',
    ];
    $statusColors = [
        'open'         => '#1565C0',
        'under_review' => '#E65100',
        'resolved'     => '#1B5E20',
        'closed'       => '#555',
    ];

    $label = $statusLabels[$newStatus] ?? ucfirst($newStatus);
    $color = $statusColors[$newStatus] ?? '#333';
    $name  = htmlspecialchars($toName);
    $order = htmlspecialchars($orderNumber);

    $trackUrl  = BASE_URL . '/customer/my-disputes.php';

    $content = "
    <h2 style='margin:0 0 8px;font-size:1.1rem;color:#1A1A1A'>
      ⚖️ Your Dispute Has Been Updated
    </h2>
    <p style='margin:0 0 16px;color:#555'>
      Hi <strong>{$name}</strong>, there has been an update to your dispute
      for order <strong>{$order}</strong>.
    </p>

    <div style='background:#F8F8F8;border:1px solid #E8E8E8;border-radius:8px;
                padding:14px 16px;margin-bottom:16px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#999;font-weight:700;margin-bottom:8px'>Dispute Status</div>
      <div style='font-size:1.1rem;font-weight:700;color:{$color}'>{$label}</div>
    </div>
    " . ($resolution ? "
    <div style='background:#E8F5E9;border:1px solid #A5D6A7;border-radius:8px;
                padding:14px 16px;margin-bottom:16px'>
      <div style='font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;
                  color:#2E7D32;font-weight:700;margin-bottom:6px'>Resolution</div>
      <p style='margin:0;font-size:.88rem;color:#1A1A1A;line-height:1.7'>" . htmlspecialchars($resolution) . "</p>
    </div>
    " : '') . "
    <div style='text-align:center;margin-bottom:16px'>
      <a href='{$trackUrl}'
         style='display:inline-block;padding:11px 26px;background:#1B6B3A;color:#fff;
                border-radius:6px;font-weight:700;font-size:.9rem;text-decoration:none'>
        View My Disputes →
      </a>
    </div>
    <p style='font-size:.78rem;color:#999;text-align:center;margin:0'>
      Questions? Contact
      <a href='mailto:" . getSetting('site_email','hello@shopafricanattire.com') . "'
         style='color:#F68B1E;text-decoration:none'>
        " . getSetting('site_email','hello@shopafricanattire.com') . "
      </a>
    </p>";

    $html = mailTemplate($content, "Dispute Update — Order {$order}");
    sendMail($toEmail, $toName, "⚖️ Dispute Update: {$label} — Order {$order}", $html);
}
