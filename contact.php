<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($subject === '') $errors[] = 'Subject is required.';
    if ($message === '') $errors[] = 'Message is required.';

    if (empty($errors)) {
        $stmt = db()->prepare(
            'INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$name, $email, $subject, $message]);
        $success = 'Your message has been sent successfully.';
    } else {
        old_set($_POST);
    }
}

$pageTitle = 'Contact Us - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <h2>Contact Us</h2>
    <p class="text-muted-custom mb-0">We're here to help with products, services, returns, and general support.</p>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="form-box">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form action="/contact.php" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?= old('name') ?>">
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
                </div>

                <div class="mb-3">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" value="<?= old('subject') ?>">
                </div>

                <div class="mb-3">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="6"><?= old('message') ?></textarea>
                </div>

                <button type="submit" class="btn btn-dani-primary">Send Message</button>
            </form>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="form-box h-100">
            <h3>Customer Support</h3>
            <p class="text-muted-custom">
                Reach out to Dani Group for any questions regarding auto parts,
                bike parts, accessories, order issues, or Dropper services.
            </p>

            <hr class="footer-line">

            <p><strong>Business:</strong> Dani Group (Pty) Ltd.</p>
            <p><strong>Location:</strong> South Africa</p>
            <p><strong>Support Areas:</strong> Orders, Returns, Towing, Delivery, Product Support</p>
        </div>
    </div>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
