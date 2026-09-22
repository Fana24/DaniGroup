<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found - ' . SITE_NAME;
    require __DIR__ . '/../includes/header.php';
    echo '<div class="form-box"><p>Sorry, that order could not be found.</p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$itemsStmt = db()->prepare(
    'SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?'
);
$itemsStmt->execute([$order['id']]);
$orderItems = $itemsStmt->fetchAll();

$pageTitle = 'Order #' . $order['id'] . ' - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Order Details</h2>
    <p class="text-muted-custom mb-0">Review your order and payment information.</p>
</div>

<div class="form-box mb-4">
    <div class="row g-4">
        <div class="col-md-6">
            <h4>Order Information</h4>
            <p><strong>Order ID:</strong> #<?= (int) $order['id'] ?></p>
            <p><strong>Date:</strong> <?= e(date('Y-m-d H:i', strtotime($order['created_at']))) ?></p>
            <p><strong>Order Status:</strong> <?= e($order['order_status']) ?></p>
            <p><strong>Payment Status:</strong> <?= e($order['payment_status']) ?></p>
            <p><strong>Payment Provider:</strong> <?= e($order['payment_provider']) ?></p>
        </div>

        <div class="col-md-6">
            <h4>Delivery Address</h4>
            <p><?= e($order['full_name']) ?></p>
            <p><?= e($order['address_line1']) ?></p>
            <?php if (!empty($order['address_line2'])): ?>
                <p><?= e($order['address_line2']) ?></p>
            <?php endif; ?>
            <p><?= e($order['city']) ?>, <?= e($order['state']) ?>, <?= e($order['postal_code']) ?></p>
            <p><?= e($order['country']) ?></p>
        </div>
    </div>
</div>

<div class="form-box">
    <h4 class="mb-3">Items Ordered</h4>

    <table class="table">
        <thead><tr><th>Product</th><th>Unit Price</th><th>Quantity</th><th>Line Total</th></tr></thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= money($item['unit_price']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= money($item['unit_price'] * $item['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="text-end mt-3">Total: <?= money($order['total_amount']) ?></h4>
    <a href="/orders/invoice.php?id=<?= (int) $order['id'] ?>" class="btn btn-dani-outline mt-3">View Invoice / Receipt</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
