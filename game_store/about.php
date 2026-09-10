<?php
/** FILE: about.php */
require_once __DIR__ . '/backend/functions.php';
$pageTitle = 'About';
$counts = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM games) AS games,
            (SELECT COUNT(*) FROM users) AS users,
            (SELECT COUNT(*) FROM orders) AS orders,
            (SELECT COUNT(*) FROM developers) AS studios'
)->fetch();
require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>About this project</h1>
  <p>A working storefront built to demonstrate a relational database end to end.</p>
</div>

<div class="wrap cart-layout">
  <div>
    <div class="panel">
      <h3>What it is</h3>
      <p style="color:var(--text-dim)">
        NEXUS Games is a course project for a Database Management Systems module. It is a complete
        e-commerce flow rather than a mockup: registration writes a hashed password, the catalogue reads
        from SQL views, checkout runs inside a transaction, and every admin action is a real CRUD statement.
      </p>
    </div>

    <div class="panel">
      <h3>How a request travels</h3>
      <p style="color:var(--text-dim)">
        The browser posts a form, PHP validates the input and binds it to a prepared statement, MySQL
        answers, and the result renders back into the page. Nothing user supplied is ever concatenated
        into a query string.
      </p>
      <table class="spec-table" style="margin-top:14px">
        <tr><th>Frontend</th><td>HTML5, CSS3, vanilla JavaScript</td></tr>
        <tr><th>Backend</th><td>PHP 8 with PDO prepared statements</td></tr>
        <tr><th>Database</th><td>MySQL, normalised to third normal form</td></tr>
        <tr><th>Server</th><td>XAMPP, Apache and MySQL on localhost</td></tr>
        <tr><th>Security</th><td>password_hash, sessions, CSRF tokens, output escaping, role checks</td></tr>
      </table>
    </div>
  </div>

  <div>
    <div class="panel">
      <h3>Right now</h3>
      <div class="summary-line"><span>Games listed</span><span><?= (int)$counts['games'] ?></span></div>
      <div class="summary-line"><span>Registered users</span><span><?= (int)$counts['users'] ?></span></div>
      <div class="summary-line"><span>Orders placed</span><span><?= (int)$counts['orders'] ?></span></div>
      <div class="summary-line"><span>Studios</span><span><?= (int)$counts['studios'] ?></span></div>
    </div>
    <div class="panel">
      <h3>Database objects</h3>
      <p style="color:var(--text-dim);font-size:.9rem">
        17 tables, 5 views, foreign keys on every relationship, CHECK constraints on prices, quantities
        and ratings, and indexes on the columns the store filters by.
      </p>
      <a class="btn btn-ghost btn-block btn-sm" style="margin-top:10px" href="<?= url('games.php') ?>">See it working</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
