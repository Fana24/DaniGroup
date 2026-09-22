<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$pageTitle = 'Admin Dashboard - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Admin Dashboard</h2>
    <p class="text-muted-custom mb-0">Manage products, orders, returns, and customer messages.</p>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <a href="/admin/products.php" class="text-decoration-none">
            <div class="dashboard-card">
                <h4>Products</h4>
                <p>Manage product listings and pricing.</p>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="/admin/orders.php" class="text-decoration-none">
            <div class="dashboard-card">
                <h4>Orders</h4>
                <p>Track, cancel, or remove customer orders.</p>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="/admin/returns.php" class="text-decoration-none">
            <div class="dashboard-card">
                <h4>Returns</h4>
                <p>Review customer return requests.</p>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="/admin/messages.php" class="text-decoration-none">
            <div class="dashboard-card">
                <h4>Messages</h4>
                <p>View contact form submissions.</p>
            </div>
        </a>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
