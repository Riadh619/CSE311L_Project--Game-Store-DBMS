<?php
/** FILE: profile.php */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'Profile';

$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([current_user_id()]);
$user = $stmt->fetch();

// Each subquery gets its own placeholder: native prepared statements do not
// allow the same named parameter to appear more than once.
$stats = $pdo->prepare(
    "SELECT (SELECT COUNT(*) FROM orders  WHERE user_id = ?)  AS orders,
            (SELECT COUNT(*) FROM library WHERE user_id = ?)  AS games,
            (SELECT COUNT(*) FROM reviews WHERE user_id = ?)  AS reviews,
            (SELECT COALESCE(SUM(total_amount),0) FROM orders
              WHERE user_id = ? AND order_status IN ('paid','completed')) AS spent"
);
$uid = current_user_id();
$stats->execute([$uid, $uid, $uid, $uid]);
$s = $stats->fetch();

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Hello, <?= e($user['name']) ?></h1>
  <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?>.</p>
</div>

<div class="wrap">
  <div class="stat-grid">
    <div class="stat-card"><div class="label">Orders</div><div class="value"><?= (int)$s['orders'] ?></div></div>
    <div class="stat-card purple"><div class="label">Games owned</div><div class="value"><?= (int)$s['games'] ?></div></div>
    <div class="stat-card blue"><div class="label">Reviews written</div><div class="value"><?= (int)$s['reviews'] ?></div></div>
    <div class="stat-card orange"><div class="label">Total spent</div><div class="value"><?= money($s['spent']) ?></div></div>
  </div>
</div>

<div class="wrap cart-layout">
  <div class="panel">
    <h3>Account details</h3>
    <form method="post" action="<?= url('backend/auth.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">

      <div class="form-row">
        <label for="name">Full name</label>
        <input class="input" type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
      </div>
      <div class="form-row">
        <label>Email address</label>
        <input class="input" type="email" value="<?= e($user['email']) ?>" readonly>
        <p class="form-hint">Email is used to log in and cannot be changed here.</p>
      </div>
      <div class="form-grid-2">
        <div class="form-row">
          <label for="phone">Phone</label>
          <input class="input" type="text" id="phone" name="phone" value="<?= e($user['phone']) ?>">
        </div>
        <div class="form-row">
          <label>Role</label>
          <input class="input" type="text" value="<?= e(ucfirst($user['role'])) ?>" readonly>
        </div>
      </div>
      <div class="form-row">
        <label for="address">Address</label>
        <input class="input" type="text" id="address" name="address" value="<?= e($user['address']) ?>">
      </div>

      <button class="btn btn-primary" type="submit">Save changes</button>
    </form>
  </div>

  <div class="panel">
    <h3>Change password</h3>
    <form method="post" action="<?= url('backend/auth.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">

      <div class="form-row">
        <label for="current_password">Current password</label>
        <input class="input" type="password" id="current_password" name="current_password" required>
      </div>
      <div class="form-row">
        <label for="new_password">New password</label>
        <input class="input" type="password" id="new_password" name="new_password" required minlength="6">
      </div>
      <div class="form-row">
        <label for="confirm_password">Confirm new password</label>
        <input class="input" type="password" id="confirm_password" name="confirm_password" required minlength="6">
      </div>

      <button class="btn btn-purple btn-block" type="submit">Update password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
