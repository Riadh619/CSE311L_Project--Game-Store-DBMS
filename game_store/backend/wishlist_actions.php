<?php
/**
 * FILE: backend/wishlist_actions.php
 * add / remove / move to cart. Duplicate rows are blocked by a UNIQUE key
 * on (user_id, game_id) and by INSERT IGNORE here.
 */
require_once __DIR__ . '/functions.php';

$isAjax = !empty($_POST['ajax']);

function wl_respond($ok, $message, $extra = []) {
    global $isAjax, $pdo;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'ok'             => $ok,
            'message'        => $message,
            'wishlist_count' => wishlist_count($pdo, current_user_id()),
            'cart_count'     => cart_count($pdo, current_user_id()),
        ], $extra));
        exit;
    }
    set_flash($ok ? 'success' : 'error', $message);
    redirect($_POST['return'] ?? 'wishlist.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('wishlist.php');
verify_csrf();

if (!is_logged_in()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'login_required' => true, 'message' => 'Log in to use your wishlist.']);
        exit;
    }
    $_SESSION['redirect_after_login'] = $_POST['return'] ?? url('wishlist.php');
    set_flash('error', 'Log in to use your wishlist.');
    redirect('login.php');
}

$userId = current_user_id();
$gameId = (int)($_POST['game_id'] ?? 0);
$action = $_POST['action'] ?? '';

switch ($action) {

    case 'add':
        $stmt = $pdo->prepare('SELECT title FROM games WHERE game_id = ? AND status = "active"');
        $stmt->execute([$gameId]);
        $title = $stmt->fetchColumn();
        if (!$title) wl_respond(false, 'That game is not available.');

        $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, game_id) VALUES (?, ?)');
        $stmt->execute([$userId, $gameId]);

        if ($stmt->rowCount() === 0) {
            wl_respond(true, $title . ' is already on your wishlist.', ['already' => true, 'in_wishlist' => true]);
        }
        wl_respond(true, $title . ' saved to your wishlist.', ['in_wishlist' => true]);
        break;

    case 'remove':
        $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND game_id = ?')->execute([$userId, $gameId]);
        wl_respond(true, 'Removed from wishlist.', ['in_wishlist' => false]);
        break;

    case 'toggle':
        if (in_wishlist($pdo, $userId, $gameId)) {
            $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND game_id = ?')->execute([$userId, $gameId]);
            wl_respond(true, 'Removed from wishlist.', ['in_wishlist' => false]);
        }
        $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, game_id) VALUES (?, ?)')->execute([$userId, $gameId]);
        wl_respond(true, 'Saved to wishlist.', ['in_wishlist' => true]);
        break;

    case 'move_to_cart':
        $stmt = $pdo->prepare('SELECT title, stock FROM games WHERE game_id = ? AND status = "active"');
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();
        if (!$game)                 wl_respond(false, 'That game is not available.');
        if ((int)$game['stock'] < 1) wl_respond(false, $game['title'] . ' is out of stock.');

        $cartId = get_or_create_cart($pdo, $userId);
        $pdo->prepare(
            'INSERT INTO cart_items (cart_id, game_id, quantity) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + 1, ?)'
        )->execute([$cartId, $gameId, (int)$game['stock']]);

        $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND game_id = ?')->execute([$userId, $gameId]);
        wl_respond(true, $game['title'] . ' moved to your cart.');
        break;

    default:
        wl_respond(false, 'Unknown wishlist action.');
}
