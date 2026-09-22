<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found - ' . SITE_NAME;
    require __DIR__ . '/includes/header.php';
    echo '<div class="form-box"><p>Sorry, that product could not be found.</p><a href="/products.php" class="btn btn-dani-primary">Back to Store</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Handle "Add a review" submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    csrf_verify();
    $user = require_login();

    $rating  = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5 || $comment === '') {
        flash_set('error', 'Please choose a rating and enter a comment.');
    } else {
        $stmt = db()->prepare(
            'INSERT INTO product_reviews (product_id, user_id, reviewer_name, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$product['id'], $user['id'], $user['email'], $rating, $comment]);
        flash_set('success', 'Thanks for your review!');
    }

    redirect('/product-details.php?id=' . $product['id']);
}

// Handle "Add to cart" submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    csrf_verify();
    $user = require_login();

    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    $stmt = db()->prepare('SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$user['id'], $product['id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = db()->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE id = ?');
        $stmt->execute([$quantity, $existing['id']]);
    } else {
        $stmt = db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $product['id'], $quantity]);
    }

    flash_set('success', 'Added to your cart.');
    redirect('/cart.php');
}

$reviewsStmt = db()->prepare('SELECT * FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC');
$reviewsStmt->execute([$product['id']]);
$reviews = $reviewsStmt->fetchAll();

$pageTitle = e($product['name']) . ' - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <h2><?= e($product['name']) ?></h2>
    <p class="text-muted-custom mb-0">Detailed product view</p>
</div>

<?php render_flash_messages(); ?>

<div class="form-box mb-4">
    <div class="row">
        <div class="col-md-6 mb-4 mb-md-0">
            <?php if (!empty($product['image_path'])): ?>
                <img src="<?= e($product['image_path']) ?>" class="img-fluid rounded" alt="<?= e($product['name']) ?>">
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <h2><?= e($product['name']) ?></h2>
            <p><?= e($product['description']) ?></p>
            <p><strong>Category:</strong> <?= e($product['category_name']) ?></p>
            <p><strong>Price:</strong> <?= money($product['price']) ?></p>

            <?php if ($product['stock_quantity'] <= 0): ?>
                <p class="stock-out"><strong>Out of Stock</strong></p>
            <?php elseif ($product['stock_quantity'] <= 5): ?>
                <p class="stock-low"><strong>Low Stock:</strong> Only <?= (int) $product['stock_quantity'] ?> left</p>
            <?php else: ?>
                <p class="stock-good"><strong>In Stock</strong></p>
            <?php endif; ?>

            <?php if ($product['stock_quantity'] > 0): ?>
                <form action="/product-details.php?id=<?= (int) $product['id'] ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_to_cart">

                    <div class="mb-3">
                        <label>Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= (int) $product['stock_quantity'] ?>" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-dani-primary">Add to Cart</button>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Currently Unavailable</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="form-box">
    <h3 class="mb-4">Customer Reviews</h3>

    <?php if (is_logged_in()): ?>
        <form action="/product-details.php?id=<?= (int) $product['id'] ?>" method="post" class="mb-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_review">

            <div class="mb-3">
                <label>Rating</label>
                <select name="rating" class="form-select" required>
                    <option value="">Select Rating</option>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Very Good</option>
                    <option value="3">3 - Good</option>
                    <option value="2">2 - Fair</option>
                    <option value="1">1 - Poor</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Comment</label>
                <textarea name="comment" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn btn-dani-primary">Submit Review</button>
        </form>
    <?php else: ?>
        <p>Please <a href="/account/login.php">log in</a> to leave a review.</p>
    <?php endif; ?>

    <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review-box mb-3">
                <div class="d-flex justify-content-between">
                    <strong><?= e($review['reviewer_name']) ?></strong>
                    <span><?= e(date('Y-m-d', strtotime($review['created_at']))) ?></span>
                </div>
                <div class="mb-2">Rating: <?= (int) $review['rating'] ?> / 5</div>
                <p class="mb-0"><?= e($review['comment']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="mb-0">No reviews yet. Be the first to review this product.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
