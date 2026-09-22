<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_login();

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if (!password_verify($current, $row['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($newPass !== $confirm) {
        $errors[] = 'New passwords do not match.';
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        $success = 'Your password has been updated.';
    }
}

$pageTitle = 'Manage Profile - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Manage Profile</h2>
    <p class="text-muted-custom mb-0">Update your account password.</p>
</div>

<div class="form-box" style="max-width: 480px; margin: 0 auto;">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <p><strong>Email:</strong> <?= e($user['email']) ?></p>

    <form action="/account/change-password.php" method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-dani-primary">Update Password</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
