<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/account/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $result = register_user($fullName, $email, $password);
        if (!$result['ok']) {
            $errors[] = $result['error'];
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$result['user_id']]);
            login_user($stmt->fetch());
            redirect('/account/dashboard.php');
        }
    }

    if (!empty($errors)) {
        old_set(['full_name' => $fullName, 'email' => $email]);
    }
}

$pageTitle = 'Register - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Create an Account</h2>
    <p class="text-muted-custom mb-0">Register to shop, track orders, and request services.</p>
</div>

<div class="form-box" style="max-width: 500px; margin: 0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form action="/account/register.php" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= old('full_name') ?>" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>

        <div class="mb-3">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8">
        </div>

        <button type="submit" class="btn btn-dani-primary w-100">Register</button>
    </form>

    <p class="mt-3 mb-0">Already have an account? <a href="/account/login.php">Log in</a></p>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
