<?php
/** FILE: index.php - storefront homepage */
require_once __DIR__ . '/backend/functions.php';
$pageTitle = 'Home';

// Wishlist ids so hearts render in the right state
$wishIds = [];
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT game_id FROM wishlists WHERE user_id = ?');
    $stmt->execute([current_user_id()]);
    $wishIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$featured = $pdo->query(
    "SELECT * FROM game_catalog_view
      WHERE status = 'active' AND category_name = 'Featured'
      ORDER BY avg_rating DESC LIMIT 4"
)->fetchAll();

$popular = $pdo->query(
    "SELECT * FROM game_catalog_view
      WHERE status = 'active'
      ORDER BY units_sold DESC, avg_rating DESC LIMIT 5"
)->fetchAll();

$newest = $pdo->query(
    "SELECT * FROM game_catalog_view
      WHERE status = 'active'
      ORDER BY release_date DESC LIMIT 5"
)->fetchAll();

$deals = $pdo->query(
    "SELECT * FROM game_catalog_view
      WHERE status = 'active' AND discount_percentage > 0
      ORDER BY discount_percentage DESC LIMIT 5"
)->fetchAll();

$heroGame = $featured[0] ?? ($popular[0] ?? null);

$stats = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM games WHERE status='active')      AS games,
            (SELECT COUNT(*) FROM users WHERE role='customer')      AS players,
            (SELECT COUNT(*) FROM developers)                       AS studios,
            (SELECT COUNT(*) FROM reviews)                          AS reviews"
)->fetch();

$genres = all_genres($pdo);

require __DIR__ . '/backend/header.php';
?>

<section class="wrap">
  <div class="hero">
    <div class="hero-content">
      <span class="eyebrow"><span class="dot"></span> Live store &middot; <?= (int)$stats['games'] ?> titles</span>
      <h1>Buy games. <span>Own them properly.</span></h1>
      <p>
        Every purchase drops straight into your library with a licence key, no launcher required.
        Browse by genre, platform or price, and pay in whatever way suits you.
      </p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= url('games.php') ?>">Browse the store</a>
        <?php if ($heroGame): ?>
          <a class="btn btn-ghost btn-lg" href="<?= url('game_details.php?id=' . (int)$heroGame['game_id']) ?>">
            Featured: <?= e($heroGame['title']) ?>
          </a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div><strong><?= (int)$stats['games'] ?></strong><span>Games</span></div>
        <div><strong><?= (int)$stats['players'] ?></strong><span>Players</span></div>
        <div><strong><?= (int)$stats['studios'] ?></strong><span>Studios</span></div>
        <div><strong><?= (int)$stats['reviews'] ?></strong><span>Reviews</span></div>
      </div>
    </div>
  </div>
</section>

<section class="wrap section">
  <div class="section-head">
    <h2>Browse by genre<small>Ten genres across fifteen titles</small></h2>
    <a class="link" href="<?= url('games.php') ?>">See all games &rarr;</a>
  </div>
  <div class="chip-row">
    <?php foreach ($genres as $g): ?>
      <a class="chip" href="<?= url('games.php?genre=' . (int)$g['genre_id']) ?>"><?= e($g['genre_name']) ?></a>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($featured): ?>
<section class="wrap section">
  <div class="section-head">
    <h2>Featured this week<small>Hand picked by the store team</small></h2>
    <a class="link" href="<?= url('games.php?category=1') ?>">More featured &rarr;</a>
  </div>
  <div class="game-grid">
    <?php foreach ($featured as $game) require __DIR__ . '/backend/game_card.php'; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($deals): ?>
<section class="wrap section">
  <div class="section-head">
    <h2>On sale now<small>Discounts end when the campaign date passes</small></h2>
    <a class="link" href="<?= url('games.php?discounted=1') ?>">All deals &rarr;</a>
  </div>
  <div class="game-grid">
    <?php foreach ($deals as $game) require __DIR__ . '/backend/game_card.php'; ?>
  </div>
</section>
<?php endif; ?>

<section class="wrap section">
  <div class="section-head">
    <h2>Most bought<small>Ranked by units sold across paid orders</small></h2>
    <a class="link" href="<?= url('games.php?sort=popular') ?>">See ranking &rarr;</a>
  </div>
  <div class="game-grid">
    <?php foreach ($popular as $game) require __DIR__ . '/backend/game_card.php'; ?>
  </div>
</section>

<section class="wrap section">
  <div class="section-head">
    <h2>New releases<small>Freshest release dates in the catalogue</small></h2>
    <a class="link" href="<?= url('games.php?sort=newest') ?>">See all &rarr;</a>
  </div>
  <div class="game-grid">
    <?php foreach ($newest as $game) require __DIR__ . '/backend/game_card.php'; ?>
  </div>
</section>

<?php require __DIR__ . '/backend/footer.php'; ?>
