<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$stmt = db()->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?');
$stmt->execute([$user['id']]);
$cartItemCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute([$user['id']]);
$orderCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM return_requests WHERE user_id = ?');
$stmt->execute([$user['id']]);
$returnCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$recentOrders = $stmt->fetchAll();

$pageTitle = 'My Account - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>My Account</h2>
    <p class="text-muted-custom mb-0">Manage your activity, orders, returns, and account options.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="dashboard-card">
            <h4>Email</h4>
            <p><?= e($user['email']) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card">
            <h4>Cart Items</h4>
            <p><?= $cartItemCount ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card">
            <h4>Orders</h4>
            <p><?= $orderCount ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-card">
            <h4>Returns</h4>
            <p><?= $returnCount ?></p>
        </div>
    </div>
</div>

<div class="form-box mb-4">
    <h3>Quick Links</h3>
    <div class="mt-3">
        <a href="/orders/index.php" class="btn btn-dani-primary me-2 mb-2">My Orders</a>
        <a href="/returns.php?view=mine" class="btn btn-dani-outline me-2 mb-2">My Returns</a>
        <a href="/cart.php" class="btn btn-dani-outline me-2 mb-2">My Cart</a>
        <a href="/account/change-password.php" class="btn btn-dani-outline mb-2">Manage Profile</a>
    </div>
</div>

<div class="form-box">
    <h3>Recent Orders</h3>

    <?php if (empty($recentOrders)): ?>
        <p>No recent orders found.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Order ID</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= e(date('Y-m-d', strtotime($order['created_at']))) ?></td>
                        <td><?= e($order['order_status']) ?></td>
                        <td><?= money($order['total_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
