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

$pageTitle = 'Invoice #' . $order['id'] . ' - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Invoice / Receipt</h2>
    <p class="text-muted-custom mb-0">Order receipt for Dani Group purchase.</p>
</div>

<div class="form-box">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4>Dani Group</h4>
            <p>South Africa</p>
            <p>Automotive Parts and Services</p>
        </div>
        <div class="col-md-6 text-md-end">
            <h4>Invoice</h4>
            <p><strong>Order ID:</strong> #<?= (int) $order['id'] ?></p>
            <p><strong>Date:</strong> <?= e(date('Y-m-d H:i', strtotime($order['created_at']))) ?></p>
            <p><strong>Payment Status:</strong> <?= e($order['payment_status']) ?></p>
        </div>
    </div>

    <hr class="footer-line">

    <div class="mb-4">
        <h5>Billed To</h5>
        <p class="mb-1"><?= e($order['full_name']) ?></p>
        <p class="mb-1"><?= e($order['address_line1']) ?></p>
        <?php if (!empty($order['address_line2'])): ?>
            <p class="mb-1"><?= e($order['address_line2']) ?></p>
        <?php endif; ?>
        <p class="mb-1"><?= e($order['city']) ?>, <?= e($order['state']) ?>, <?= e($order['postal_code']) ?></p>
        <p class="mb-1"><?= e($order['country']) ?></p>
        <p class="mb-1"><?= e($order['email']) ?></p>
    </div>

    <table class="table">
        <thead><tr><th>Product</th><th>Unit Price</th><th>Qty</th><th>Total</th></tr></thead>
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

    <h4 class="text-end mt-3">Grand Total: <?= money($order['total_amount']) ?></h4>

    <div class="mt-4">
        <button class="btn btn-dani-outline" onclick="window.print()">Print Receipt</button>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
