<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$view = $_GET['view'] ?? 'create';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $orderReference = trim($_POST['order_reference'] ?? '');
    $reason         = trim($_POST['reason'] ?? '');

    if ($orderReference === '') $errors[] = 'Order reference is required.';
    if (strlen($orderReference) > 150) $errors[] = 'Order reference is too long.';
    if ($reason === '') $errors[] = 'Reason is required.';
    if (strlen($reason) > 500) $errors[] = 'Reason is too long (max 500 characters).';

    $damageImagePath = null;

    if (empty($errors) && !empty($_FILES['damage_image']['name'])) {
        try {
            $damageImagePath = save_uploaded_image('damage_image', 'returns');
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (empty($errors)) {
        $stmt = db()->prepare(
            'INSERT INTO return_requests (user_id, order_reference, reason, damage_image_path, created_at, status)
             VALUES (?, ?, ?, ?, NOW(), ?)'
        );
        $stmt->execute([$user['id'], $orderReference, $reason, $damageImagePath, 'Pending']);

        redirect('/returns.php?view=mine');
    } else {
        old_set($_POST);
    }
}

if ($view === 'mine') {
    $stmt = db()->prepare('SELECT * FROM return_requests WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $myReturns = $stmt->fetchAll();
}

$pageTitle = ($view === 'mine' ? 'My Return Requests' : 'Request a Return') . ' - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<?php if ($view === 'mine'): ?>

    <div class="page-banner">
        <h2>My Return Requests</h2>
        <p class="text-muted-custom mb-0">Track the progress of your submitted return requests.</p>
    </div>

    <div class="form-box">
        <p class="mb-3"><a href="/returns.php" class="btn btn-dani-primary btn-sm">+ New Return Request</a></p>

        <?php if (empty($myReturns)): ?>
            <p>No return requests found.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Order Ref</th><th>Reason</th><th>Status</th><th>Date</th><th>Image</th></tr></thead>
                <tbody>
                    <?php foreach ($myReturns as $item): ?>
                        <tr>
                            <td><?= e($item['order_reference']) ?></td>
                            <td><?= e($item['reason']) ?></td>
                            <td><?= e($item['status']) ?></td>
                            <td><?= e(date('Y-m-d', strtotime($item['created_at']))) ?></td>
                            <td>
                                <?php if (!empty($item['damage_image_path'])): ?>
                                    <a href="<?= e($item['damage_image_path']) ?>" target="_blank" class="btn btn-dani-outline btn-sm">View Image</a>
                                <?php else: ?>
                                    <span>No image</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="page-banner">
        <h2>Request a Return</h2>
        <p class="text-muted-custom mb-0">Submit your damaged or incorrect item request with supporting details.</p>
    </div>

    <div class="form-box">
        <p class="mb-3"><a href="/returns.php?view=mine" class="btn btn-dani-outline btn-sm">View My Return Requests</a></p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form action="/returns.php" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label>Order Reference</label>
                <input type="text" name="order_reference" class="form-control" value="<?= old('order_reference') ?>">
            </div>

            <div class="mb-3">
                <label>Reason</label>
                <textarea name="reason" class="form-control" rows="6"><?= old('reason') ?></textarea>
            </div>

            <div class="mb-3">
                <label>Upload Image of Damaged Product</label>
                <input type="file" name="damage_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <button type="submit" class="btn btn-dani-primary">Submit Return Request</button>
        </form>
    </div>

<?php endif; ?>

<?php old_clear(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
