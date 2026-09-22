<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        $orderId = (int) $_POST['order_id'];
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            flash_set('error', 'Order not found.');
        } elseif ($order['order_status'] === 'Cancelled') {
            flash_set('error', "Order #{$order['id']} is already cancelled.");
        } else {
            db()->prepare('UPDATE orders SET order_status = ? WHERE id = ?')->execute(['Cancelled', $orderId]);
            flash_set('success', "Order #{$orderId} has been cancelled.");
        }
        redirect('/admin/orders.php');
    }

    if ($action === 'delete') {
        $orderId = (int) $_POST['id'];
        $stmt = db()->prepare('SELECT id FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);

        if (!$stmt->fetch()) {
            flash_set('error', 'Order not found.');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$orderId]);
                $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$orderId]);
                $pdo->commit();
                flash_set('success', "Order #{$orderId} has been permanently removed.");
            } catch (Exception $ex) {
                $pdo->rollBack();
                flash_set('error', 'Error removing order: ' . $ex->getMessage());
            }
        }
        redirect('/admin/orders.php');
    }
}

$orders = db()->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Manage Orders - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Manage Orders</h2>
    <p class="text-muted-custom mb-0">Monitor customer orders, cancel them, or remove them entirely.</p>
</div>

<?php render_flash_messages(); ?>

<div class="form-box">
    <table class="table">
        <thead>
            <tr><th>Order ID</th><th>Customer</th><th>Email</th><th>Status</th><th>Payment</th><th>Date</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= e($order['full_name']) ?></td>
                    <td><?= e($order['email']) ?></td>
                    <td><?= e($order['order_status']) ?></td>
                    <td><?= e($order['payment_status']) ?></td>
                    <td><?= e(date('Y-m-d', strtotime($order['created_at']))) ?></td>
                    <td><?= money($order['total_amount']) ?></td>
                    <td class="text-nowrap">
                        <a href="/admin/order-details.php?id=<?= (int) $order['id'] ?>" class="btn btn-dani-primary btn-sm">Details</a>

                        <?php if ($order['order_status'] !== 'Cancelled'): ?>
                            <form action="/admin/orders.php" method="post" style="display:inline;"
                                  onsubmit="return confirm('Cancel order #<?= (int) $order['id'] ?> for <?= e($order['full_name']) ?>?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <button type="submit" class="btn btn-warning btn-sm">Cancel</button>
                            </form>
                        <?php endif; ?>

                        <form action="/admin/orders.php" method="post" style="display:inline;"
                              onsubmit="return confirm('Permanently remove order #<?= (int) $order['id'] ?>? This cannot be undone.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
