<?php
/**
 * Session-based authentication, replacing ASP.NET Core Identity.
 *
 * On successful login we store the user's id, email, full name and role
 * in the session. current_user() re-reads the full row from the
 * database the first time it's called on a request, then caches it.
 */

function login_user(array $userRow): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $userRow['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, full_name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // The account no longer exists (e.g. deleted) - clear the stale session.
        logout_user();
        return null;
    }

    $cached = $user;
    return $cached;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'Admin';
}

// Call at the top of any page that requires a logged-in user.
// Redirects to the login page and returns afterwards via ?return=.
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        $return = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        redirect(base_path('account/login.php') . '?return=' . $return);
    }
    return $user;
}

// Call at the top of any admin/*.php page.
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'Admin') {
        http_response_code(403);
        die('You do not have permission to view this page.');
    }
    return $user;
}

function register_user(string $fullName, string $email, string $password): array
{
    $email = strtolower(trim($email));

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'An account with that email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$fullName, $email, $hash, 'Customer']);

    $userId = (int) db()->lastInsertId();

    return ['ok' => true, 'user_id' => $userId];
}

function attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }

    login_user($user);
    return ['ok' => true, 'user' => $user];
}

// Resolve a path relative to the site root, regardless of which
// subfolder (account/, admin/, orders/, ...) the current page lives in.
function base_path(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}
