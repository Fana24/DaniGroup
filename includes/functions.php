<?php
/**
 * General-purpose helpers used across the site.
 */

// Escape output for safe HTML rendering.
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Format a number as South African Rand, matching the original site's "R 123.45" style.
function money($amount): string
{
    return CURRENCY_PREFIX . number_format((float) $amount, 2);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------------
// One-time flash messages (success / error banners), stored in session
// and cleared after being read once, e.g. after a redirect.
// ---------------------------------------------------------------------
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function render_flash_messages(): void
{
    $success = flash_get('success');
    $error   = flash_get('error');

    if ($success !== null) {
        echo '<div class="alert alert-success">' . e($success) . '</div>';
    }
    if ($error !== null) {
        echo '<div class="alert alert-danger">' . e($error) . '</div>';
    }
}

// ---------------------------------------------------------------------
// Re-populating a form after a validation error, without losing what
// the visitor already typed. Call old_set($_POST) before re-showing
// the form, and old('fieldName') inside the form's value="" attributes.
// ---------------------------------------------------------------------
function old_set(array $data): void
{
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function old_clear(): void
{
    unset($_SESSION['old']);
}

// ---------------------------------------------------------------------
// CSRF protection - one token per session, checked on every POST form.
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Your session expired or this form was submitted incorrectly. Please go back and try again.');
    }
}

// ---------------------------------------------------------------------
// Image upload validation + save. Accepts any common picture format
// (see UPLOAD_ALLOWED_EXT/MIME in config.php) up to UPLOAD_MAX_BYTES.
// SVG is deliberately excluded - see the note next to UPLOAD_ALLOWED_EXT
// in config.php for why.
// Returns the web-relative path (e.g. "/assets/uploads/products/xxx.jpg")
// on success, or null if no file was uploaded. Throws a RuntimeException
// with a user-friendly message if the file fails validation.
// ---------------------------------------------------------------------
function save_uploaded_image(string $fieldName, string $subfolder): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    $maxMb = number_format(UPLOAD_MAX_BYTES / 1024 / 1024, 0);
    $friendlyFormats = strtoupper(implode(', ', UPLOAD_ALLOWED_EXT));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file could not be uploaded. Please try again.');
    }

    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException("Images must be {$maxMb} MB or smaller.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT, true)) {
        throw new RuntimeException("That file type isn't supported. Allowed formats: {$friendlyFormats}.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, UPLOAD_ALLOWED_MIME, true)) {
        throw new RuntimeException("That file doesn't look like a valid image. Allowed formats: {$friendlyFormats}.");
    }

    $uploadDir = __DIR__ . '/../assets/uploads/' . $subfolder;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The file could not be saved. Please try again.');
    }

    return '/assets/uploads/' . $subfolder . '/' . $fileName;
}

// Simple wrapper around PHP's mail(); on cPanel this uses the server's
// local mail transport and normally needs no extra configuration.
function send_site_email(string $to, string $subject, string $htmlBody): bool
{
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>' . "\r\n";

    return @mail($to, $subject, $htmlBody, $headers);
}

function base_url(string $path = ''): string
{
    return rtrim(SITE_BASE_URL, '/') . '/' . ltrim($path, '/');
}

// Ports AdminController.cs' ApplyNewCategoryIfProvided: lets the admin
// type a brand new category name on the Add/Edit product forms instead
// of being limited to the existing dropdown list. Returns the resolved
// category id, or null if no name was given.
function apply_new_category_if_provided(?string $newCategoryName): ?int
{
    if ($newCategoryName === null || trim($newCategoryName) === '') {
        return null;
    }

    $trimmed = trim($newCategoryName);

    $stmt = db()->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(?)');
    $stmt->execute([$trimmed]);
    $existing = $stmt->fetch();

    if ($existing) {
        return (int) $existing['id'];
    }

    $stmt = db()->prepare('INSERT INTO categories (name) VALUES (?)');
    $stmt->execute([$trimmed]);
    return (int) db()->lastInsertId();
}
