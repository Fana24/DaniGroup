<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>My Orders</h2>
    <p class="text-muted-custom mb-0">Track your purchases and order status.</p>
</div>

<div class="form-box">
    <?php if (empty($orders)): ?>
        <p>You have not placed any orders yet.</p>
        <a href="/products.php" class="btn btn-dani-primary mt-2">Browse Products</a>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>Order ID</th><th>Date</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= e(date('Y-m-d', strtotime($order['created_at']))) ?></td>
                        <td><?= e($order['order_status']) ?></td>
                        <td><?= e($order['payment_status']) ?></td>
                        <td><?= money($order['total_amount']) ?></td>
                        <td><a href="/orders/details.php?id=<?= (int) $order['id'] ?>" class="btn btn-dani-primary btn-sm">Details</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
