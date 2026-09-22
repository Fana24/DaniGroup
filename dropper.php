<?php
require_once __DIR__ . '/includes/bootstrap.php';

$services = [
    ['title' => 'Send Parcels', 'description' => 'Fast parcel delivery service.'],
    ['title' => 'Move Furniture', 'description' => 'Furniture moving for homes and offices.'],
    ['title' => '24hr Towing Service', 'description' => 'Emergency towing at any time.'],
    ['title' => 'Become a Driver', 'description' => 'Apply to join our delivery and towing network.'],
];

$pageTitle = 'Dropper Services - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <h2>Dropper Services</h2>
    <p class="text-muted-custom mb-0">Reliable transport, towing, and delivery support from Dani Group.</p>
</div>

<div class="form-box mb-4">
    <p class="mb-0">
        Dani Group Dropper is built to help customers move parcels, furniture,
        vehicles, and more with dependable support and service access.
    </p>
</div>

<div class="row">
    <?php foreach ($services as $service): ?>
        <div class="col-md-6 mb-4">
            <div class="card dani-card h-100">
                <div class="card-body d-flex flex-column">
                    <h4><?= e($service['title']) ?></h4>
                    <p class="flex-grow-1"><?= e($service['description']) ?></p>

                    <?php if ($service['title'] === 'Become a Driver'): ?>
                        <a href="https://dms.danigroup.co.za" target="_blank" rel="noopener noreferrer" class="btn btn-dani-primary">Apply via DMS</a>
                    <?php elseif ($service['title'] === '24hr Towing Service'): ?>
                        <a href="/towing.php" class="btn btn-dani-primary">View Pricing &amp; Apply</a>
                    <?php elseif ($service['title'] === 'Send Parcels'): ?>
                        <a href="/parcel.php" class="btn btn-dani-primary">View Pricing &amp; Apply</a>
                    <?php elseif ($service['title'] === 'Move Furniture'): ?>
                        <a href="/moving.php" class="btn btn-dani-primary">View Pricing &amp; Apply</a>
                    <?php else: ?>
                        <a href="/contact.php" class="btn btn-dani-outline">Request Service</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
