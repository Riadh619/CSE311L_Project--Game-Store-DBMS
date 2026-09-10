<?php
/**
 * FILE: backend/checkout_process.php
 * Simulated payment + order creation, wrapped in a single MySQL transaction.
 *
 * One transaction performs all of this, or none of it:
 *   1. lock and re-check stock for every cart line
 *   2. insert orders
 *   3. insert order_items at the discounted price
 *   4. insert the payment record
 *   5. reduce games.stock
 *   6. copy purchased games into the library
 *   7. empty the cart
 */
require_once __DIR__ . '/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('cart.php');
verify_csrf();

$userId = current_user_id();
$method = $_POST['payment_method'] ?? '';
$allowedMethods = ['card', 'bkash', 'nagad', 'paypal', 'cash_on_delivery'];

if (!in_array($method, $allowedMethods, true)) {
    set_flash('error', 'Choose a payment method.');
    redirect('checkout.php');
}

$items = cart_rows($pdo, $userId);
if (!$items) {
    set_flash('error', 'Your cart is empty.');
    redirect('cart.php');
}

try {
    $pdo->beginTransaction();

    // 1. Re-check stock inside the transaction (SELECT ... FOR UPDATE locks the rows)
    $stockStmt = $pdo->prepare('SELECT title, stock, status FROM games WHERE game_id = ? FOR UPDATE');
    $total = 0.0;

    foreach ($items as $item) {
        $stockStmt->execute([$item['game_id']]);
        $game = $stockStmt->fetch();

        if (!$game || $game['status'] !== 'active') {
            throw new RuntimeException($item['title'] . ' is no longer on sale.');
        }
        if ((int)$game['stock'] < (int)$item['quantity']) {
            throw new RuntimeException(
                'Not enough stock for ' . $game['title'] . '. Only ' . $game['stock'] . ' left.'
            );
        }
        $total += (float)$item['final_price'] * (int)$item['quantity'];
    }
    $total = round($total, 2);

    // 2. Order header
    $pdo->prepare('INSERT INTO orders (user_id, total_amount, order_status) VALUES (?, ?, "paid")')
        ->execute([$userId, $total]);
    $orderId = (int)$pdo->lastInsertId();

    // 3. Order lines at the price actually charged
    $lineStmt  = $pdo->prepare('INSERT INTO order_items (order_id, game_id, quantity, price) VALUES (?, ?, ?, ?)');
    // 5. Stock reduction
    $stockDown = $pdo->prepare('UPDATE games SET stock = stock - ? WHERE game_id = ? AND stock >= ?');
    // 6. Library entry (ON DUPLICATE keeps re-buying a game from breaking the unique key)
    $libStmt   = $pdo->prepare(
        'INSERT INTO library (user_id, game_id, order_id, license_key, download_url)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), acquired_at = NOW()'
    );

    foreach ($items as $item) {
        $lineStmt->execute([$orderId, $item['game_id'], $item['quantity'], $item['final_price']]);

        $stockDown->execute([$item['quantity'], $item['game_id'], $item['quantity']]);
        if ($stockDown->rowCount() === 0) {
            throw new RuntimeException('Stock changed while checking out. Please review your cart.');
        }

        $license = 'GS-' . strtoupper(bin2hex(random_bytes(2))) . '-'
                        . strtoupper(bin2hex(random_bytes(2))) . '-'
                        . strtoupper(bin2hex(random_bytes(2)));
        $libStmt->execute([$userId, $item['game_id'], $orderId, $license, 'downloads/game-' . $item['game_id'] . '.zip']);
    }

    // 4. Payment record (simulated gateway response)
    $reference = 'TXN-' . date('Ymd') . '-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
    $status    = $method === 'cash_on_delivery' ? 'pending' : 'success';

    $pdo->prepare(
        'INSERT INTO payments (order_id, payment_method, payment_status, transaction_reference, amount)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$orderId, $method, $status, $reference, $total]);

    if ($method === 'cash_on_delivery') {
        $pdo->prepare('UPDATE orders SET order_status = "pending" WHERE order_id = ?')->execute([$orderId]);
    }

    // 7. Empty the cart
    $pdo->prepare('DELETE ci FROM cart_items ci JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ?')
        ->execute([$userId]);

    $pdo->commit();

    set_flash('success', 'Payment accepted. Order #' . $orderId . ' is confirmed and your games are in your library.');
    redirect('order_details.php?id=' . $orderId);

} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Checkout cancelled: ' . $ex->getMessage());
    redirect('cart.php');
}
