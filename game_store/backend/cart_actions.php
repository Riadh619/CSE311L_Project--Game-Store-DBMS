<?php
/**
 * FILE: backend/cart_actions.php
 * add / increase / decrease / remove / clear cart operations.
 * Responds with JSON when called by fetch(), otherwise redirects back.
 */
require_once __DIR__ . '/functions.php';

$isAjax = !empty($_POST['ajax']);

function respond($ok, $message, $extra = []) {
    global $isAjax, $pdo;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'ok'         => $ok,
            'message'    => $message,
            'cart_count' => cart_count($pdo, current_user_id()),
        ], $extra));
        exit;
    }
    set_flash($ok ? 'success' : 'error', $message);
    redirect($_POST['return'] ?? 'cart.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}
verify_csrf();

if (!is_logged_in()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'login_required' => true, 'message' => 'Log in to use the cart.']);
        exit;
    }
    $_SESSION['redirect_after_login'] = $_POST['return'] ?? url('cart.php');
    set_flash('error', 'Log in to use the cart.');
    redirect('login.php');
}

$userId = current_user_id();
$cartId = get_or_create_cart($pdo, $userId);
$action = $_POST['action'] ?? '';
$gameId = (int)($_POST['game_id'] ?? 0);

switch ($action) {

    case 'add':
        $qty = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $pdo->prepare('SELECT title, stock, status FROM games WHERE game_id = ?');
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if (!$game || $game['status'] !== 'active') {
            respond(false, 'That game is not available.');
        }
        if ((int)$game['stock'] < 1) {
            respond(false, $game['title'] . ' is out of stock.');
        }

        $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE cart_id = ? AND game_id = ?');
        $stmt->execute([$cartId, $gameId]);
        $existing = (int)$stmt->fetchColumn();

        if ($existing + $qty > (int)$game['stock']) {
            respond(false, 'Only ' . $game['stock'] . ' copies of ' . $game['title'] . ' left in stock.');
        }

        if ($existing) {
            $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE cart_id = ? AND game_id = ?')
                ->execute([$qty, $cartId, $gameId]);
        } else {
            $pdo->prepare('INSERT INTO cart_items (cart_id, game_id, quantity) VALUES (?, ?, ?)')
                ->execute([$cartId, $gameId, $qty]);
        }
        respond(true, $game['title'] . ' added to your cart.');
        break;

    case 'increase':
        $stmt = $pdo->prepare(
            'SELECT ci.quantity, g.stock, g.title
               FROM cart_items ci JOIN games g ON g.game_id = ci.game_id
              WHERE ci.cart_id = ? AND ci.game_id = ?'
        );
        $stmt->execute([$cartId, $gameId]);
        $row = $stmt->fetch();
        if (!$row) respond(false, 'That item is not in your cart.');

        if ((int)$row['quantity'] + 1 > (int)$row['stock']) {
            respond(false, 'Stock limit reached for ' . $row['title'] . '.');
        }
        $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE cart_id = ? AND game_id = ?')
            ->execute([$cartId, $gameId]);
        respond(true, 'Quantity updated.');
        break;

    case 'decrease':
        $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE cart_id = ? AND game_id = ?');
        $stmt->execute([$cartId, $gameId]);
        $qty = (int)$stmt->fetchColumn();

        if ($qty <= 1) {
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND game_id = ?')->execute([$cartId, $gameId]);
            respond(true, 'Item removed from cart.');
        }
        $pdo->prepare('UPDATE cart_items SET quantity = quantity - 1 WHERE cart_id = ? AND game_id = ?')
            ->execute([$cartId, $gameId]);
        respond(true, 'Quantity updated.');
        break;

    case 'remove':
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND game_id = ?')->execute([$cartId, $gameId]);
        respond(true, 'Item removed from cart.');
        break;

    case 'clear':
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
        respond(true, 'Cart cleared.');
        break;

    default:
        respond(false, 'Unknown cart action.');
}
