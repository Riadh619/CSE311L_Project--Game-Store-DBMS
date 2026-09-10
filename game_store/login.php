<?php
/** FILE: login.php */
require_once __DIR__ . '/backend/functions.php';
if (is_logged_in()) redirect('index.php');
$pageTitle = 'Log in';
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);
require __DIR__ . '/backend/header.php';
?>
<div class="wrap auth-shell">
  <div class="auth-card panel">
    <h1>Welcome back</h1>
    <p class="sub">Log in to reach your library, cart and order history.</p>

    <form method="post" action="<?= url('backend/auth.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="login">

      <div class="form-row">
        <label for="email">Email address</label>
        <input class="input" type="email" id="email" name="email" required autofocus
               value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com">
      </div>

      <div class="form-row">
        <label for="password">Password</label>
        <input class="input" type="password" id="password" name="password" required placeholder="Your password">
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit">Log in</button>
    </form>

    <p class="auth-alt">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>

    <div class="demo-box">
      <strong>Demo accounts</strong><br>
      Customer: <code>tanvir@example.com</code> / <code>user123</code><br>
      Admin: <code>admin@gamestore.com</code> / <code>admin123</code>
    </div>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
