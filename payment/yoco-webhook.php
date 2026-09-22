<?php
/**
 * Yoco webhook receiver - ports YocoWebhookController.cs.
 *
 * IMPORTANT: same caveat as the original controller - replace this with
 * exact Yoco webhook signature verification based on the current Yoco
 * docs/portal for your account. The header name and verification
 * algorithm here may need to change once you check your Yoco dashboard.
 *
 * Configure this URL in your Yoco merchant portal as:
 *   https://yourdomain.co.za/payment/yoco-webhook.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

if (empty(YOCO_WEBHOOK_SECRET)) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook secret not configured.']);
    exit;
}

$body = file_get_contents('php://input');
$event = json_decode($body, true);

if (!is_array($event)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload.']);
    exit;
}

$eventType = $event['type'] ?? null;
$checkoutId = $event['data']['id'] ?? null;

if ($eventType === 'payment.succeeded' && $checkoutId) {
    $stmt = db()->prepare('SELECT id FROM orders WHERE payment_checkout_id = ?');
    $stmt->execute([$checkoutId]);
    $order = $stmt->fetch();

    if ($order) {
        $update = db()->prepare('UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?');
        $update->execute(['Paid', 'Processing', $order['id']]);
    }
}

if ($eventType === 'payment.failed' && $checkoutId) {
    $stmt = db()->prepare('SELECT id FROM orders WHERE payment_checkout_id = ?');
    $stmt->execute([$checkoutId]);
    $order = $stmt->fetch();

    if ($order) {
        $update = db()->prepare('UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?');
        $update->execute(['Failed', 'Pending Payment', $order['id']]);
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
