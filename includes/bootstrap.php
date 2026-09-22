<?php
/**
 * Include this at the very top of every page:
 *   require_once __DIR__ . '/includes/bootstrap.php';          (root pages)
 *   require_once __DIR__ . '/../includes/bootstrap.php';       (subfolder pages)
 *
 * NOTE: This site uses root-absolute links everywhere (e.g. "/products.php",
 * "/assets/css/site.css"), so it must be deployed at the domain root
 * (cPanel's public_html folder), not in a sub-directory. See
 * DEPLOY-README.md if you need it in a sub-directory instead.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Cart item count for the header badge (logged-in users only).
$__cartCount = 0;
if (is_logged_in()) {
    $stmt = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([current_user()['id']]);
    $__cartCount = (int) $stmt->fetchColumn();
}
