<?php
require_once __DIR__ . '/includes/bootstrap.php';

$searchTerm  = trim($_GET['searchTerm'] ?? '');
$categoryId  = isset($_GET['categoryId']) && $_GET['categoryId'] !== '' ? (int) $_GET['categoryId'] : null;
$minPrice    = isset($_GET['minPrice']) && $_GET['minPrice'] !== '' ? (float) $_GET['minPrice'] : null;
$maxPrice    = isset($_GET['maxPrice']) && $_GET['maxPrice'] !== '' ? (float) $_GET['maxPrice'] : null;
$inStockOnly = isset($_GET['inStockOnly']);

$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
$params = [];

if ($searchTerm !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
}
if ($categoryId !== null) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}
if ($minPrice !== null) {
    $sql .= ' AND p.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $sql .= ' AND p.price <= ?';
    $params[] = $maxPrice;
}
if ($inStockOnly) {
    $sql .= ' AND p.stock_quantity > 0';
}
$sql .= ' ORDER BY p.name ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = db()->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();

$pageTitle = 'Online Store - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="row align-items-center g-4">
        <div class="col-md-8">
            <h2>Online Store</h2>
            <p class="text-muted-custom mb-0">Browse quality products from Dani Group.</p>
        </div>
        <div class="col-md-4 text-center">
            <img src="/assets/images/site/ecommerce-cart-laptop.webp" alt="Browse the Dani Group store" class="img-fluid rounded" style="max-height: 160px;">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="form-box">
            <h4 class="mb-3">Filter Products</h4>

            <form method="get">
                <div class="mb-3">
                    <label>Search</label>
                    <input type="text" name="searchTerm" value="<?= e($searchTerm) ?>" class="form-control" placeholder="Search products...">
                </div>

                <div class="mb-3">
                    <label>Category</label>
                    <select name="categoryId" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Min Price</label>
                    <input type="number" step="0.01" name="minPrice" value="<?= e($minPrice !== null ? (string) $minPrice : '') ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Max Price</label>
                    <input type="number" step="0.01" name="maxPrice" value="<?= e($maxPrice !== null ? (string) $maxPrice : '') ?>" class="form-control">
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="inStockOnly" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
                    <label class="form-check-label">In stock only</label>
                </div>

                <button type="submit" class="btn btn-dani-primary w-100 mb-2">Apply Filters</button>
                <a href="/products.php" class="btn btn-dani-outline w-100">Clear Filters</a>
            </form>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="row">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="form-box">
                        <p class="mb-0">No products found matching your filters.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card dani-card h-100">
                        <?php if (!empty($product['image_path'])): ?>
                            <img src="<?= e($product['image_path']) ?>" class="card-img-top product-image" alt="<?= e($product['name']) ?>">
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h5><?= e($product['name']) ?></h5>
                            <p class="text-muted-custom"><?= e($product['category_name']) ?></p>
                            <p class="flex-grow-1"><?= e($product['description']) ?></p>

                            <?php if ($product['stock_quantity'] <= 0): ?>
                                <p class="stock-out mb-2">Out of Stock</p>
                            <?php elseif ($product['stock_quantity'] <= 5): ?>
                                <p class="stock-low mb-2">Low Stock: <?= (int) $product['stock_quantity'] ?> left</p>
                            <?php else: ?>
                                <p class="stock-good mb-2">In Stock</p>
                            <?php endif; ?>

                            <p class="product-price"><?= money($product['price']) ?></p>
                            <a href="/product-details.php?id=<?= (int) $product['id'] ?>" class="btn btn-dani-primary">Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
