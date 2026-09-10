<?php
/** FILE: admin/categories.php - full CRUD on categories */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $name   = clean($_POST['category_name'] ?? '');
    $desc   = clean($_POST['description'] ?? '');
    $id     = (int)($_POST['category_id'] ?? 0);

    try {
        if ($action === 'create' && $name !== '') {
            $pdo->prepare('INSERT INTO categories (category_name, description) VALUES (?, ?)')
                ->execute([$name, $desc ?: null]);
            set_flash('success', 'Category added.');
        } elseif ($action === 'update' && $name !== '') {
            $pdo->prepare('UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?')
                ->execute([$name, $desc ?: null, $id]);
            set_flash('success', 'Category updated.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$id]);
            set_flash('success', 'Category deleted. Games that used it now have no category.');
        }
    } catch (PDOException $ex) {
        set_flash('error', 'That category name is already taken.');
    }
    redirect('admin/categories.php');
}

$pageTitle = 'Categories';
$rows = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM games g WHERE g.category_id = c.category_id) AS game_count
       FROM categories c ORDER BY c.category_name'
)->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
require __DIR__ . '/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:18px" class="dash-grid">
  <div class="table-wrap" style="height:fit-content">
    <table class="data">
      <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Games</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['category_id'] ?></td>
          <td><strong><?= e($r['category_name']) ?></strong></td>
          <td style="color:var(--text-dim);font-size:.86rem"><?= e($r['description'] ?: '-') ?></td>
          <td><?= (int)$r['game_count'] ?></td>
          <td style="white-space:nowrap">
            <a class="btn btn-ghost btn-sm" href="<?= url('admin/categories.php?edit=' . (int)$r['category_id']) ?>">Edit</a>
            <form class="inline-form" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="category_id" value="<?= (int)$r['category_id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Delete <?= e($r['category_name']) ?>?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php
    $editing = null;
    foreach ($rows as $r) { if ((int)$r['category_id'] === $editId) $editing = $r; }
  ?>
  <form class="panel" method="post" style="height:fit-content">
    <?= csrf_field() ?>
    <h3><?= $editing ? 'Edit category' : 'New category' ?></h3>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?>
      <input type="hidden" name="category_id" value="<?= (int)$editing['category_id'] ?>">
    <?php endif; ?>

    <div class="form-row">
      <label for="category_name">Name</label>
      <input class="input" type="text" id="category_name" name="category_name" required
             value="<?= e($editing['category_name'] ?? '') ?>">
    </div>
    <div class="form-row">
      <label for="description">Description</label>
      <textarea class="input" id="description" name="description" style="min-height:80px"><?= e($editing['description'] ?? '') ?></textarea>
    </div>

    <button class="btn btn-primary btn-block" type="submit"><?= $editing ? 'Save changes' : 'Add category' ?></button>
    <?php if ($editing): ?>
      <a class="btn btn-ghost btn-block btn-sm" style="margin-top:8px" href="<?= url('admin/categories.php') ?>">Cancel</a>
    <?php endif; ?>
  </form>
</div>
<style>@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr !important; } }</style>
<?php require __DIR__ . '/footer.php'; ?>
