<?php
/** FILE: register.php */
require_once __DIR__ . '/backend/functions.php';
if (is_logged_in()) redirect('index.php');
$pageTitle = 'Create account';
$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);
require __DIR__ . '/backend/header.php';
?>
<div class="wrap auth-shell">
  <div class="auth-card panel">
    <h1>Create your account</h1>
    <p class="sub">One account covers the store, your library and your reviews.</p>

    <?php if ($errors): ?>
      <ul class="error-list">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="<?= url('backend/auth.php') ?>" id="registerForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="register">

      <div class="form-row">
        <label for="name">Full name</label>
        <input class="input" type="text" id="name" name="name" required minlength="3"
               value="<?= e($old['name'] ?? '') ?>" placeholder="Tanvir Ahmed">
      </div>

      <div class="form-row">
        <label for="email">Email address</label>
        <input class="input" type="email" id="email" name="email" required
               value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com">
      </div>

      <div class="form-grid-2">
        <div class="form-row">
          <label for="password">Password</label>
          <input class="input" type="password" id="password" name="password" required minlength="6">
          <p class="form-hint">At least 6 characters.</p>
        </div>
        <div class="form-row">
          <label for="confirm_password">Confirm password</label>
          <input class="input" type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
      </div>

      <div class="form-row">
        <label for="phone">Phone <span style="text-transform:none;color:var(--text-faint)">(optional)</span></label>
        <input class="input" type="text" id="phone" name="phone"
               value="<?= e($old['phone'] ?? '') ?>" placeholder="01711000000">
      </div>

      <div class="form-row">
        <label for="address">Address <span style="text-transform:none;color:var(--text-faint)">(optional)</span></label>
        <input class="input" type="text" id="address" name="address"
               value="<?= e($old['address'] ?? '') ?>" placeholder="Mirpur, Dhaka">
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit">Create account</button>
    </form>

    <p class="auth-alt">Already registered? <a href="<?= url('login.php') ?>">Log in instead</a></p>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
