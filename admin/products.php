<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int) $_POST['id'];

    $stmt = db()->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        flash_set('error', 'Product not found.');
    } else {
        $orderItemsExist = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $orderItemsExist->execute([$id]);

        if ((int) $orderItemsExist->fetchColumn() > 0) {
            flash_set('error', 'This product cannot be deleted because it exists in customer orders.');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM cart_items WHERE product_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM product_reviews WHERE product_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
                $pdo->commit();
                flash_set('success', 'Product deleted successfully.');
            } catch (Exception $ex) {
                $pdo->rollBack();
                flash_set('error', 'Error deleting product: ' . $ex->getMessage());
            }
        }
    }

    redirect('/admin/products.php');
}

$products = db()->query(
    'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC'
)->fetchAll();

$pageTitle = 'Manage Products - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Manage Products</h2>
    <p class="text-muted-custom mb-0">Add, edit, or remove products from the store.</p>
</div>

<?php render_flash_messages(); ?>

<p class="mb-3"><a href="/admin/add-product.php" class="btn btn-dani-primary">+ Add Product</a></p>

<div class="form-box">
    <?php if (empty($products)): ?>
        <p class="mb-0">No products yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Featured</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product['image_path'])): ?>
                                <img src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['category_name']) ?></td>
                        <td><?= money($product['price']) ?></td>
                        <td><?= (int) $product['stock_quantity'] ?></td>
                        <td><?= $product['is_featured'] ? 'Yes' : 'No' ?></td>
                        <td class="text-nowrap">
                            <a href="/admin/edit-product.php?id=<?= (int) $product['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                            <form action="/admin/products.php" method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete product &quot;<?= e($product['name']) ?>&quot;? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
