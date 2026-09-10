<?php
/**
 * FILE: backend/header.php
 * Shared navigation for every customer facing page.
 * Set $pageTitle before including this file.
 */
require_once __DIR__ . '/functions.php';
$navCartCount = cart_count($pdo, current_user_id());
$navWishCount = wishlist_count($pdo, current_user_id());
$currentPage  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Browse games') ?> &middot; <?= e(SITE_NAME) ?></title>
<link rel="icon" href="<?= url('images/placeholder.svg') ?>">
<link rel="stylesheet" href="<?= url('css/style.css') ?>">
<script>
  window.BASE_URL   = <?= json_encode(BASE_URL) ?>;
  window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
</script>
</head>
<body>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= url('index.php') ?>">
      <span class="brand-mark">NX</span>
      <span class="brand-text">NEXUS<em>Games</em></span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <nav class="main-nav" id="mainNav">
      <a href="<?= url('index.php') ?>"  class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="<?= url('games.php') ?>"  class="<?= $currentPage === 'games.php' ? 'active' : '' ?>">Store</a>
      <?php if (is_logged_in()): ?>
        <a href="<?= url('library.php') ?>" class="<?= $currentPage === 'library.php' ? 'active' : '' ?>">Library</a>
        <a href="<?= url('orders.php') ?>"  class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
      <?php endif; ?>
      <a href="<?= url('about.php') ?>"   class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a>
      <a href="<?= url('contact.php') ?>" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Contact</a>
    </nav>

    <form class="header-search" action="<?= url('games.php') ?>" method="get" role="search">
      <input type="search" name="search" placeholder="Search games, studios, genres"
             value="<?= e($_GET['search'] ?? '') ?>" aria-label="Search games">
      <button type="submit" aria-label="Search">&#9906;</button>
    </form>

    <div class="header-actions">
      <a class="icon-btn" href="<?= url('wishlist.php') ?>" title="Wishlist">
        &#9825;<?php if ($navWishCount): ?><span class="badge"><?= $navWishCount ?></span><?php endif; ?>
      </a>
      <a class="icon-btn" href="<?= url('cart.php') ?>" title="Cart">
        &#128722;<?php if ($navCartCount): ?><span class="badge"><?= $navCartCount ?></span><?php endif; ?>
      </a>

      <?php if (is_logged_in()): ?>
        <div class="user-menu">
          <button class="user-chip" id="userChip">
            <?= e(strtoupper(substr(current_user_name(), 0, 1))) ?>
          </button>
          <div class="dropdown" id="userDropdown">
            <span class="dropdown-name"><?= e(current_user_name()) ?></span>
            <a href="<?= url('profile.php') ?>">Profile</a>
            <a href="<?= url('orders.php') ?>">My orders</a>
            <a href="<?= url('library.php') ?>">My library</a>
            <a href="<?= url('reviews.php') ?>">My reviews</a>
            <?php if (is_admin()): ?>
              <a href="<?= url('admin/dashboard.php') ?>">Admin panel</a>
            <?php endif; ?>
            <a class="danger" href="<?= url('logout.php') ?>">Log out</a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= url('login.php') ?>">Log in</a>
        <a class="btn btn-primary btn-sm" href="<?= url('register.php') ?>">Sign up</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php $flashes = take_flash(); if ($flashes): ?>
<div class="wrap flash-wrap">
  <?php foreach ($flashes as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?><button class="flash-close">&times;</button></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<main>
