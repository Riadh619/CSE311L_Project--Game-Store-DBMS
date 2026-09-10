<?php
/** FILE: library.php - games the logged in user has purchased */
require_once __DIR__ . '/backend/functions.php';
require_login();
$pageTitle = 'My library';

$stmt = $pdo->prepare(
    'SELECT l.library_id, l.license_key, l.download_url, l.acquired_at, l.order_id,
            v.game_id, v.title, v.cover_image, v.genres, v.developer_name, v.avg_rating,
            (SELECT rating FROM reviews r WHERE r.game_id = v.game_id AND r.user_id = l.user_id) AS my_rating
       FROM library l JOIN game_catalog_view v ON v.game_id = l.game_id
      WHERE l.user_id = ?
      ORDER BY l.acquired_at DESC'
);
$stmt->execute([current_user_id()]);
$games = $stmt->fetchAll();

require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Your library</h1>
  <p><?= count($games) ?> game<?= count($games) === 1 ? '' : 's' ?> owned. Licence keys are yours to keep.</p>
</div>

<div class="wrap">
<?php if (!$games): ?>
  <div class="panel empty">
    <div class="glyph">&#127918;</div>
    <h3>Your library is empty</h3>
    <p>Anything you buy lands here straight away, with a licence key.</p>
    <a class="btn btn-primary" href="<?= url('games.php') ?>">Find something to play</a>
  </div>
<?php else: ?>
  <div class="panel">
    <?php foreach ($games as $g): ?>
      <div class="cart-item">
        <a href="<?= url('game_details.php?id=' . (int)$g['game_id']) ?>">
          <img src="<?= cover_url($g['cover_image']) ?>" alt="<?= e($g['title']) ?>">
        </a>
        <div>
          <h4><a href="<?= url('game_details.php?id=' . (int)$g['game_id']) ?>"><?= e($g['title']) ?></a></h4>
          <p class="card-meta"><?= e($g['developer_name']) ?> &middot; <?= e($g['genres']) ?></p>
          <p style="font-size:.83rem;margin-top:6px">
            <span style="color:var(--text-faint)">Licence</span>
            <code style="color:var(--blue)"><?= e($g['license_key']) ?></code>
          </p>
          <p style="font-size:.8rem;color:var(--text-faint);margin-top:3px">
            Added <?= date('d M Y', strtotime($g['acquired_at'])) ?>
            <?php if ($g['order_id']): ?>
              &middot; <a href="<?= url('order_details.php?id=' . (int)$g['order_id']) ?>" style="color:var(--text-dim)">order #<?= (int)$g['order_id'] ?></a>
            <?php endif; ?>
          </p>
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end">
          <a class="btn btn-blue btn-sm" href="<?= url($g['download_url'] ?: 'library.php') ?>"
             onclick="alert('Download is simulated for this project.');return false;">Download</a>
          <a class="btn btn-ghost btn-sm" href="<?= url('game_details.php?id=' . (int)$g['game_id']) ?>#review">
            <?= $g['my_rating'] ? 'Edit review' : 'Write review' ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
