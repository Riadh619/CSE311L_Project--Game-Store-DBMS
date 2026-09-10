<?php
/** FILE: admin/dashboard.php - store statistics */
$pageTitle = 'Dashboard';
require __DIR__ . '/header.php';

$totals = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM users WHERE role='customer')                                   AS customers,
            (SELECT COUNT(*) FROM games)                                                         AS games,
            (SELECT COUNT(*) FROM orders)                                                        AS orders,
            (SELECT COALESCE(SUM(total_amount),0) FROM orders
              WHERE order_status IN ('paid','completed'))                                        AS revenue,
            (SELECT COUNT(*) FROM reviews)                                                       AS reviews,
            (SELECT COUNT(*) FROM games WHERE stock <= 5)                                        AS low_stock,
            (SELECT COUNT(*) FROM orders WHERE order_status='pending')                           AS pending,
            (SELECT COALESCE(AVG(total_amount),0) FROM orders
              WHERE order_status IN ('paid','completed'))                                        AS avg_order"
)->fetch();

$popular = $pdo->query(
    'SELECT title, units_sold, revenue FROM sales_by_game_view
      ORDER BY units_sold DESC, revenue DESC LIMIT 6'
)->fetchAll();

$recentOrders = $pdo->query(
    'SELECT order_id, customer_name, total_amount, order_status, order_date
       FROM order_summary_view ORDER BY order_date DESC LIMIT 6'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT game_id, title, stock, price FROM games WHERE stock <= 5 ORDER BY stock ASC LIMIT 6'
)->fetchAll();

$recentUsers = $pdo->query(
    "SELECT user_id, name, email, created_at FROM users
      WHERE role='customer' ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$monthly = $pdo->query(
    "SELECT DATE_FORMAT(order_date,'%b %y') AS label, ROUND(SUM(total_amount),2) AS revenue
       FROM orders WHERE order_status IN ('paid','completed')
      GROUP BY YEAR(order_date), MONTH(order_date)
      ORDER BY YEAR(order_date), MONTH(order_date)"
)->fetchAll();
$maxRevenue = 0;
foreach ($monthly as $m) { $maxRevenue = max($maxRevenue, (float)$m['revenue']); }
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="label">Total revenue</div>
    <div class="value"><?= money($totals['revenue']) ?></div>
    <div class="sub">Average order <?= money($totals['avg_order']) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="label">Orders</div>
    <div class="value"><?= (int)$totals['orders'] ?></div>
    <div class="sub"><?= (int)$totals['pending'] ?> awaiting payment</div>
  </div>
  <div class="stat-card purple">
    <div class="label">Customers</div>
    <div class="value"><?= (int)$totals['customers'] ?></div>
    <div class="sub"><?= (int)$totals['reviews'] ?> reviews written</div>
  </div>
  <div class="stat-card orange">
    <div class="label">Games listed</div>
    <div class="value"><?= (int)$totals['games'] ?></div>
    <div class="sub"><?= (int)$totals['low_stock'] ?> low on stock</div>
  </div>
</div>

<?php if ($monthly): ?>
<div class="panel" style="margin-top:20px">
  <h3>Revenue by month</h3>
  <div style="display:flex;align-items:flex-end;gap:10px;height:190px;padding-top:14px">
    <?php foreach ($monthly as $m):
        $h = $maxRevenue > 0 ? max(6, round((float)$m['revenue'] / $maxRevenue * 150)) : 6; ?>
      <div style="flex:1;text-align:center;min-width:38px">
        <div style="font-size:.72rem;color:var(--text-dim);margin-bottom:5px"><?= money($m['revenue']) ?></div>
        <div title="<?= money($m['revenue']) ?>"
             style="height:<?= $h ?>px;border-radius:6px 6px 0 0;
                    background:linear-gradient(180deg,var(--green),rgba(78,245,138,.25))"></div>
        <div style="font-size:.72rem;color:var(--text-faint);margin-top:6px"><?= e($m['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:20px" class="dash-grid">

  <div class="panel">
    <h3>Best selling games</h3>
    <div class="table-wrap" style="border:none">
      <table class="data" style="min-width:0">
        <thead><tr><th>Game</th><th>Units</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($popular as $p): ?>
          <tr><td><?= e($p['title']) ?></td><td><?= (int)$p['units_sold'] ?></td><td><?= money($p['revenue']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3>Recent orders</h3>
    <div class="table-wrap" style="border:none">
      <table class="data" style="min-width:0">
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><a href="<?= url('order_details.php?id=' . (int)$o['order_id']) ?>">#<?= (int)$o['order_id'] ?></a></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= money($o['total_amount']) ?></td>
            <td><span class="status status-<?= e($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3>Low stock alert</h3>
    <?php if (!$lowStock): ?>
      <p style="color:var(--text-dim);font-size:.9rem">Every title has healthy stock.</p>
    <?php else: ?>
      <div class="table-wrap" style="border:none">
        <table class="data" style="min-width:0">
          <thead><tr><th>Game</th><th>Stock</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($lowStock as $g): ?>
            <tr>
              <td><?= e($g['title']) ?></td>
              <td><span class="status <?= (int)$g['stock'] === 0 ? 'status-cancelled' : 'status-pending' ?>"><?= (int)$g['stock'] ?></span></td>
              <td><a class="btn btn-ghost btn-sm" href="<?= url('admin/inventory.php') ?>">Restock</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h3>Newest customers</h3>
    <div class="table-wrap" style="border:none">
      <table class="data" style="min-width:0">
        <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($recentUsers as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td style="font-size:.83rem;color:var(--text-dim)"><?= e($u['email']) ?></td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>@media (max-width: 1000px) { .dash-grid { grid-template-columns: 1fr !important; } }</style>

<?php require __DIR__ . '/footer.php'; ?>
