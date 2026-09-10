<?php
/**
 * FILE: games.php - catalogue with search, filters, sorting and pagination.
 * Every filter value is bound through a prepared statement.
 */
require_once __DIR__ . '/backend/functions.php';
$pageTitle = 'Store';

$search     = clean($_GET['search'] ?? '');
$genreId    = (int)($_GET['genre'] ?? 0);
$platformId = (int)($_GET['platform'] ?? 0);
$categoryId = (int)($_GET['category'] ?? 0);
$maxPrice   = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$minRating  = (float)($_GET['rating'] ?? 0);
$discounted = !empty($_GET['discounted']);
$sort       = $_GET['sort'] ?? 'newest';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 12;

$where  = ["v.status = 'active'"];
$params = [];

if ($search !== '') {
    // Search covers title, developer, publisher and genre
    $where[] = '(v.title LIKE ? OR v.developer_name LIKE ? OR v.publisher_name LIKE ? OR v.genres LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($genreId) {
    $where[] = 'EXISTS (SELECT 1 FROM game_genres gg WHERE gg.game_id = v.game_id AND gg.genre_id = ?)';
    $params[] = $genreId;
}
if ($platformId) {
    $where[] = 'EXISTS (SELECT 1 FROM game_platforms gp WHERE gp.game_id = v.game_id AND gp.platform_id = ?)';
    $params[] = $platformId;
}
if ($categoryId) { $where[] = 'v.category_id = ?';    $params[] = $categoryId; }
if ($maxPrice > 0) { $where[] = 'v.final_price <= ?'; $params[] = $maxPrice; }
if ($minRating > 0) { $where[] = 'v.avg_rating >= ?'; $params[] = $minRating; }
if ($discounted)    { $where[] = 'v.discount_percentage > 0'; }

$whereSql = 'WHERE ' . implode(' AND ', $where);

$orderMap = [
    'price_low'  => 'v.final_price ASC',
    'price_high' => 'v.final_price DESC',
    'rating'     => 'v.avg_rating DESC, v.review_count DESC',
    'popular'    => 'v.units_sold DESC, v.avg_rating DESC',
    'newest'     => 'v.release_date DESC',
    'title'      => 'v.title ASC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['newest'];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM game_catalog_view v $whereSql");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listStmt = $pdo->prepare("SELECT * FROM game_catalog_view v $whereSql ORDER BY $orderSql LIMIT $perPage OFFSET $offset");
$listStmt->execute($params);
$games = $listStmt->fetchAll();

$wishIds = [];
if (is_logged_in()) {
    $w = $pdo->prepare('SELECT game_id FROM wishlists WHERE user_id = ?');
    $w->execute([current_user_id()]);
    $wishIds = array_map('intval', $w->fetchAll(PDO::FETCH_COLUMN));
}

$genres     = all_genres($pdo);
$platforms  = all_platforms($pdo);
$categories = all_categories($pdo);
$priceCap   = (float)$pdo->query('SELECT CEIL(MAX(price)) FROM games')->fetchColumn();

function pageLink($overrides = []) {
    $q = array_merge($_GET, $overrides);
    return url('games.php?' . http_build_query(array_filter($q, function ($v) { return $v !== '' && $v !== null; })));
}

require __DIR__ . '/backend/header.php';
?>

<div class="wrap page-head">
  <h1><?= $search !== '' ? 'Results for "' . e($search) . '"' : 'All games' ?></h1>
  <p>Filter the catalogue by genre, platform, category, price and rating.</p>
</div>

<div class="wrap store-layout">

  <form class="filter-panel panel" id="filterForm" method="get" action="<?= url('games.php') ?>">
    <input type="hidden" name="search" value="<?= e($search) ?>">
    <h3>Filters</h3>

    <div class="filter-group">
      <h4>Genre</h4>
      <select class="input" name="genre">
        <option value="">Every genre</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= (int)$g['genre_id'] ?>" <?= $genreId === (int)$g['genre_id'] ? 'selected' : '' ?>>
            <?= e($g['genre_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <h4>Platform</h4>
      <select class="input" name="platform">
        <option value="">Every platform</option>
        <?php foreach ($platforms as $p): ?>
          <option value="<?= (int)$p['platform_id'] ?>" <?= $platformId === (int)$p['platform_id'] ? 'selected' : '' ?>>
            <?= e($p['platform_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <h4>Category</h4>
      <select class="input" name="category">
        <option value="">Every category</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['category_id'] ?>" <?= $categoryId === (int)$c['category_id'] ? 'selected' : '' ?>>
            <?= e($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <h4>Max price <span id="maxPriceOut"><?= $maxPrice > 0 ? '$' . (int)$maxPrice : 'any' ?></span></h4>
      <input type="range" id="maxPrice" name="max_price" min="0" max="<?= (int)$priceCap ?>" step="5"
             value="<?= (int)$maxPrice ?>" style="width:100%">
    </div>

    <div class="filter-group">
      <h4>Minimum rating</h4>
      <select class="input" name="rating">
        <option value="">Any rating</option>
        <option value="4" <?= $minRating == 4 ? 'selected' : '' ?>>4 stars and up</option>
        <option value="3" <?= $minRating == 3 ? 'selected' : '' ?>>3 stars and up</option>
        <option value="2" <?= $minRating == 2 ? 'selected' : '' ?>>2 stars and up</option>
      </select>
    </div>

    <div class="filter-group">
      <label style="display:flex;gap:9px;align-items:center;font-size:.9rem;color:var(--text-dim);cursor:pointer">
        <input type="checkbox" name="discounted" value="1" <?= $discounted ? 'checked' : '' ?>>
        On sale only
      </label>
    </div>

    <button class="btn btn-primary btn-block btn-sm" type="submit">Apply filters</button>
    <a class="btn btn-ghost btn-block btn-sm" style="margin-top:8px" href="<?= url('games.php') ?>">Reset</a>
  </form>

  <div>
    <div class="toolbar">
      <span class="result-count"><strong><?= $totalRows ?></strong> game<?= $totalRows === 1 ? '' : 's' ?> found</span>
      <form method="get" action="<?= url('games.php') ?>" style="display:flex;gap:8px;align-items:center">
        <?php foreach (['search','genre','platform','category','max_price','rating','discounted'] as $keep): ?>
          <?php if (!empty($_GET[$keep])): ?>
            <input type="hidden" name="<?= $keep ?>" value="<?= e($_GET[$keep]) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
        <label for="sortSelect" style="font-size:.85rem;color:var(--text-faint)">Sort</label>
        <select class="input" id="sortSelect" name="sort" onchange="this.form.submit()" style="width:auto">
          <option value="newest"     <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
          <option value="popular"    <?= $sort === 'popular' ? 'selected' : '' ?>>Most popular</option>
          <option value="rating"     <?= $sort === 'rating' ? 'selected' : '' ?>>Highest rated</option>
          <option value="price_low"  <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
          <option value="title"      <?= $sort === 'title' ? 'selected' : '' ?>>Title A to Z</option>
        </select>
      </form>
    </div>

    <?php if (!$games): ?>
      <div class="panel empty">
        <div class="glyph">&#9788;</div>
        <h3>Nothing matched those filters</h3>
        <p>Try widening the price range or clearing the genre filter.</p>
        <a class="btn btn-primary" href="<?= url('games.php') ?>">Reset filters</a>
      </div>
    <?php else: ?>
      <div class="game-grid">
        <?php foreach ($games as $game) require __DIR__ . '/backend/game_card.php'; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination">
          <?php if ($page > 1): ?>
            <a href="<?= pageLink(['page' => $page - 1]) ?>">Prev</a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= pageLink(['page' => $i]) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="<?= pageLink(['page' => $page + 1]) ?>">Next</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/backend/footer.php'; ?>
