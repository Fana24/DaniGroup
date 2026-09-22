<?php
/**
 * Central configuration for the Dani Group PHP site.
 *
 * IMPORTANT: Fill in the database and Yoco values below before you upload
 * this to your cPanel hosting. See DEPLOY-README.md for step-by-step
 * instructions on creating the database in cPanel.
 */

// ---------------------------------------------------------------------
// Database connection (cPanel: MySQL Databases -> create DB + user)
// cPanel usually prefixes both the database name and the DB user with
// your cPanel username, e.g. "myuser_danigroup" and "myuser_dbuser".
// ---------------------------------------------------------------------
if (file_exists(__DIR__ . '/../.env.php')) {
    require __DIR__ . '/../.env.php';
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_danigroup');
define('DB_USER', 'cpaneluser_dbuser');
define('DB_PASS', 'CHANGE_ME');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// Site basics
// ---------------------------------------------------------------------
define('SITE_NAME', 'Dani Group');
define('CURRENCY_PREFIX', 'R '); // South African Rand
define('SITE_TIMEZONE', 'Africa/Johannesburg');

// Base URL of the site, no trailing slash. Used for absolute links in
// emails and for building Yoco success/cancel redirect URLs.
// Example: https://www.yourdomain.co.za
define('SITE_BASE_URL', 'https://www.danigroup.co.za');

// ---------------------------------------------------------------------
// File uploads
// ---------------------------------------------------------------------
define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024); // 8 MB - raised from 2 MB to comfortably fit larger formats like BMP/TIFF
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'heif', 'avif']);
define('UPLOAD_ALLOWED_MIME', [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
    'image/bmp', 'image/x-ms-bmp',
    'image/tiff', 'image/x-tiff',
    'image/x-icon', 'image/vnd.microsoft.icon',
    'image/heic', 'image/heif',
    'image/avif',
]);
// NOTE: SVG is intentionally not allowed. Unlike the formats above, an SVG
// file is XML and can embed <script> tags - uploading one straight through
// to a public folder is a real cross-site-scripting risk. If you need SVG
// support later, it needs to go through a sanitizer first, not just an
// extension/MIME check like the other formats.

// ---------------------------------------------------------------------
// Yoco payment gateway
// Get these from your Yoco merchant portal (https://portal.yoco.com).
// IMPORTANT: exactly like the original ASP.NET Core app, the Yoco
// checkout payload/response field names and the webhook signature
// scheme should be double-checked against the current Yoco API docs
// for your account before going live with real payments.
// ---------------------------------------------------------------------
if (file_exists(__DIR__ . '/../.env.php')) {
    require __DIR__ . '/../.env.php';
}
define('YOCO_SECRET_KEY', getenv('YOCO_SECRET_KEY') ?: '');
define('YOCO_PUBLIC_KEY', getenv('YOCO_PUBLIC_KEY') ?: '');
define('YOCO_WEBHOOK_SECRET', getenv('YOCO_WEBHOOK_SECRET') ?: '');
define('YOCO_API_BASE_URL', 'https://payments.yoco.com/api');

// ---------------------------------------------------------------------
// Outgoing email (contact form confirmations, order notices, etc.)
// PHP's built-in mail() uses the server's local sendmail/exim on
// cPanel, which normally works out of the box for a cPanel-hosted
// domain without any extra setup.
// ---------------------------------------------------------------------
define('MAIL_FROM_ADDRESS', 'no-reply@danigroup.co.za');
define('MAIL_FROM_NAME', 'Dani Group');

// ---------------------------------------------------------------------
// Error display - turn OFF once the site is live on cPanel.
// ---------------------------------------------------------------------
define('APP_DEBUG', true);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

date_default_timezone_set(SITE_TIMEZONE);
