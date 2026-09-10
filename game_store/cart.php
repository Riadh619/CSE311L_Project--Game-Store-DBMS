<?php
/** FILE: cart.php */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'Your cart';

$items  = cart_rows($pdo, current_user_id());
$totals = cart_totals($items);

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Your cart</h1>
  <p><?= count($items) ?> title<?= count($items) === 1 ? '' : 's' ?> ready for checkout.</p>
</div>

<?php if (!$items): ?>
  <div class="wrap panel empty">
    <div class="glyph">&#128722;</div>
    <h3>Your cart is empty</h3>
    <p>Browse the store and add something you like.</p>
    <a class="btn btn-primary" href="<?= url('games.php') ?>">Browse games</a>
  </div>
<?php else: ?>
<div class="wrap cart-layout">

  <div class="panel">
    <?php foreach ($items as $item): ?>
      <div class="cart-item">
        <a href="<?= url('game_details.php?id=' . (int)$item['game_id']) ?>">
          <img src="<?= cover_url($item['cover_image']) ?>" alt="<?= e($item['title']) ?>">
        </a>

        <div>
          <h4><a href="<?= url('game_details.php?id=' . (int)$item['game_id']) ?>"><?= e($item['title']) ?></a></h4>
          <p class="card-meta"><?= e($item['genres']) ?></p>
          <p style="font-size:.86rem;margin-top:5px">
            <?php if ((float)$item['discount_percentage'] > 0): ?>
              <span class="price-old"><?= money($item['price']) ?></span>
            <?php endif; ?>
            <span style="color:var(--green);font-weight:600"><?= money($item['final_price']) ?></span>
            <span style="color:var(--text-faint)"> each</span>
          </p>

          <div style="display:flex;gap:10px;align-items:center;margin-top:9px">
            <form method="post" action="<?= url('backend/cart_actions.php') ?>" class="qty">
              <?= csrf_field() ?>
              <input type="hidden" name="game_id" value="<?= (int)$item['game_id'] ?>">
              <input type="hidden" name="return" value="cart.php">
              <button type="submit" name="action" value="decrease" aria-label="Decrease quantity">&minus;</button>
              <span><?= (int)$item['quantity'] ?></span>
              <button type="submit" name="action" value="increase" aria-label="Increase quantity">&plus;</button>
            </form>

            <form method="post" action="<?= url('backend/cart_actions.php') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="game_id" value="<?= (int)$item['game_id'] ?>">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="return" value="cart.php">
              <button class="btn btn-danger btn-sm" type="submit">Remove</button>
            </form>
          </div>
        </div>

        <div style="text-align:right">
          <div class="price"><?= money($item['line_total']) ?></div>
          <div style="font-size:.78rem;color:var(--text-faint)"><?= (int)$item['stock'] ?> in stock</div>
        </div>
      </div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('backend/cart_actions.php') ?>" style="margin-top:16px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="clear">
      <input type="hidden" name="return" value="cart.php">
      <button class="btn btn-ghost btn-sm" type="submit" data-confirm="Remove every item from your cart?">Clear cart</button>
    </form>
  </div>

  <div class="panel">
    <h3>Order summary</h3>
    <div class="summary-line"><span>Subtotal</span><span><?= money($totals['subtotal']) ?></span></div>
    <?php if ($totals['savings'] > 0): ?>
      <div class="summary-line save"><span>Discounts</span><span>-<?= money($totals['savings']) ?></span></div>
    <?php endif; ?>
    <div class="summary-line"><span>Delivery</span><span>Digital, free</span></div>
    <div class="summary-line total"><span>Total</span><span><?= money($totals['total']) ?></span></div>

    <a class="btn btn-primary btn-block btn-lg" style="margin-top:18px" href="<?= url('checkout.php') ?>">
      Go to checkout
    </a>
    <a class="btn btn-ghost btn-block btn-sm" style="margin-top:10px" href="<?= url('games.php') ?>">Keep shopping</a>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/backend/footer.php'; ?>
