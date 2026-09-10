<?php
/**
 * FILE: backend/functions.php
 * Session handling, authentication guards, escaping and shared query helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

/* ------------------------------------------------------------------ */
/* Output escaping and small formatting helpers                        */
/* ------------------------------------------------------------------ */

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return '$' . number_format((float)$amount, 2);
}

function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function redirect($path) {
    header('Location: ' . (preg_match('~^https?://~', $path) ? $path : url($path)));
    exit;
}

function cover_url($file) {
    $file = trim((string)$file);
    if ($file !== '' && file_exists(__DIR__ . '/../images/' . $file)) {
        return url('images/' . $file);
    }
    return url('images/placeholder.svg');
}

function star_html($rating) {
    $rating = (float)$rating;
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= round($rating) ? 'star on' : 'star';
        $out .= '<span class="' . $class . '">&#9733;</span>';
    }
    return $out;
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' h ago';
    if ($diff < 604800) return floor($diff / 86400) . ' d ago';
    return date('d M Y', strtotime($datetime));
}

/* ------------------------------------------------------------------ */
/* Flash messages                                                      */
/* ------------------------------------------------------------------ */

function set_flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ------------------------------------------------------------------ */
/* CSRF protection                                                     */
/* ------------------------------------------------------------------ */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf() {
    $sent = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(403);
        die('Security check failed. Reload the page and try again.');
    }
}

/* ------------------------------------------------------------------ */
/* Authentication and authorisation                                    */
/* ------------------------------------------------------------------ */

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function current_user_id() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_name() {
    return $_SESSION['user_name'] ?? '';
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        set_flash('error', 'Log in to continue.');
        redirect('login.php');
    }
}

function require_admin() {
    if (!is_admin()) {
        set_flash('error', 'Admin access only.');
        redirect('admin/login.php');
    }
}

/* ------------------------------------------------------------------ */
/* Validation                                                          */
/* ------------------------------------------------------------------ */

/**
 * Character count that works whether or not the mbstring extension is loaded.
 * XAMPP enables mbstring by default, but this keeps the project portable.
 */
function str_len($value) {
    return function_exists('mb_strlen') ? mb_strlen((string)$value, 'UTF-8') : strlen((string)$value);
}

function clean($value) {
    return trim((string)$value);
}

function valid_email($email) {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/* ------------------------------------------------------------------ */
/* Cart helpers                                                        */
/* ------------------------------------------------------------------ */

function get_or_create_cart(PDO $pdo, $userId) {
    $stmt = $pdo->prepare('SELECT cart_id FROM carts WHERE user_id = ?');
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();
    if ($cartId) {
        return (int)$cartId;
    }
    $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)')->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

function cart_count(PDO $pdo, $userId) {
    if (!$userId) return 0;
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(ci.quantity), 0)
           FROM cart_items ci
           JOIN carts c ON c.cart_id = ci.cart_id
          WHERE c.user_id = ?'
    );
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function wishlist_count(PDO $pdo, $userId) {
    if (!$userId) return 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function cart_rows(PDO $pdo, $userId) {
    $stmt = $pdo->prepare(
        'SELECT ci.cart_item_id, ci.quantity, v.game_id, v.title, v.cover_image, v.price,
                v.final_price, v.discount_percentage, v.stock, v.genres,
                ROUND(v.final_price * ci.quantity, 2) AS line_total
           FROM cart_items ci
           JOIN carts c            ON c.cart_id = ci.cart_id
           JOIN game_catalog_view v ON v.game_id = ci.game_id
          WHERE c.user_id = ?
          ORDER BY ci.added_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function cart_totals(array $rows) {
    $subtotal = 0.0;
    $savings  = 0.0;
    foreach ($rows as $r) {
        $subtotal += (float)$r['price'] * (int)$r['quantity'];
        $savings  += ((float)$r['price'] - (float)$r['final_price']) * (int)$r['quantity'];
    }
    return [
        'subtotal' => round($subtotal, 2),
        'savings'  => round($savings, 2),
        'total'    => round($subtotal - $savings, 2),
    ];
}

/* ------------------------------------------------------------------ */
/* Ownership checks                                                    */
/* ------------------------------------------------------------------ */

function owns_game(PDO $pdo, $userId, $gameId) {
    if (!$userId) return false;
    $stmt = $pdo->prepare('SELECT 1 FROM library WHERE user_id = ? AND game_id = ?');
    $stmt->execute([$userId, $gameId]);
    return (bool)$stmt->fetchColumn();
}

function in_wishlist(PDO $pdo, $userId, $gameId) {
    if (!$userId) return false;
    $stmt = $pdo->prepare('SELECT 1 FROM wishlists WHERE user_id = ? AND game_id = ?');
    $stmt->execute([$userId, $gameId]);
    return (bool)$stmt->fetchColumn();
}

/* ------------------------------------------------------------------ */
/* Catalogue helpers                                                   */
/* ------------------------------------------------------------------ */

function all_genres(PDO $pdo)     { return $pdo->query('SELECT * FROM genres ORDER BY genre_name')->fetchAll(); }
function all_platforms(PDO $pdo)  { return $pdo->query('SELECT * FROM platforms ORDER BY platform_name')->fetchAll(); }
function all_categories(PDO $pdo) { return $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll(); }
function all_developers(PDO $pdo) { return $pdo->query('SELECT * FROM developers ORDER BY developer_name')->fetchAll(); }
function all_publishers(PDO $pdo) { return $pdo->query('SELECT * FROM publishers ORDER BY publisher_name')->fetchAll(); }
