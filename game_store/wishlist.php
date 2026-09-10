<?php
/** FILE: wishlist.php */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'Wishlist';

$stmt = $pdo->prepare(
    'SELECT v.*, w.created_at AS saved_at
       FROM wishlists w JOIN game_catalog_view v ON v.game_id = w.game_id
      WHERE w.user_id = ?
      ORDER BY w.created_at DESC'
);
$stmt->execute([current_user_id()]);
$items = $stmt->fetchAll();

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Your wishlist</h1>
  <p><?= count($items) ?> saved title<?= count($items) === 1 ? '' : 's' ?>. Move one to the cart whenever you are ready.</p>
</div>

<div class="wrap">
<?php if (!$items): ?>
  <div class="panel empty">
    <div class="glyph">&#9825;</div>
    <h3>Nothing saved yet</h3>
    <p>Tap the heart on any game to keep an eye on it.</p>
    <a class="btn btn-primary" href="<?= url('games.php') ?>">Browse games</a>
  </div>
<?php else: ?>
  <div class="panel">
    <?php foreach ($items as $item): ?>
      <div class="cart-item">
        <a href="<?= url('game_details.php?id=' . (int)$item['game_id']) ?>">
          <img src="<?= cover_url($item['cover_image']) ?>" alt="<?= e($item['title']) ?>">
        </a>
        <div>
          <h4><a href="<?= url('game_details.php?id=' . (int)$item['game_id']) ?>"><?= e($item['title']) ?></a></h4>
          <p class="card-meta"><?= e($item['genres']) ?> &middot; saved <?= time_ago($item['saved_at']) ?></p>
          <div class="card-rating" style="margin-top:5px"><?= star_html($item['avg_rating']) ?>
            <span><?= $item['review_count'] ? number_format((float)$item['avg_rating'], 1) : 'No reviews' ?></span>
          </div>
          <div style="display:flex;gap:9px;margin-top:10px;flex-wrap:wrap">
            <form method="post" action="<?= url('backend/wishlist_actions.php') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="game_id" value="<?= (int)$item['game_id'] ?>">
              <input type="hidden" name="action" value="move_to_cart">
              <input type="hidden" name="return" value="wishlist.php">
              <button class="btn btn-primary btn-sm" type="submit"
                      <?= (int)$item['stock'] <= 0 ? 'disabled' : '' ?>>Move to cart</button>
            </form>
            <form method="post" action="<?= url('backend/wishlist_actions.php') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="game_id" value="<?= (int)$item['game_id'] ?>">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="return" value="wishlist.php">
              <button class="btn btn-danger btn-sm" type="submit">Remove</button>
            </form>
          </div>
        </div>
        <div style="text-align:right">
          <?php if ((float)$item['discount_percentage'] > 0): ?>
            <div class="price-old"><?= money($item['price']) ?></div>
          <?php endif; ?>
          <div class="price"><?= money($item['final_price']) ?></div>
          <?php if ((int)$item['stock'] <= 0): ?>
            <div style="font-size:.78rem;color:var(--red)">Out of stock</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
