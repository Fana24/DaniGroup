<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$statusOptions = ['Pending', 'Approved', 'Rejected', 'Completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) $_POST['id'];
    $status = $_POST['status'] ?? 'Pending';
    db()->prepare('UPDATE return_requests SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Return request updated.');
    redirect('/admin/returns.php');
}

$returns = db()->query(
    'SELECT r.*, u.email AS user_email FROM return_requests r
     LEFT JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC'
)->fetchAll();

$pageTitle = 'Return Requests - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Return Requests</h2>
    <p class="text-muted-custom mb-0">Review and update customer return requests.</p>
</div>

<?php render_flash_messages(); ?>

<div class="form-box">
    <?php if (empty($returns)): ?>
        <p class="mb-0">No return requests found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Customer</th><th>Order Ref</th><th>Reason</th><th>Image</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $item): ?>
                    <tr>
                        <td><?= e(date('Y-m-d', strtotime($item['created_at']))) ?></td>
                        <td><?= e($item['user_email']) ?></td>
                        <td><?= e($item['order_reference']) ?></td>
                        <td><?= e($item['reason']) ?></td>
                        <td>
                            <?php if (!empty($item['damage_image_path'])): ?>
                                <a href="<?= e($item['damage_image_path']) ?>" target="_blank" class="btn btn-dani-outline btn-sm">View</a>
                            <?php else: ?>
                                <span>None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="/admin/returns.php" method="post" class="d-flex gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <select name="status" class="form-select form-select-sm" style="width:auto;">
                                    <?php foreach ($statusOptions as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-dani-primary btn-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
