<?php
/** FILE: game_details.php - single game page with reviews, cart and wishlist */
require_once __DIR__ . '/backend/functions.php';

$gameId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM game_catalog_view WHERE game_id = ?');
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    http_response_code(404);
    $pageTitle = 'Game not found';
    require __DIR__ . '/backend/header.php';
    echo '<div class="wrap panel empty" style="margin-top:40px"><div class="glyph">&#9888;</div>'
       . '<h3>That game does not exist</h3><p>The link may be out of date.</p>'
       . '<a class="btn btn-primary" href="' . url('games.php') . '">Back to the store</a></div>';
    require __DIR__ . '/backend/footer.php';
    exit;
}

$pageTitle = $game['title'];

$reviewStmt = $pdo->prepare(
    'SELECT r.review_id, r.rating, r.comment, r.review_date, u.name, u.user_id
       FROM reviews r JOIN users u ON u.user_id = r.user_id
      WHERE r.game_id = ?
      ORDER BY r.review_date DESC'
);
$reviewStmt->execute([$gameId]);
$reviews = $reviewStmt->fetchAll();

$breakdown = $pdo->prepare('SELECT * FROM game_rating_view WHERE game_id = ?');
$breakdown->execute([$gameId]);
$rating = $breakdown->fetch();

$owns    = owns_game($pdo, current_user_id(), $gameId);
$onWish  = in_wishlist($pdo, current_user_id(), $gameId);
$myReview = null;
if (is_logged_in()) {
    $mr = $pdo->prepare('SELECT * FROM reviews WHERE user_id = ? AND game_id = ?');
    $mr->execute([current_user_id(), $gameId]);
    $myReview = $mr->fetch();
}

// Related titles that share at least one genre
$related = $pdo->prepare(
    'SELECT DISTINCT v.* FROM game_catalog_view v
       JOIN game_genres gg ON gg.game_id = v.game_id
      WHERE gg.genre_id IN (SELECT genre_id FROM game_genres WHERE game_id = ?)
        AND v.game_id <> ? AND v.status = "active"
      ORDER BY v.avg_rating DESC LIMIT 5'
);
$related->execute([$gameId, $gameId]);
$relatedGames = $related->fetchAll();

$wishIds = [];
if (is_logged_in()) {
    $w = $pdo->prepare('SELECT game_id FROM wishlists WHERE user_id = ?');
    $w->execute([current_user_id()]);
    $wishIds = array_map('intval', $w->fetchAll(PDO::FETCH_COLUMN));
}

require __DIR__ . '/backend/header.php';
?>

<div class="wrap breadcrumb">
  <a href="<?= url('index.php') ?>">Home</a> / <a href="<?= url('games.php') ?>">Store</a> / <?= e($game['title']) ?>
</div>

<div class="wrap detail-grid">

  <div>
    <div class="detail-cover">
      <img src="<?= cover_url($game['cover_image']) ?>" alt="<?= e($game['title']) ?> cover art">
    </div>
  </div>

  <div>
    <h1 class="detail-title"><?= e($game['title']) ?></h1>
    <p class="detail-sub">
      by <strong><?= e($game['developer_name']) ?></strong> &middot; published by <?= e($game['publisher_name']) ?>
    </p>

    <div class="badge-row">
      <?php foreach (explode(', ', (string)$game['genres']) as $gname): if ($gname === '') continue; ?>
        <span class="pill purple"><?= e($gname) ?></span>
      <?php endforeach; ?>
      <?php foreach (explode(', ', (string)$game['platforms']) as $pname): if ($pname === '') continue; ?>
        <span class="pill blue"><?= e($pname) ?></span>
      <?php endforeach; ?>
      <?php if ($game['category_name']): ?>
        <span class="pill orange"><?= e($game['category_name']) ?></span>
      <?php endif; ?>
    </div>

    <div class="card-rating" style="margin-bottom:18px">
      <?= star_html($game['avg_rating']) ?>
      <span>
        <?= $game['review_count']
            ? number_format((float)$game['avg_rating'], 2) . ' from ' . (int)$game['review_count'] . ' review' . ($game['review_count'] == 1 ? '' : 's')
            : 'No reviews yet' ?>
      </span>
    </div>

    <p style="color:var(--text-dim)"><?= nl2br(e($game['description'])) ?></p>

    <div class="buy-box">
      <div>
        <?php if ((float)$game['discount_percentage'] > 0): ?>
          <span class="price-old" style="font-size:1rem"><?= money($game['price']) ?></span>
          <span class="pill orange">-<?= (int)$game['discount_percentage'] ?>% off</span>
        <?php endif; ?>
        <div class="buy-price"><?= money($game['final_price']) ?></div>
        <div style="font-size:.85rem;color:var(--text-faint)">
          <?php if ((int)$game['stock'] > 0): ?>
            <?= (int)$game['stock'] ?> in stock
          <?php else: ?>
            Currently out of stock
          <?php endif; ?>
        </div>
      </div>

      <div class="buy-actions">
        <?php if ($owns): ?>
          <a class="btn btn-blue" href="<?= url('library.php') ?>">In your library</a>
        <?php elseif ((int)$game['stock'] > 0): ?>
          <button class="btn btn-primary js-add-cart" data-game="<?= (int)$game['game_id'] ?>">Add to cart</button>
        <?php else: ?>
          <span class="btn btn-ghost disabled">Sold out</span>
        <?php endif; ?>

        <button class="btn btn-ghost js-wish <?= $onWish ? 'saved' : '' ?>" data-game="<?= (int)$game['game_id'] ?>">
          <?= $onWish ? '&#9829; Saved' : '&#9825; Wishlist' ?>
        </button>
      </div>
    </div>

    <div class="panel">
      <h3>Details</h3>
      <table class="spec-table">
        <tr><th>Developer</th><td><?= e($game['developer_name']) ?></td></tr>
        <tr><th>Publisher</th><td><?= e($game['publisher_name']) ?></td></tr>
        <tr><th>Release date</th><td><?= date('d M Y', strtotime($game['release_date'])) ?></td></tr>
        <tr><th>Genres</th><td><?= e($game['genres'] ?: 'Not set') ?></td></tr>
        <tr><th>Platforms</th><td><?= e($game['platforms'] ?: 'Not set') ?></td></tr>
        <tr><th>Category</th><td><?= e($game['category_name'] ?: 'Not set') ?></td></tr>
        <tr><th>Copies sold</th><td><?= (int)$game['units_sold'] ?></td></tr>
      </table>
    </div>
  </div>
</div>

<section class="wrap section">
  <div class="section-head"><h2>Reviews<small>Only verified buyers can post</small></h2></div>

  <div class="cart-layout">
    <div class="panel">
      <?php if (!$reviews): ?>
        <div class="empty" style="padding:34px 10px">
          <div class="glyph">&#9734;</div>
          <h3>No reviews yet</h3>
          <p>Be the first to write one after you buy it.</p>
        </div>
      <?php else: ?>
        <?php foreach ($reviews as $r): ?>
          <div class="review">
            <div class="review-head">
              <div class="avatar"><?= e(strtoupper(substr($r['name'], 0, 1))) ?></div>
              <div>
                <div class="review-who"><?= e($r['name']) ?></div>
                <div class="review-when"><?= star_html($r['rating']) ?> &middot; <?= time_ago($r['review_date']) ?></div>
              </div>
              <?php if (is_admin() || (int)$r['user_id'] === current_user_id()): ?>
                <form method="post" action="<?= url('backend/review_actions.php') ?>" style="margin-left:auto">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
                  <input type="hidden" name="game_id" value="<?= (int)$gameId ?>">
                  <input type="hidden" name="return" value="game_details.php?id=<?= (int)$gameId ?>">
                  <button class="btn btn-danger btn-sm" data-confirm="Delete this review?">Delete</button>
                </form>
              <?php endif; ?>
            </div>
            <?php if ($r['comment']): ?><p><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div>
      <?php if ($rating && (int)$rating['review_count'] > 0): ?>
      <div class="panel">
        <h3>Rating breakdown</h3>
        <?php foreach ([5 => 'five_star', 4 => 'four_star', 3 => 'three_star', 2 => 'two_star', 1 => 'one_star'] as $starVal => $col):
              $count = (int)$rating[$col];
              $pct   = $rating['review_count'] ? round($count / $rating['review_count'] * 100) : 0; ?>
          <div style="display:flex;align-items:center;gap:10px;margin:7px 0;font-size:.86rem">
            <span style="width:44px;color:var(--text-dim)"><?= $starVal ?> star</span>
            <span style="flex:1;height:7px;background:var(--surface-2);border-radius:99px;overflow:hidden">
              <span style="display:block;height:100%;width:<?= $pct ?>%;background:var(--orange)"></span>
            </span>
            <span style="width:26px;text-align:right;color:var(--text-faint)"><?= $count ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="panel">
        <h3><?= $myReview ? 'Update your review' : 'Write a review' ?></h3>
        <?php if (!is_logged_in()): ?>
          <p style="color:var(--text-dim);font-size:.92rem">
            <a href="<?= url('login.php') ?>" style="color:var(--green)">Log in</a> to review games you own.
          </p>
        <?php elseif (!$owns): ?>
          <p style="color:var(--text-dim);font-size:.92rem">
            Reviews are limited to verified buyers. Purchase this game to leave one.
          </p>
        <?php else: ?>
          <form method="post" action="<?= url('backend/review_actions.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="game_id" value="<?= (int)$gameId ?>">
            <input type="hidden" name="return" value="game_details.php?id=<?= (int)$gameId ?>">

            <div class="form-row">
              <label>Your rating</label>
              <div class="star-picker">
                <?php for ($s = 5; $s >= 1; $s--): ?>
                  <input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>"
                         <?= $myReview && (int)$myReview['rating'] === $s ? 'checked' : '' ?>>
                  <label for="star<?= $s ?>" title="<?= $s ?> stars">&#9733;</label>
                <?php endfor; ?>
              </div>
            </div>

            <div class="form-row">
              <label for="comment">Your thoughts</label>
              <textarea class="input" id="comment" name="comment" maxlength="1000"
                        placeholder="What worked, what did not?"><?= e($myReview['comment'] ?? '') ?></textarea>
            </div>

            <button class="btn btn-primary btn-block" type="submit">
              <?= $myReview ? 'Update review' : 'Post review' ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($relatedGames): ?>
<section class="wrap section">
  <div class="section-head"><h2>Similar games<small>Sharing a genre with <?= e($game['title']) ?></small></h2></div>
  <div class="game-grid">
    <?php foreach ($relatedGames as $game) require __DIR__ . '/backend/game_card.php'; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/backend/footer.php'; ?>
