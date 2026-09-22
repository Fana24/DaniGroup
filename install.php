<?php
/**
 * ONE-TIME SETUP SCRIPT.
 *
 * Run this once in your browser (e.g. https://yourdomain.co.za/install.php)
 * after you have:
 *   1. Created the MySQL database + user in cPanel
 *   2. Filled in includes/config.php with those DB details
 *   3. Imported schema.sql via phpMyAdmin
 *
 * It creates the admin login and seeds starter categories/products,
 * exactly like the original app's DbInitializer did. DELETE THIS FILE
 * from the server immediately after running it once.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$log = [];

// --- Admin account -----------------------------------------------------
$adminEmail    = 'admin@danigroup.com';
$adminPassword = 'Admin@12345';

$stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$adminEmail]);

if (!$stmt->fetch()) {
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute(['Site Admin', $adminEmail, $hash, 'Admin']);
    $log[] = "Created admin account: $adminEmail / $adminPassword (change this password after logging in).";
} else {
    $log[] = "Admin account already exists ($adminEmail) - left unchanged.";
}

// --- Starter categories -------------------------------------------------
$categoryNames = ['Car Parts', 'Accessories', 'Bike Parts'];
$categoryIds = [];

foreach ($categoryNames as $name) {
    $stmt = db()->prepare('SELECT id FROM categories WHERE name = ?');
    $stmt->execute([$name]);
    $row = $stmt->fetch();

    if ($row) {
        $categoryIds[$name] = (int) $row['id'];
    } else {
        $stmt = db()->prepare('INSERT INTO categories (name) VALUES (?)');
        $stmt->execute([$name]);
        $categoryIds[$name] = (int) db()->lastInsertId();
        $log[] = "Created category: $name";
    }
}

// --- Starter products -----------------------------------------------------
$countStmt = db()->query('SELECT COUNT(*) FROM products');
if ((int) $countStmt->fetchColumn() === 0) {
    $sampleProducts = [
        ['Brake Pads Set', 'High quality brake pads for reliable stopping performance.', 89.99, 20, $categoryIds['Car Parts'], 1],
        ['Steering Wheel Cover', 'Premium black and orange steering wheel cover.', 24.99, 50, $categoryIds['Accessories'], 1],
        ['Bike Chain Kit', 'Durable replacement chain kit for bike maintenance.', 39.99, 35, $categoryIds['Bike Parts'], 1],
    ];

    $stmt = db()->prepare(
        'INSERT INTO products (name, description, price, stock_quantity, category_id, is_featured) VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($sampleProducts as $p) {
        $stmt->execute($p);
    }
    $log[] = 'Seeded 3 starter products.';
} else {
    $log[] = 'Products table already has data - left unchanged.';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Dani Group - Setup</title></head>
<body style="font-family: sans-serif; max-width: 640px; margin: 40px auto; line-height: 1.6;">
<h1>Setup complete</h1>
<ul>
<?php foreach ($log as $line): ?>
    <li><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
</ul>
<p><strong>Important:</strong> delete <code>install.php</code> from your server now. Leaving it online lets
anyone re-run it.</p>
<p><a href="/index.php">Go to the site</a></p>
</body>
</html>
