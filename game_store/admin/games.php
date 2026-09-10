<?php
/** FILE: admin/games.php - list, search and delete games */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $gameId = (int)$_POST['game_id'];
    try {
        $pdo->prepare('DELETE FROM games WHERE game_id = ?')->execute([$gameId]);
        set_flash('success', 'Game deleted.');
    } catch (PDOException $ex) {
        // Order history keeps a foreign key on the game, so deactivate instead.
        $pdo->prepare('UPDATE games SET status = "inactive" WHERE game_id = ?')->execute([$gameId]);
        set_flash('info', 'This game appears in past orders, so it was hidden from the store instead of deleted.');
    }
    redirect('admin/games.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    verify_csrf();
    $pdo->prepare('UPDATE games SET status = IF(status = "active", "inactive", "active") WHERE game_id = ?')
        ->execute([(int)$_POST['game_id']]);
    set_flash('success', 'Status updated.');
    redirect('admin/games.php');
}

$pageTitle = 'Games';
$search = clean($_GET['q'] ?? '');

$sql    = 'SELECT * FROM game_catalog_view';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE title LIKE ? OR developer_name LIKE ? OR publisher_name LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY game_id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$games = $stmt->fetchAll();

require __DIR__ . '/header.php';
?>
<div class="admin-toolbar">
  <form class="admin-search" method="get">
    <input class="input" type="search" name="q" placeholder="Search title, developer, publisher" value="<?= e($search) ?>">
    <button class="btn btn-ghost btn-sm" type="submit">Search</button>
    <?php if ($search !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/games.php') ?>">Clear</a><?php endif; ?>
  </form>
  <a class="btn btn-primary" href="<?= url('admin/add_game.php') ?>">Add new game</a>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr><th></th><th>Title</th><th>Developer</th><th>Price</th><th>Stock</th>
          <th>Sold</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($games as $g): ?>
      <tr>
        <td><img class="thumb" src="<?= cover_url($g['cover_image']) ?>" alt=""></td>
        <td>
          <strong><?= e($g['title']) ?></strong>
          <div style="font-size:.78rem;color:var(--text-faint)"><?= e($g['genres'] ?: 'No genre') ?></div>
        </td>
        <td><?= e($g['developer_name']) ?></td>
        <td>
          <?= money($g['final_price']) ?>
          <?php if ((float)$g['discount_percentage'] > 0): ?>
            <div style="font-size:.75rem;color:var(--orange)">-<?= (int)$g['discount_percentage'] ?>%</div>
          <?php endif; ?>
        </td>
        <td><span class="status <?= (int)$g['stock'] <= 5 ? 'status-pending' : 'status-active' ?>"><?= (int)$g['stock'] ?></span></td>
        <td><?= (int)$g['units_sold'] ?></td>
        <td><?= $g['review_count'] ? number_format((float)$g['avg_rating'], 1) : '&ndash;' ?></td>
        <td><span class="status status-<?= e($g['status']) ?>"><?= e($g['status']) ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn btn-ghost btn-sm" href="<?= url('admin/edit_game.php?id=' . (int)$g['game_id']) ?>">Edit</a>
          <form class="inline-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="game_id" value="<?= (int)$g['game_id'] ?>">
            <button class="btn btn-ghost btn-sm" name="action" value="toggle_status">
              <?= $g['status'] === 'active' ? 'Hide' : 'Show' ?>
            </button>
          </form>
          <form class="inline-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="game_id" value="<?= (int)$g['game_id'] ?>">
            <button class="btn btn-danger btn-sm" name="action" value="delete"
                    data-confirm="Delete <?= e($g['title']) ?>?">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
