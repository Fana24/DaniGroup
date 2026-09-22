<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$orderId = (int) ($_GET['orderId'] ?? 0);

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found - ' . SITE_NAME;
    require __DIR__ . '/../includes/header.php';
    echo '<div class="form-box"><p>Sorry, that order could not be found.</p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = db()->prepare('UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?');
$stmt->execute(['Cancelled', 'Pending', $order['id']]);

$pageTitle = 'Payment Cancelled - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Payment Cancelled</h2>
    <p class="text-muted-custom mb-0">Your payment was not completed.</p>
</div>

<div class="form-box">
    <p><strong>Order ID:</strong> #<?= (int) $order['id'] ?></p>
    <p>You can try paying again from your order history, or contact us if you need help.</p>
    <a href="/orders/index.php" class="btn btn-dani-primary mt-2">View My Orders</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
