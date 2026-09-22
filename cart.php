<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    csrf_verify();
    $stmt = db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
    $stmt->execute([(int) $_POST['id'], $user['id']]);
    redirect('/cart.php');
}

$stmt = db()->prepare(
    'SELECT ci.*, p.name AS product_name, p.price AS product_price, p.image_path
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.user_id = ?'
);
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

$pageTitle = 'Your Cart - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="row align-items-center g-4">
        <div class="col-md-8">
            <h2>Your Cart</h2>
            <p class="text-muted-custom mb-0">Review your selected products before checkout.</p>
        </div>
        <div class="col-md-4 text-center">
            <img src="/assets/images/site/ecommerce-cart-phone.jpg" alt="Your Dani Group cart" class="img-fluid rounded" style="max-height: 160px;">
        </div>
    </div>
</div>

<?php render_flash_messages(); ?>

<div class="form-box">
    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty.</p>
        <a href="/products.php" class="btn btn-dani-primary mt-2">Start Shopping</a>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $cartTotal = 0; ?>
                <?php foreach ($cartItems as $item): ?>
                    <?php $lineTotal = $item['product_price'] * $item['quantity']; $cartTotal += $lineTotal; ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= money($item['product_price']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= money($lineTotal) ?></td>
                        <td>
                            <form action="/cart.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-end mt-3">
            <p class="fw-bold">Cart Total: <?= money($cartTotal) ?></p>
            <a href="/checkout.php" class="btn btn-dani-primary">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
