<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Featured products - a different random set of up to 6 on every page load.
$stmt = db()->query(
    'SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_featured = 1
     ORDER BY RAND()
     LIMIT 6'
);
$featuredProducts = $stmt->fetchAll();

// If there aren't 6 featured products yet, top up with other random products
// so the homepage section still looks full.
if (count($featuredProducts) < 6) {
    $needed = 6 - count($featuredProducts);
    $excludeIds = array_column($featuredProducts, 'id');

    if ($excludeIds) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category_name FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id NOT IN ($placeholders)
             ORDER BY RAND() LIMIT $needed"
        );
        $stmt->execute($excludeIds);
    } else {
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category_name FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY RAND() LIMIT $needed"
        );
        $stmt->execute();
    }

    $featuredProducts = array_merge($featuredProducts, $stmt->fetchAll());
}

// Shop by Category - every category in the store (including ones the admin
// has added), each with a different random sample of its products on every
// page load.
$categories = db()->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();

$categoryProducts = [];
foreach ($categories as $category) {
    $stmt = db()->prepare(
        'SELECT * FROM products WHERE category_id = ? ORDER BY RAND() LIMIT 3'
    );
    $stmt->execute([$category['id']]);
    $categoryProducts[$category['id']] = $stmt->fetchAll();
}

$pageTitle = SITE_NAME . ' - Auto Parts, Accessories & Delivery Services';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="hero-overlay">
        <div class="hero-content text-center">
            <span class="badge-dani mb-3 d-inline-block">Trusted Automotive &amp; Service Platform</span>
            <h1>Dani Group</h1>
            <p>
                Your trusted destination for premium car parts, appliances, accessories, bike parts,
                parcel delivery, towing, furniture moving, and reliable customer support.
            </p>
            <div class="mt-4">
                <a href="/products.php" class="btn btn-dani-primary btn-lg me-2 mb-2">Shop Now</a>
                <a href="/dropper.php" class="btn btn-dani-outline btn-lg mb-2">Explore Services</a>
            </div>
        </div>
    </div>
</section>

<section class="my-5">
    <div class="row align-items-center g-4">
        <div class="col-md-6 text-center">
            <img src="/assets/images/site/ecommerce-laptop-gifts.jpg" alt="Shop online with Dani Group" class="img-fluid rounded shadow-lg">
        </div>
        <div class="col-md-6 text-center text-md-start">
            <img src="/assets/images/logo/DA Logo.png" alt="Dani Group Logo" style="max-height: 120px;" class="mb-3">
            <h2 class="section-title">Performance. Reliability. Service.</h2>
            <p class="text-muted-custom">
                Dani Group brings together automotive retail and practical customer services in one powerful platform.
            </p>
        </div>
    </div>
</section>

<section class="my-5">
    <div class="page-banner text-center">
        <h2 class="section-title">Shop by Category</h2>
        <p class="text-muted-custom mb-0">A fresh pick of products from each category every time you visit.</p>
    </div>

    <?php if (empty($categories)): ?>
        <div class="form-box text-center">
            <p class="mb-0">No categories yet - check back soon.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($categories as $category): ?>
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0"><?= e($category['name']) ?></h3>
                <a href="/products.php?categoryId=<?= (int) $category['id'] ?>" class="btn btn-dani-outline btn-sm">Browse All</a>
            </div>

            <?php if (empty($categoryProducts[$category['id']])): ?>
                <div class="category-box">
                    <p class="mb-0">No products in this category yet.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($categoryProducts[$category['id']] as $product): ?>
                        <div class="col-md-4">
                            <div class="card dani-card h-100">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?= e($product['image_path']) ?>" class="card-img-top product-image" alt="<?= e($product['name']) ?>">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5><?= e($product['name']) ?></h5>
                                    <p class="flex-grow-1"><?= e($product['description']) ?></p>
                                    <p class="product-price"><strong><?= money($product['price']) ?></strong></p>
                                    <a href="/product-details.php?id=<?= (int) $product['id'] ?>" class="btn btn-dani-primary">View Product</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

<section class="my-5">
    <div class="page-banner text-center">
        <h2 class="section-title">Dropper Services</h2>
        <p class="text-muted-custom mb-0">
            From parcel delivery to 24-hour towing, Dani Group helps keep your day moving.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="dashboard-card">
                <h4>Send Parcels</h4>
                <p>Secure parcel movement with fast service options.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card">
                <h4>Move Furniture</h4>
                <p>Convenient furniture transport for home and office needs.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card">
                <h4>24hr Towing</h4>
                <p>Round-the-clock towing support when you need it most.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="dashboard-card">
                <h4>Become a Driver</h4>
                <p>Join Dani Group and grow with our service network.</p>
            </div>
        </div>
    </div>
</section>

<section class="mt-5">
    <div class="page-banner text-center">
        <h2 class="section-title">Featured Products</h2>
        <p class="text-muted-custom mb-0">A new selection every time you visit</p>
    </div>

    <div class="row">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card dani-card h-100">
                    <?php if (!empty($product['image_path'])): ?>
                        <img src="<?= e($product['image_path']) ?>" class="card-img-top product-image" alt="<?= e($product['name']) ?>">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5><?= e($product['name']) ?></h5>
                        <p class="flex-grow-1"><?= e($product['description']) ?></p>
                        <p class="product-price"><strong><?= money($product['price']) ?></strong></p>
                        <a href="/product-details.php?id=<?= (int) $product['id'] ?>" class="btn btn-dani-primary">View Product</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

