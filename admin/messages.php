<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Contact Messages - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Contact Messages</h2>
    <p class="text-muted-custom mb-0">Review messages submitted by customers and site visitors.</p>
</div>

<div class="form-box">
    <?php if (empty($messages)): ?>
        <p>No messages found.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th></tr></thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td><?= e(date('Y-m-d', strtotime($message['created_at']))) ?></td>
                        <td><?= e($message['name']) ?></td>
                        <td><?= e($message['email']) ?></td>
                        <td><?= e($message['subject']) ?></td>
                        <td><?= e($message['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
