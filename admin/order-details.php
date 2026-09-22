<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['order_id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['order_status'] ?? '';
        db()->prepare('UPDATE orders SET order_status = ? WHERE id = ?')->execute([$newStatus, $id]);
        flash_set('success', 'Order status updated successfully.');
        redirect('/admin/order-details.php?id=' . $id);
    }

    if ($action === 'cancel') {
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            flash_set('error', 'Order not found.');
            redirect('/admin/orders.php');
        }

        if ($existing['order_status'] === 'Cancelled') {
            flash_set('error', "Order #{$id} is already cancelled.");
        } else {
            db()->prepare('UPDATE orders SET order_status = ? WHERE id = ?')->execute(['Cancelled', $id]);
            flash_set('success', "Order #{$id} has been cancelled.");
        }
        redirect('/admin/order-details.php?id=' . $id);
    }

    if ($action === 'delete') {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
            $pdo->commit();
            flash_set('success', "Order #{$id} has been permanently removed.");
        } catch (Exception $ex) {
            $pdo->rollBack();
            flash_set('error', 'Error removing order: ' . $ex->getMessage());
        }
        redirect('/admin/orders.php');
    }
}

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
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

$statusOptions = ['Pending Payment', 'Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

$pageTitle = 'Order #' . $order['id'] . ' - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Order Details</h2>
    <p class="text-muted-custom mb-0">Review order contents, payment details, and update status.</p>
</div>

<?php render_flash_messages(); ?>

<div class="form-box mb-4">
    <div class="row g-4">
        <div class="col-md-6">
            <h4>Order Information</h4>
            <p><strong>Order ID:</strong> #<?= (int) $order['id'] ?></p>
            <p><strong>Date:</strong> <?= e(date('Y-m-d H:i', strtotime($order['created_at']))) ?></p>
            <p><strong>Order Status:</strong> <?= e($order['order_status']) ?></p>
            <p><strong>Payment Status:</strong> <?= e($order['payment_status']) ?></p>
            <p><strong>Payment Provider:</strong> <?= e($order['payment_provider']) ?></p>
            <p><strong>Customer:</strong> <?= e($order['full_name']) ?> (<?= e($order['email']) ?>)</p>
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

<div class="form-box mb-4">
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
</div>

<div class="form-box">
    <h4 class="mb-3">Update Order Status</h4>
    <form action="/admin/order-details.php?id=<?= (int) $order['id'] ?>" method="post" class="mb-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

        <div class="mb-3">
            <label>Order Status</label>
            <select name="order_status" class="form-select">
                <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= e($status) ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-dani-primary">Update Status</button>
    </form>

    <hr class="footer-line">

    <h4>Order Actions</h4>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($order['order_status'] !== 'Cancelled'): ?>
            <form action="/admin/order-details.php?id=<?= (int) $order['id'] ?>" method="post"
                  onsubmit="return confirm('Cancel order #<?= (int) $order['id'] ?> for <?= e($order['full_name']) ?>?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <button type="submit" class="btn btn-warning">Cancel Order</button>
            </form>
        <?php endif; ?>

        <form action="/admin/order-details.php?id=<?= (int) $order['id'] ?>" method="post"
              onsubmit="return confirm('Permanently remove order #<?= (int) $order['id'] ?>? This cannot be undone.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
            <button type="submit" class="btn btn-danger">Remove Order</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
