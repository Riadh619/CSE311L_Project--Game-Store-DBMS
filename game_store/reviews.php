<?php
/** FILE: reviews.php - every review the logged in user has written */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'My reviews';

$stmt = $pdo->prepare(
    'SELECT r.review_id, r.rating, r.comment, r.review_date,
            g.game_id, g.title, g.cover_image
       FROM reviews r JOIN games g ON g.game_id = r.game_id
      WHERE r.user_id = ?
      ORDER BY r.review_date DESC'
);
$stmt->execute([current_user_id()]);
$myReviews = $stmt->fetchAll();

// Owned games still waiting for a review
$pending = $pdo->prepare(
    'SELECT g.game_id, g.title, g.cover_image
       FROM library l JOIN games g ON g.game_id = l.game_id
      WHERE l.user_id = ?
        AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.user_id = l.user_id AND r.game_id = l.game_id)
      ORDER BY l.acquired_at DESC'
);
$pending->execute([current_user_id()]);
$unreviewed = $pending->fetchAll();

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Your reviews</h1>
  <p><?= count($myReviews) ?> written &middot; <?= count($unreviewed) ?> game<?= count($unreviewed) === 1 ? '' : 's' ?> still waiting.</p>
</div>

<div class="wrap cart-layout">
  <div class="panel">
    <h3>Published</h3>
    <?php if (!$myReviews): ?>
      <div class="empty" style="padding:34px 10px">
        <div class="glyph">&#9998;</div>
        <h3>You have not reviewed anything yet</h3>
        <p>Reviews help other buyers decide.</p>
      </div>
    <?php else: ?>
      <?php foreach ($myReviews as $r): ?>
        <div class="cart-item">
          <img src="<?= cover_url($r['cover_image']) ?>" alt="<?= e($r['title']) ?>">
          <div>
            <h4><a href="<?= url('game_details.php?id=' . (int)$r['game_id']) ?>"><?= e($r['title']) ?></a></h4>
            <div class="card-rating"><?= star_html($r['rating']) ?><span><?= time_ago($r['review_date']) ?></span></div>
            <?php if ($r['comment']): ?>
              <p style="color:var(--text-dim);font-size:.9rem;margin-top:6px"><?= nl2br(e($r['comment'])) ?></p>
            <?php endif; ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px">
            <a class="btn btn-ghost btn-sm" href="<?= url('game_details.php?id=' . (int)$r['game_id']) ?>">Edit</a>
            <form method="post" action="<?= url('backend/review_actions.php') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
              <input type="hidden" name="game_id" value="<?= (int)$r['game_id'] ?>">
              <input type="hidden" name="return" value="reviews.php">
              <button class="btn btn-danger btn-sm" data-confirm="Delete this review?">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h3>Waiting for your verdict</h3>
    <?php if (!$unreviewed): ?>
      <p style="color:var(--text-dim);font-size:.92rem">Every game you own has been reviewed. Nice.</p>
    <?php else: ?>
      <?php foreach ($unreviewed as $u): ?>
        <div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)">
          <img src="<?= cover_url($u['cover_image']) ?>" alt="" style="width:44px;height:58px;object-fit:cover;border-radius:7px">
          <div style="flex:1">
            <div style="font-weight:600;font-size:.93rem"><?= e($u['title']) ?></div>
            <a style="font-size:.83rem;color:var(--green)" href="<?= url('game_details.php?id=' . (int)$u['game_id']) ?>">Write a review</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
