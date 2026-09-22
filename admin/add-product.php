<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name          = trim($_POST['name'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = (float) ($_POST['price'] ?? 0);
    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);
    $categoryId    = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
    $isFeatured    = isset($_POST['is_featured']) ? 1 : 0;
    $newCategoryName = trim($_POST['new_category_name'] ?? '');

    // Let the admin create a brand new category on the fly instead of only
    // picking from the existing dropdown list.
    $resolvedCategoryId = apply_new_category_if_provided($newCategoryName);
    if ($resolvedCategoryId !== null) {
        $categoryId = $resolvedCategoryId;
    }

    if ($name === '') $errors[] = 'Product name is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if ($price < 0) $errors[] = 'Price must be zero or more.';
    if ($stockQuantity < 0) $errors[] = 'Stock quantity must be zero or more.';
    if (!$categoryId) $errors[] = 'Please select a category or type a new one.';

    $imagePath = null;
    if (empty($errors)) {
        try {
            $imagePath = save_uploaded_image('image_file', 'products');
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO products (name, description, price, stock_quantity, category_id, image_path, is_featured)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $description, $price, $stockQuantity, $categoryId, $imagePath, $isFeatured]);

            flash_set('success', 'Product added successfully.');
            redirect('/admin/products.php');
        } catch (Exception $ex) {
            $errors[] = 'An error occurred while saving the product: ' . $ex->getMessage();
        }
    }

    if (!empty($errors)) {
        old_set($_POST);
    }
}

$categories = db()->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();

$pageTitle = 'Add Product - ' . SITE_NAME;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-banner">
    <h2>Add Product</h2>
    <p class="text-muted-custom mb-0">Create a new product listing.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="form-box">
    <form action="/admin/add-product.php" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= old('name') ?>">
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"><?= old('description') ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Price (R)</label>
                <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= old('price', '0') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label>Stock Quantity</label>
                <input type="number" min="0" name="stock_quantity" class="form-control" value="<?= old('stock_quantity', '0') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <select name="category_id" class="form-select">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="newCategoryName">Or Create a New Category</label>
            <input type="text" id="newCategoryName" name="new_category_name" class="form-control" placeholder="e.g. Brake Pads">
            <small class="text-muted-custom">Type a name here to add a new category instead of picking one above. If it already exists, the existing category will be used.</small>
        </div>

        <div class="mb-3">
            <label>Product Image</label>
            <input type="file" name="image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.tif,.tiff,.ico,.heic,.heif,.avif,image/*">
            <small class="text-muted-custom">Any common image format (JPG, PNG, WEBP, GIF, BMP, TIFF, HEIC, AVIF, ICO), up to 8 MB.</small>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="is_featured" id="isFeatured" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isFeatured">Featured Product</label>
        </div>

        <button type="submit" class="btn btn-dani-primary">Save Product</button>
        <a href="/admin/products.php" class="btn btn-dani-outline">Cancel</a>
    </form>
</div>

<?php old_clear(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
