<?php
/** FILE: admin/inventory.php - stock levels and restocking */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $gameId = (int)($_POST['game_id'] ?? 0);

    if (($_POST['action'] ?? '') === 'set_stock') {
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $pdo->prepare('UPDATE games SET stock = ? WHERE game_id = ?')->execute([$stock, $gameId]);
        set_flash('success', 'Stock level updated.');
    } elseif (($_POST['action'] ?? '') === 'restock_all') {
        $pdo->query('UPDATE games SET stock = stock + 25 WHERE stock <= 5');
        set_flash('success', 'Added 25 units to every critically low title.');
    }
    redirect('admin/inventory.php');
}

$pageTitle = 'Inventory';
$rows = $pdo->query(
    "SELECT g.game_id, g.title, g.stock, g.price, g.cover_image,
            CASE WHEN g.stock = 0 THEN 'Out of stock'
                 WHEN g.stock <= 5 THEN 'Critical'
                 WHEN g.stock <= 15 THEN 'Low'
                 ELSE 'Healthy' END AS stock_state,
            COALESCE((SELECT SUM(oi.quantity) FROM order_items oi
                        JOIN orders o ON o.order_id = oi.order_id
                       WHERE oi.game_id = g.game_id AND o.order_status IN ('paid','completed')), 0) AS sold
       FROM games g
      ORDER BY g.stock ASC"
)->fetchAll();

$summary = $pdo->query(
    "SELECT SUM(stock) AS units,
            SUM(stock = 0) AS out_of_stock,
            SUM(stock > 0 AND stock <= 5) AS critical,
            ROUND(SUM(stock * price), 2) AS stock_value
       FROM games"
)->fetch();

require __DIR__ . '/header.php';
?>
<div class="stat-grid" style="margin-bottom:20px">
  <div class="stat-card"><div class="label">Units in stock</div><div class="value"><?= (int)$summary['units'] ?></div></div>
  <div class="stat-card blue"><div class="label">Stock value</div><div class="value"><?= money($summary['stock_value']) ?></div></div>
  <div class="stat-card orange"><div class="label">Critically low</div><div class="value"><?= (int)$summary['critical'] ?></div></div>
  <div class="stat-card purple"><div class="label">Out of stock</div><div class="value"><?= (int)$summary['out_of_stock'] ?></div></div>
</div>

<div class="admin-toolbar">
  <span class="result-count">Sorted by lowest stock first</span>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="restock_all">
    <button class="btn btn-purple btn-sm" data-confirm="Add 25 units to every title with 5 or fewer in stock?">
      Restock all critical titles
    </button>
  </form>
</div>

<div class="table-wrap">
  <table class="data">
    <thead><tr><th></th><th>Game</th><th>Stock</th><th>State</th><th>Sold</th><th>Value</th><th>Set stock</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><img class="thumb" src="<?= cover_url($r['cover_image']) ?>" alt=""></td>
        <td><strong><?= e($r['title']) ?></strong></td>
        <td><strong style="font-size:1.05rem"><?= (int)$r['stock'] ?></strong></td>
        <td>
          <span class="status <?= $r['stock_state'] === 'Healthy' ? 'status-active'
                                : ($r['stock_state'] === 'Low' ? 'status-paid'
                                : ($r['stock_state'] === 'Critical' ? 'status-pending' : 'status-cancelled')) ?>">
            <?= e($r['stock_state']) ?>
          </span>
        </td>
        <td><?= (int)$r['sold'] ?></td>
        <td><?= money($r['stock'] * $r['price']) ?></td>
        <td>
          <form class="inline-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="set_stock">
            <input type="hidden" name="game_id" value="<?= (int)$r['game_id'] ?>">
            <input class="input" type="number" name="stock" min="0" value="<?= (int)$r['stock'] ?>"
                   style="width:82px;padding:7px 9px">
            <button class="btn btn-primary btn-sm" type="submit">Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
