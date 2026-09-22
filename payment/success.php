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
$stmt->execute(['Paid', 'Processing', $order['id']]);

$itemsStmt = db()->prepare(
    'SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?'
);
$itemsStmt->execute([$order['id']]);
$orderItems = $itemsStmt->fetchAll();

$pageTitle = 'Payment Successful - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Payment Successful</h2>
    <p class="text-muted-custom mb-0">Thank you - your order is being processed.</p>
</div>

<div class="form-box">
    <p><strong>Order ID:</strong> #<?= (int) $order['id'] ?></p>
    <p><strong>Order Status:</strong> Processing</p>
    <p><strong>Payment Status:</strong> Paid</p>

    <table class="table mt-3">
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= money($item['unit_price']) ?></td>
                    <td><?= money($item['unit_price'] * $item['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h5 class="text-end">Order Total: <?= money($order['total_amount']) ?></h5>

    <a href="/orders/index.php" class="btn btn-dani-primary mt-3">View My Orders</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
