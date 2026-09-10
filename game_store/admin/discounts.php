<?php
/** FILE: admin/discounts.php - manage campaign discounts */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM discounts WHERE discount_id = ?')->execute([(int)$_POST['discount_id']]);
        set_flash('success', 'Discount removed.');
        redirect('admin/discounts.php');
    }

    $gameId = (int)($_POST['game_id'] ?? 0);
    $pct    = (float)($_POST['discount_percentage'] ?? 0);
    $start  = $_POST['start_date'] ?? '';
    $end    = $_POST['end_date'] ?? '';

    if (!$gameId || $pct <= 0 || $pct > 90) {
        set_flash('error', 'Pick a game and a percentage between 1 and 90.');
    } elseif (!strtotime($start) || !strtotime($end) || strtotime($end) < strtotime($start)) {
        set_flash('error', 'The end date must fall on or after the start date.');
    } else {
        $pdo->prepare(
            'INSERT INTO discounts (game_id, discount_percentage, start_date, end_date) VALUES (?, ?, ?, ?)'
        )->execute([$gameId, $pct, $start, $end]);
        set_flash('success', 'Discount scheduled.');
    }
    redirect('admin/discounts.php');
}

$pageTitle = 'Discounts';
$rows = $pdo->query(
    "SELECT d.*, g.title, g.price,
            ROUND(g.price * (1 - d.discount_percentage/100), 2) AS sale_price,
            CASE
                WHEN CURDATE() BETWEEN d.start_date AND d.end_date THEN 'live'
                WHEN CURDATE() < d.start_date                      THEN 'scheduled'
                ELSE 'expired'
            END AS state
       FROM discounts d JOIN games g ON g.game_id = d.game_id
      ORDER BY d.start_date DESC"
)->fetchAll();

$games = $pdo->query('SELECT game_id, title, price FROM games WHERE status = "active" ORDER BY title')->fetchAll();
require __DIR__ . '/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 330px;gap:18px" class="dash-grid">
  <div class="table-wrap" style="height:fit-content">
    <table class="data">
      <thead><tr><th>Game</th><th>Off</th><th>Price</th><th>Runs</th><th>State</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['title']) ?></strong></td>
          <td><span class="status status-pending"><?= (int)$r['discount_percentage'] ?>%</span></td>
          <td>
            <span class="price-old"><?= money($r['price']) ?></span>
            <strong style="color:var(--green)"><?= money($r['sale_price']) ?></strong>
          </td>
          <td style="font-size:.83rem">
            <?= date('d M Y', strtotime($r['start_date'])) ?> &rarr; <?= date('d M Y', strtotime($r['end_date'])) ?>
          </td>
          <td>
            <span class="status <?= $r['state'] === 'live' ? 'status-active' : ($r['state'] === 'scheduled' ? 'status-paid' : 'status-refunded') ?>">
              <?= e($r['state']) ?>
            </span>
          </td>
          <td>
            <form class="inline-form" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="discount_id" value="<?= (int)$r['discount_id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Remove this discount?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <form class="panel" method="post" style="height:fit-content">
    <?= csrf_field() ?>
    <h3>Schedule a discount</h3>
    <input type="hidden" name="action" value="create">

    <div class="form-row">
      <label for="game_id">Game</label>
      <select class="input" id="game_id" name="game_id" required>
        <option value="">Select a game</option>
        <?php foreach ($games as $g): ?>
          <option value="<?= (int)$g['game_id'] ?>"><?= e($g['title']) ?> (<?= money($g['price']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="discount_percentage">Percentage off</label>
      <input class="input" type="number" id="discount_percentage" name="discount_percentage"
             min="1" max="90" step="1" required placeholder="25">
    </div>
    <div class="form-row">
      <label for="start_date">Starts</label>
      <input class="input" type="date" id="start_date" name="start_date" required value="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-row">
      <label for="end_date">Ends</label>
      <input class="input" type="date" id="end_date" name="end_date" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
    </div>

    <button class="btn btn-primary btn-block" type="submit">Schedule discount</button>
  </form>
</div>
<style>@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr !important; } }</style>
<?php require __DIR__ . '/footer.php'; ?>
