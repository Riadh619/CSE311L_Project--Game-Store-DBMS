<?php
/** FILE: admin/login.php */
require_once __DIR__ . '/../backend/functions.php';
if (is_admin()) redirect('admin/dashboard.php');
$pageTitle = 'Admin login';
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login &middot; <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
<div class="wrap auth-shell" style="min-height:100vh">
  <div class="auth-card panel">
    <a class="brand" style="margin-bottom:22px" href="<?= url('index.php') ?>">
      <span class="brand-mark">NX</span><span class="brand-text">Admin<em>Panel</em></span>
    </a>

    <h1>Staff sign in</h1>
    <p class="sub">Admin accounts only. Customers should use the normal login page.</p>

    <?php foreach (take_flash() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>" style="margin-bottom:14px"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('backend/auth.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="login">
      <input type="hidden" name="admin_only" value="1">

      <div class="form-row">
        <label for="email">Admin email</label>
        <input class="input" type="email" id="email" name="email" required autofocus
               value="<?= e($old['email'] ?? '') ?>">
      </div>
      <div class="form-row">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" required>
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
    </form>

    <p class="auth-alt"><a href="<?= url('index.php') ?>">Back to the storefront</a></p>

    <div class="demo-box">
      <strong>Demo admin</strong><br>
      <code>admin@gamestore.com</code> / <code>admin123</code>
    </div>
  </div>
</div>
<script src="<?= url('js/main.js') ?>"></script>
</body>
</html>
