<?php
/** FILE: admin/genres.php - full CRUD on genres */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $name   = clean($_POST['genre_name'] ?? '');
    $id     = (int)($_POST['genre_id'] ?? 0);

    try {
        if ($action === 'create' && $name !== '') {
            $pdo->prepare('INSERT INTO genres (genre_name) VALUES (?)')->execute([$name]);
            set_flash('success', 'Genre added.');
        } elseif ($action === 'update' && $name !== '') {
            $pdo->prepare('UPDATE genres SET genre_name = ? WHERE genre_id = ?')->execute([$name, $id]);
            set_flash('success', 'Genre renamed.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM genres WHERE genre_id = ?')->execute([$id]);
            set_flash('success', 'Genre deleted and unlinked from its games.');
        }
    } catch (PDOException $ex) {
        set_flash('error', 'That genre name already exists.');
    }
    redirect('admin/genres.php');
}

$pageTitle = 'Genres';
$rows = $pdo->query(
    'SELECT ge.*, (SELECT COUNT(*) FROM game_genres gg WHERE gg.genre_id = ge.genre_id) AS game_count
       FROM genres ge ORDER BY ge.genre_name'
)->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
require __DIR__ . '/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:18px" class="dash-grid">
  <div class="table-wrap" style="height:fit-content">
    <table class="data">
      <thead><tr><th>#</th><th>Genre</th><th>Games tagged</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['genre_id'] ?></td>
          <td><strong><?= e($r['genre_name']) ?></strong></td>
          <td><?= (int)$r['game_count'] ?></td>
          <td style="white-space:nowrap">
            <a class="btn btn-ghost btn-sm" href="<?= url('admin/genres.php?edit=' . (int)$r['genre_id']) ?>">Rename</a>
            <form class="inline-form" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="genre_id" value="<?= (int)$r['genre_id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Delete the <?= e($r['genre_name']) ?> genre?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php $editing = null; foreach ($rows as $r) { if ((int)$r['genre_id'] === $editId) $editing = $r; } ?>
  <form class="panel" method="post" style="height:fit-content">
    <?= csrf_field() ?>
    <h3><?= $editing ? 'Rename genre' : 'New genre' ?></h3>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="genre_id" value="<?= (int)$editing['genre_id'] ?>"><?php endif; ?>

    <div class="form-row">
      <label for="genre_name">Genre name</label>
      <input class="input" type="text" id="genre_name" name="genre_name" required value="<?= e($editing['genre_name'] ?? '') ?>">
    </div>
    <button class="btn btn-primary btn-block" type="submit"><?= $editing ? 'Save' : 'Add genre' ?></button>
    <?php if ($editing): ?>
      <a class="btn btn-ghost btn-block btn-sm" style="margin-top:8px" href="<?= url('admin/genres.php') ?>">Cancel</a>
    <?php endif; ?>
  </form>
</div>
<style>@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr !important; } }</style>
<?php require __DIR__ . '/footer.php'; ?>
