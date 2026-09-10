<?php
/** FILE: order_details.php */
require_once __DIR__ . '/backend/functions.php';
require_login();

$orderId = (int)($_GET['id'] ?? 0);

// A customer may only open their own orders; admins may open any.
$sql = 'SELECT o.*, u.name, u.email, u.phone, u.address,
               p.payment_method, p.payment_status, p.transaction_reference, p.payment_date
          FROM orders o
          JOIN users u ON u.user_id = o.user_id
     LEFT JOIN payments p ON p.order_id = o.order_id
         WHERE o.order_id = ?';
$params = [$orderId];
if (!is_admin()) {
    $sql .= ' AND o.user_id = ?';
    $params[] = current_user_id();
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'That order could not be found.');
    redirect('orders.php');
}
$pageTitle = 'Order #' . $orderId;

$lines = $pdo->prepare(
    'SELECT oi.quantity, oi.price, g.game_id, g.title, g.cover_image,
            (oi.quantity * oi.price) AS line_total
       FROM order_items oi JOIN games g ON g.game_id = oi.game_id
      WHERE oi.order_id = ?'
);
$lines->execute([$orderId]);
$items = $lines->fetchAll();

$keys = $pdo->prepare('SELECT game_id, license_key FROM library WHERE order_id = ? AND user_id = ?');
$keys->execute([$orderId, (int)$order['user_id']]);
$licenses = $keys->fetchAll(PDO::FETCH_KEY_PAIR);

require __DIR__ . '/backend/header.php';
?>
<div class="wrap breadcrumb">
  <a href="<?= url('orders.php') ?>">My orders</a> / Order #<?= (int)$orderId ?>
</div>

<div class="wrap page-head" style="display:flex;justify-content:space-between;align-items:flex-end;gap:14px;flex-wrap:wrap">
  <div>
    <h1>Order #<?= (int)$orderId ?></h1>
    <p>Placed <?= date('d M Y, g:i a', strtotime($order['order_date'])) ?></p>
  </div>
  <span class="status status-<?= e($order['order_status']) ?>" style="font-size:.9rem;padding:7px 16px">
    <?= e($order['order_status']) ?>
  </span>
</div>

<div class="wrap cart-layout">
  <div class="panel">
    <h3>Items in this order</h3>
    <?php foreach ($items as $item): ?>
      <div class="cart-item">
        <img src="<?= cover_url($item['cover_image']) ?>" alt="<?= e($item['title']) ?>">
        <div>
          <h4><a href="<?= url('game_details.php?id=' . (int)$item['game_id']) ?>"><?= e($item['title']) ?></a></h4>
          <p class="card-meta"><?= money($item['price']) ?> &times; <?= (int)$item['quantity'] ?></p>
          <?php if (isset($licenses[$item['game_id']])): ?>
            <p style="margin-top:7px;font-size:.83rem">
              <span style="color:var(--text-faint)">Licence key</span>
              <code style="color:var(--blue)"><?= e($licenses[$item['game_id']]) ?></code>
            </p>
          <?php endif; ?>
        </div>
        <div class="price"><?= money($item['line_total']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div>
    <div class="panel">
      <h3>Payment</h3>
      <div class="summary-line"><span>Method</span><span><?= e(ucfirst(str_replace('_', ' ', (string)$order['payment_method']))) ?></span></div>
      <div class="summary-line"><span>Status</span>
        <span class="status status-<?= e($order['payment_status']) ?>"><?= e($order['payment_status']) ?></span>
      </div>
      <div class="summary-line"><span>Reference</span><span style="font-size:.8rem"><?= e($order['transaction_reference']) ?></span></div>
      <div class="summary-line total"><span>Paid</span><span><?= money($order['total_amount']) ?></span></div>
    </div>

    <div class="panel">
      <h3>Customer</h3>
      <div class="summary-line"><span>Name</span><span><?= e($order['name']) ?></span></div>
      <div class="summary-line"><span>Email</span><span style="font-size:.83rem"><?= e($order['email']) ?></span></div>
      <?php if ($order['phone']): ?>
        <div class="summary-line"><span>Phone</span><span><?= e($order['phone']) ?></span></div>
      <?php endif; ?>
      <?php if ($order['address']): ?>
        <div class="summary-line"><span>Address</span><span><?= e($order['address']) ?></span></div>
      <?php endif; ?>
    </div>

    <a class="btn btn-primary btn-block" href="<?= url('library.php') ?>">Open my library</a>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
