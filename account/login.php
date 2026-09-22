<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/account/dashboard.php');
}

$errors = [];
$returnUrl = $_GET['return'] ?? $_POST['return'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = attempt_login($email, $password);

    if (!$result['ok']) {
        $errors[] = $result['error'];
    } else {
        if ($returnUrl && str_starts_with($returnUrl, '/')) {
            redirect($returnUrl);
        }
        redirect('/account/dashboard.php');
    }
}

$pageTitle = 'Log In - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Log In</h2>
    <p class="text-muted-custom mb-0">Access your account, orders, and cart.</p>
</div>

<div class="form-box" style="max-width: 460px; margin: 0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form action="/account/login.php<?= $returnUrl ? '?return=' . urlencode($returnUrl) : '' ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-dani-primary w-100">Log In</button>
    </form>

    <p class="mt-3 mb-0">Don't have an account? <a href="/account/register.php">Register</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
