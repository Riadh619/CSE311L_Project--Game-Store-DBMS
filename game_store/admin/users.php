<?php
/** FILE: admin/users.php - view and manage customer accounts */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['user_id'] ?? 0);

    if ($id === current_user_id()) {
        set_flash('error', 'You cannot change your own account from here.');
        redirect('admin/users.php');
    }

    if ($action === 'toggle_block') {
        $pdo->prepare('UPDATE users SET status = IF(status = "active", "blocked", "active") WHERE user_id = ?')
            ->execute([$id]);
        set_flash('success', 'Account status updated.');
    } elseif ($action === 'toggle_role') {
        $pdo->prepare('UPDATE users SET role = IF(role = "admin", "customer", "admin") WHERE user_id = ?')
            ->execute([$id]);
        set_flash('success', 'Role updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$id]);
        set_flash('success', 'User deleted along with their cart, wishlist and reviews.');
    }
    redirect('admin/users.php');
}

$pageTitle = 'Users';
$search = clean($_GET['q'] ?? '');

$sql = 'SELECT u.*,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) AS orders,
               (SELECT COALESCE(SUM(total_amount),0) FROM orders o
                 WHERE o.user_id = u.user_id AND o.order_status IN ("paid","completed")) AS spent,
               (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.user_id) AS reviews
          FROM users u';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ?';
    $params = ['%' . $search . '%', '%' . $search . '%'];
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require __DIR__ . '/header.php';
?>
<div class="admin-toolbar">
  <form class="admin-search" method="get">
    <input class="input" type="search" name="q" placeholder="Search name or email" value="<?= e($search) ?>">
    <button class="btn btn-ghost btn-sm" type="submit">Search</button>
    <?php if ($search !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= url('admin/users.php') ?>">Clear</a><?php endif; ?>
  </form>
  <span class="result-count"><strong><?= count($users) ?></strong> accounts</span>
</div>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Orders</th>
          <th>Spent</th><th>Reviews</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['user_id'] ?></td>
        <td><strong><?= e($u['name']) ?></strong></td>
        <td style="font-size:.85rem;color:var(--text-dim)"><?= e($u['email']) ?></td>
        <td><span class="status <?= $u['role'] === 'admin' ? 'status-paid' : 'status-refunded' ?>"><?= e($u['role']) ?></span></td>
        <td><?= (int)$u['orders'] ?></td>
        <td><?= money($u['spent']) ?></td>
        <td><?= (int)$u['reviews'] ?></td>
        <td><span class="status status-<?= e($u['status']) ?>"><?= e($u['status']) ?></span></td>
        <td style="font-size:.83rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td style="white-space:nowrap">
          <?php if ((int)$u['user_id'] === current_user_id()): ?>
            <span style="font-size:.8rem;color:var(--text-faint)">That's you</span>
          <?php else: ?>
            <form class="inline-form" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="btn btn-ghost btn-sm" name="action" value="toggle_role">
                <?= $u['role'] === 'admin' ? 'Make customer' : 'Make admin' ?>
              </button>
            </form>
            <form class="inline-form" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="btn btn-ghost btn-sm" name="action" value="toggle_block">
                <?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?>
              </button>
            </form>
            <form class="inline-form" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
              <button class="btn btn-danger btn-sm" name="action" value="delete"
                      data-confirm="Delete <?= e($u['name']) ?> and all their data?">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
