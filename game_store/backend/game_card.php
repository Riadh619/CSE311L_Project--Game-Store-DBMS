<?php
/**
 * FILE: backend/game_card.php
 * Reusable game card. Expects $game (a row from game_catalog_view).
 * Optional: $wishIds - array of game ids already on the user's wishlist.
 */
$wishIds   = $wishIds ?? [];
$onWish    = in_array((int)$game['game_id'], $wishIds, true);
$isNew     = strtotime($game['release_date']) > strtotime('-120 days');
$hasSale   = (float)$game['discount_percentage'] > 0;
$outStock  = (int)$game['stock'] <= 0;
?>
<article class="game-card">
  <div class="card-media">
    <a href="<?= url('game_details.php?id=' . (int)$game['game_id']) ?>">
      <img src="<?= cover_url($game['cover_image']) ?>" alt="<?= e($game['title']) ?> cover art" loading="lazy">
    </a>

    <?php if ($outStock): ?>
      <span class="card-tag tag-out">Out of stock</span>
    <?php elseif ($hasSale): ?>
      <span class="card-tag tag-sale">-<?= (int)$game['discount_percentage'] ?>%</span>
    <?php elseif ($isNew): ?>
      <span class="card-tag tag-new">New</span>
    <?php elseif ((int)$game['units_sold'] >= 3): ?>
      <span class="card-tag tag-hot">Popular</span>
    <?php endif; ?>

    <button class="card-wish js-wish <?= $onWish ? 'saved' : '' ?>"
            data-game="<?= (int)$game['game_id'] ?>"
            title="<?= $onWish ? 'Remove from wishlist' : 'Save to wishlist' ?>"
            aria-label="Toggle wishlist"><?= $onWish ? '&#9829;' : '&#9825;' ?></button>

    <div class="card-overlay">
      <?php if ($outStock): ?>
        <span class="btn btn-ghost disabled">Sold out</span>
      <?php else: ?>
        <button class="btn btn-primary js-add-cart" data-game="<?= (int)$game['game_id'] ?>">Add to cart</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body">
    <h3 class="card-title"><a href="<?= url('game_details.php?id=' . (int)$game['game_id']) ?>"><?= e($game['title']) ?></a></h3>
    <p class="card-meta"><?= e($game['genres'] ?: 'Uncategorised') ?></p>
    <div class="card-rating">
      <?= star_html($game['avg_rating']) ?>
      <span><?= $game['review_count'] ? number_format((float)$game['avg_rating'], 1) . ' (' . (int)$game['review_count'] . ')' : 'No reviews yet' ?></span>
    </div>
    <div class="card-foot">
      <span>
        <?php if ($hasSale): ?><span class="price-old"><?= money($game['price']) ?></span><?php endif; ?>
        <span class="price"><?= money($game['final_price']) ?></span>
      </span>
      <a class="btn btn-ghost btn-sm" href="<?= url('game_details.php?id=' . (int)$game['game_id']) ?>">View</a>
    </div>
  </div>
</article>
