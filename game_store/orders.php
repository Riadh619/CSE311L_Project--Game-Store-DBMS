<?php
/** FILE: orders.php - the logged in customer's order history */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'My orders';

$stmt = $pdo->prepare(
    'SELECT order_id, order_date, order_status, total_amount, line_items, total_units,
            payment_method, payment_status
       FROM order_summary_view
      WHERE user_id = ?
      ORDER BY order_date DESC'
);
$stmt->execute([current_user_id()]);
$orders = $stmt->fetchAll();

$spend = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount),0) FROM orders
      WHERE user_id = ? AND order_status IN ('paid','completed')"
);
$spend->execute([current_user_id()]);
$lifetime = $spend->fetchColumn();

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Order history</h1>
  <p><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?> &middot; <?= money($lifetime) ?> spent in total.</p>
</div>

<div class="wrap">
<?php if (!$orders): ?>
  <div class="panel empty">
    <div class="glyph">&#128230;</div>
    <h3>No orders yet</h3>
    <p>Once you buy something it shows up here.</p>
    <a class="btn btn-primary" href="<?= url('games.php') ?>">Browse games</a>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Order</th><th>Date</th><th>Items</th><th>Total</th>
          <th>Payment</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong>#<?= (int)$o['order_id'] ?></strong></td>
          <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
          <td><?= (int)$o['total_units'] ?> unit<?= (int)$o['total_units'] === 1 ? '' : 's' ?></td>
          <td><?= money($o['total_amount']) ?></td>
          <td><?= e(ucfirst(str_replace('_', ' ', (string)$o['payment_method']))) ?></td>
          <td><span class="status status-<?= e($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
          <td><a class="btn btn-ghost btn-sm" href="<?= url('order_details.php?id=' . (int)$o['order_id']) ?>">Details</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
