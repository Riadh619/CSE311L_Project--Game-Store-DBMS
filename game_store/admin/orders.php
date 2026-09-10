<?php
/** FILE: admin/orders.php - review and update every order */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status  = $_POST['order_status'] ?? '';
    $allowed = ['pending', 'paid', 'completed', 'cancelled'];

    if (in_array($status, $allowed, true)) {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_id = ?')->execute([$status, $orderId]);

        // Keep the payment row in step with the order
        $payStatus = $status === 'cancelled' ? 'refunded' : ($status === 'pending' ? 'pending' : 'success');
        $pdo->prepare('UPDATE payments SET payment_status = ? WHERE order_id = ?')->execute([$payStatus, $orderId]);
        $pdo->commit();

        set_flash('success', 'Order #' . $orderId . ' set to ' . $status . '.');
    }
    redirect('admin/orders.php');
}

$pageTitle = 'Orders';
$filter = $_GET['status'] ?? '';

$sql = 'SELECT * FROM order_summary_view';
$params = [];
if (in_array($filter, ['pending','paid','completed','cancelled'], true)) {
    $sql .= ' WHERE order_status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY order_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$totals = $pdo->query(
    "SELECT COUNT(*) AS all_orders,
            SUM(order_status = 'pending')   AS pending,
            SUM(order_status = 'paid')      AS paid,
            SUM(order_status = 'completed') AS completed,
            SUM(order_status = 'cancelled') AS cancelled
       FROM orders"
)->fetch();

require __DIR__ . '/header.php';
?>
<div class="tab-row">
  <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="<?= url('admin/orders.php') ?>">All (<?= (int)$totals['all_orders'] ?>)</a>
  <a class="chip <?= $filter === 'pending' ? 'active' : '' ?>" href="<?= url('admin/orders.php?status=pending') ?>">Pending (<?= (int)$totals['pending'] ?>)</a>
  <a class="chip <?= $filter === 'paid' ? 'active' : '' ?>" href="<?= url('admin/orders.php?status=paid') ?>">Paid (<?= (int)$totals['paid'] ?>)</a>
  <a class="chip <?= $filter === 'completed' ? 'active' : '' ?>" href="<?= url('admin/orders.php?status=completed') ?>">Completed (<?= (int)$totals['completed'] ?>)</a>
  <a class="chip <?= $filter === 'cancelled' ? 'active' : '' ?>" href="<?= url('admin/orders.php?status=cancelled') ?>">Cancelled (<?= (int)$totals['cancelled'] ?>)</a>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr><th>#</th><th>Customer</th><th>Date</th><th>Units</th><th>Total</th>
          <th>Payment</th><th>Status</th><th>Update</th></tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="<?= url('order_details.php?id=' . (int)$o['order_id']) ?>"><strong>#<?= (int)$o['order_id'] ?></strong></a></td>
        <td>
          <?= e($o['customer_name']) ?>
          <div style="font-size:.78rem;color:var(--text-faint)"><?= e($o['customer_email']) ?></div>
        </td>
        <td style="font-size:.85rem"><?= date('d M Y', strtotime($o['order_date'])) ?></td>
        <td><?= (int)$o['total_units'] ?></td>
        <td><strong><?= money($o['total_amount']) ?></strong></td>
        <td style="font-size:.83rem">
          <?= e(ucfirst(str_replace('_', ' ', (string)$o['payment_method']))) ?>
          <div><span class="status status-<?= e($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></div>
        </td>
        <td><span class="status status-<?= e($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
        <td>
          <form class="inline-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
            <select class="input" name="order_status" style="width:auto;padding:7px 10px;font-size:.85rem">
              <?php foreach (['pending','paid','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $o['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
