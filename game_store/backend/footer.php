</main>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <a class="brand" href="<?= url('index.php') ?>">
        <span class="brand-mark">NX</span>
        <span class="brand-text">NEXUS<em>Games</em></span>
      </a>
      <p class="footer-note">A student built game marketplace running on PHP, MySQL and plain JavaScript. Built for the Database Management Systems course.</p>
    </div>
    <div>
      <h4>Store</h4>
      <a href="<?= url('games.php') ?>">All games</a>
      <a href="<?= url('games.php?sort=newest') ?>">New releases</a>
      <a href="<?= url('games.php?sort=popular') ?>">Popular</a>
      <a href="<?= url('games.php?discounted=1') ?>">On sale</a>
    </div>
    <div>
      <h4>Account</h4>
      <a href="<?= url('login.php') ?>">Log in</a>
      <a href="<?= url('register.php') ?>">Create account</a>
      <a href="<?= url('orders.php') ?>">Order history</a>
      <a href="<?= url('library.php') ?>">Game library</a>
    </div>
    <div>
      <h4>Help</h4>
      <a href="<?= url('about.php') ?>">About us</a>
      <a href="<?= url('contact.php') ?>">Contact</a>
      <a href="<?= url('admin/login.php') ?>">Staff login</a>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Course project, not a real storefront.</span>
    <span>PHP &middot; MySQL &middot; HTML &middot; CSS &middot; JavaScript</span>
  </div>
</footer>

<script src="<?= url('js/main.js') ?>"></script>
</body>
</html>
