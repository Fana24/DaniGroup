<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/yoco.php';

$user = require_login();

$stmt = db()->prepare(
    'SELECT ci.*, p.name AS product_name, p.price AS product_price
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.user_id = ?'
);
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect('/cart.php');
}

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['product_price'] * $item['quantity'];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fullName     = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $addressLine2 = trim($_POST['address_line2'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $state        = trim($_POST['state'] ?? '');
    $postalCode   = trim($_POST['postal_code'] ?? '');
    $country      = trim($_POST['country'] ?? '');

    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($addressLine1 === '') $errors[] = 'Address line 1 is required.';
    if ($city === '') $errors[] = 'City is required.';
    if ($state === '') $errors[] = 'State/Province is required.';
    if ($postalCode === '') $errors[] = 'Postal code is required.';
    if ($country === '') $errors[] = 'Country is required.';

    if (empty($errors)) {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, full_name, email, address_line1, address_line2, city, state, postal_code, country,
                    order_status, created_at, total_amount, payment_status, payment_provider)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)'
            );
            $stmt->execute([
                $user['id'], $fullName, $email, $addressLine1, $addressLine2 ?: null,
                $city, $state, $postalCode, $country,
                'Pending Payment', $cartTotal, 'Pending', 'Yoco',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
            );
            foreach ($cartItems as $item) {
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['product_price']]);
            }

            $pdo->commit();
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Could not create your order. Please try again.';
        }

        if (empty($errors)) {
            $order = ['id' => $orderId, 'total_amount' => $cartTotal, 'email' => $email];
            $yocoResult = yoco_create_checkout($order);

            if (!$yocoResult['ok']) {
                $errors[] = $yocoResult['error'];
            } else {
                $stmt = $pdo->prepare('UPDATE orders SET payment_checkout_id = ?, payment_reference = ? WHERE id = ?');
                $stmt->execute([$yocoResult['id'], $yocoResult['id'], $orderId]);

                // In a more advanced production setup, only clear the cart after a
                // verified payment-success webhook rather than at checkout time.
                $clearStmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
                $clearStmt->execute([$user['id']]);

                redirect($yocoResult['redirect_url']);
            }
        }
    }

    if (!empty($errors)) {
        old_set($_POST);
    }
}

$pageTitle = 'Checkout - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <h2>Checkout</h2>
    <p class="text-muted-custom mb-0">Enter your delivery details to complete your order.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="form-box">
            <h4 class="mb-3">Delivery Details</h4>
            <form action="/checkout.php" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= old('full_name') ?>" required>
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= old('email', $user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Address Line 1</label>
                    <input type="text" name="address_line1" class="form-control" value="<?= old('address_line1') ?>" required>
                </div>

                <div class="mb-3">
                    <label>Address Line 2 (optional)</label>
                    <input type="text" name="address_line2" class="form-control" value="<?= old('address_line2') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" value="<?= old('city') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>State / Province</label>
                        <input type="text" name="state" class="form-control" value="<?= old('state') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= old('postal_code') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Country</label>
                        <input type="text" name="country" class="form-control" value="<?= old('country', 'South Africa') ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-dani-primary">Continue to Payment</button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="form-box">
            <h4 class="mb-3">Order Summary</h4>
            <table class="table">
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?= e($item['product_name']) ?> &times; <?= (int) $item['quantity'] ?></td>
                            <td class="text-end"><?= money($item['product_price'] * $item['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h5 class="text-end">Total: <?= money($cartTotal) ?></h5>
        </div>
    </div>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
