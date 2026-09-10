<?php
/**
 * FILE: admin/header.php
 * Sidebar shell for every admin screen. Guards access before any output.
 */
require_once __DIR__ . '/../backend/functions.php';
require_admin();
$adminPage = basename($_SERVER['PHP_SELF']);

function navlink($file, $label, $glyph) {
    global $adminPage;
    $active = $adminPage === $file ? ' active' : '';
    echo '<a class="nav-link' . $active . '" href="' . url('admin/' . $file) . '">'
       . '<span class="nav-glyph">' . $glyph . '</span>' . e($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> &middot; <?= e(SITE_NAME) ?> admin</title>
<link rel="stylesheet" href="<?= url('css/style.css') ?>">
<link rel="stylesheet" href="<?= url('css/admin.css') ?>">
<script>
  window.BASE_URL   = <?= json_encode(BASE_URL) ?>;
  window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
</script>
</head>
<body>
<div class="admin-body">

  <aside class="admin-side">
    <a class="brand" href="<?= url('admin/dashboard.php') ?>">
      <span class="brand-mark">NX</span>
      <span class="brand-text">Admin<em>Panel</em></span>
    </a>

    <h5>Overview</h5>
    <?php navlink('dashboard.php', 'Dashboard', '&#9632;'); ?>

    <h5>Catalogue</h5>
    <?php
      navlink('games.php', 'Games', '&#127918;');
      navlink('add_game.php', 'Add game', '&#43;');
      navlink('categories.php', 'Categories', '&#9776;');
      navlink('genres.php', 'Genres', '&#9863;');
      navlink('discounts.php', 'Discounts', '&#37;');
      navlink('inventory.php', 'Inventory', '&#128230;');
    ?>

    <h5>Commerce</h5>
    <?php
      navlink('orders.php', 'Orders', '&#128179;');
      navlink('users.php', 'Users', '&#128100;');
      navlink('reviews.php', 'Reviews', '&#9733;');
    ?>

    <h5>Session</h5>
    <a class="nav-link" href="<?= url('index.php') ?>"><span class="nav-glyph">&#8599;</span>View storefront</a>
    <a class="nav-link" href="<?= url('logout.php') ?>"><span class="nav-glyph">&#8592;</span>Log out</a>
  </aside>

  <div class="admin-main">
    <div class="admin-top">
      <h1><?= e($pageTitle ?? 'Admin') ?></h1>
      <span class="who">Signed in as <strong style="color:var(--text)"><?= e(current_user_name()) ?></strong></span>
    </div>

    <div class="admin-inner">
      <?php foreach (take_flash() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" style="margin-bottom:14px">
          <?= e($f['message']) ?><button class="flash-close">&times;</button>
        </div>
      <?php endforeach; ?>
