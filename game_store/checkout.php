<?php
/** FILE: checkout.php */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'Checkout';

$items  = cart_rows($pdo, current_user_id());
if (!$items) {
    set_flash('error', 'Your cart is empty.');
    redirect('cart.php');
}
$totals = cart_totals($items);

$me = $pdo->prepare('SELECT name, email, phone, address FROM users WHERE user_id = ?');
$me->execute([current_user_id()]);
$user = $me->fetch();

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Checkout</h1>
  <p>Payment is simulated for this course project. No real money moves.</p>
</div>

<form class="wrap cart-layout" method="post" action="<?= url('backend/checkout_process.php') ?>">
  <?= csrf_field() ?>

  <div>
    <div class="panel">
      <h3>Billing details</h3>
      <div class="form-grid-2">
        <div class="form-row">
          <label>Name</label>
          <input class="input" type="text" value="<?= e($user['name']) ?>" readonly>
        </div>
        <div class="form-row">
          <label>Email</label>
          <input class="input" type="email" value="<?= e($user['email']) ?>" readonly>
        </div>
      </div>
      <div class="form-row">
        <label>Delivery note</label>
        <input class="input" type="text" value="Digital delivery to your library" readonly>
      </div>
      <p class="form-hint">Update these details on your <a href="<?= url('profile.php') ?>" style="color:var(--green)">profile page</a>.</p>
    </div>

    <div class="panel">
      <h3>Payment method</h3>
      <label class="pay-option">
        <input type="radio" name="payment_method" value="card" checked>
        <span>&#128179; Credit or debit card</span>
      </label>
      <label class="pay-option">
        <input type="radio" name="payment_method" value="bkash">
        <span>&#128241; bKash</span>
      </label>
      <label class="pay-option">
        <input type="radio" name="payment_method" value="nagad">
        <span>&#128241; Nagad</span>
      </label>
      <label class="pay-option">
        <input type="radio" name="payment_method" value="paypal">
        <span>&#127760; PayPal</span>
      </label>
      <label class="pay-option">
        <input type="radio" name="payment_method" value="cash_on_delivery">
        <span>&#128176; Cash on delivery (order stays pending)</span>
      </label>
    </div>
  </div>

  <div class="panel">
    <h3>Your order</h3>
    <?php foreach ($items as $item): ?>
      <div class="summary-line">
        <span><?= e($item['title']) ?> &times; <?= (int)$item['quantity'] ?></span>
        <span><?= money($item['line_total']) ?></span>
      </div>
    <?php endforeach; ?>

    <div class="summary-line" style="border-top:1px solid var(--line);margin-top:8px;padding-top:12px">
      <span>Subtotal</span><span><?= money($totals['subtotal']) ?></span>
    </div>
    <?php if ($totals['savings'] > 0): ?>
      <div class="summary-line save"><span>You save</span><span>-<?= money($totals['savings']) ?></span></div>
    <?php endif; ?>
    <div class="summary-line total"><span>Pay now</span><span><?= money($totals['total']) ?></span></div>

    <button class="btn btn-primary btn-block btn-lg" style="margin-top:18px" type="submit">
      Place order &amp; pay <?= money($totals['total']) ?>
    </button>
    <a class="btn btn-ghost btn-block btn-sm" style="margin-top:10px" href="<?= url('cart.php') ?>">Back to cart</a>
  </div>
</form>
<?php require __DIR__ . '/backend/footer.php'; ?>
