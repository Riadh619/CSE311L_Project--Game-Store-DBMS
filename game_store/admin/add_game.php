<?php
/** FILE: admin/add_game.php - create a game with genres, platforms and an optional cover upload */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title       = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $developerId = (int)($_POST['developer_id'] ?? 0);
    $publisherId = (int)($_POST['publisher_id'] ?? 0);
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $releaseDate = $_POST['release_date'] ?? '';
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $status      = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $genreIds    = array_map('intval', $_POST['genres'] ?? []);
    $platformIds = array_map('intval', $_POST['platforms'] ?? []);
    $old = compact('title','description','developerId','publisherId','categoryId','releaseDate','price','stock','status');

    if ($title === '')                              $errors[] = 'Title is required.';
    if ($description === '')                        $errors[] = 'Description is required.';
    if (!$developerId)                              $errors[] = 'Pick a developer.';
    if (!$publisherId)                              $errors[] = 'Pick a publisher.';
    if (!$releaseDate || !strtotime($releaseDate))  $errors[] = 'Enter a valid release date.';
    if ($price < 0)                                 $errors[] = 'Price cannot be negative.';
    if ($stock < 0)                                 $errors[] = 'Stock cannot be negative.';
    if (!$genreIds)                                 $errors[] = 'Choose at least one genre.';
    if (!$platformIds)                              $errors[] = 'Choose at least one platform.';

    // Optional cover upload
    $coverPath = null;
    if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        $mime    = mime_content_type($_FILES['cover']['tmp_name']);
        if (!isset($allowed[$mime])) {
            $errors[] = 'Cover must be a JPG, PNG, WEBP or SVG file.';
        } elseif ($_FILES['cover']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Cover must be smaller than 3 MB.';
        } else {
            $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($title));
            $file = trim($slug, '_') . '_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($_FILES['cover']['tmp_name'], __DIR__ . '/../images/covers/' . $file)) {
                $coverPath = 'covers/' . $file;
            } else {
                $errors[] = 'Could not save the uploaded cover.';
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                'INSERT INTO games (title, description, developer_id, publisher_id, category_id,
                                    release_date, price, stock, cover_image, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$title, $description, $developerId, $publisherId, $categoryId ?: null,
                        $releaseDate, $price, $stock, $coverPath, $status]);

            $gameId = (int)$pdo->lastInsertId();

            $gStmt = $pdo->prepare('INSERT INTO game_genres (game_id, genre_id) VALUES (?, ?)');
            foreach ($genreIds as $gid) { $gStmt->execute([$gameId, $gid]); }

            $pStmt = $pdo->prepare('INSERT INTO game_platforms (game_id, platform_id) VALUES (?, ?)');
            foreach ($platformIds as $pid) { $pStmt->execute([$gameId, $pid]); }

            $pdo->commit();
            set_flash('success', $title . ' added to the catalogue.');
            redirect('admin/games.php');

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Could not save the game: ' . $ex->getMessage();
        }
    }
}

$pageTitle  = 'Add game';
$developers = all_developers($pdo);
$publishers = all_publishers($pdo);
$categories = all_categories($pdo);
$genres     = all_genres($pdo);
$platforms  = all_platforms($pdo);
require __DIR__ . '/header.php';
?>
<?php if ($errors): ?>
  <ul class="error-list"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<form class="panel" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="form-row">
    <label for="title">Title</label>
    <input class="input" type="text" id="title" name="title" required value="<?= e($old['title'] ?? '') ?>">
  </div>

  <div class="form-row">
    <label for="description">Description</label>
    <textarea class="input" id="description" name="description" required><?= e($old['description'] ?? '') ?></textarea>
  </div>

  <div class="form-grid-2">
    <div class="form-row">
      <label for="developer_id">Developer</label>
      <select class="input" id="developer_id" name="developer_id" required>
        <option value="">Select a developer</option>
        <?php foreach ($developers as $d): ?>
          <option value="<?= (int)$d['developer_id'] ?>" <?= ($old['developerId'] ?? 0) === (int)$d['developer_id'] ? 'selected' : '' ?>>
            <?= e($d['developer_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="publisher_id">Publisher</label>
      <select class="input" id="publisher_id" name="publisher_id" required>
        <option value="">Select a publisher</option>
        <?php foreach ($publishers as $p): ?>
          <option value="<?= (int)$p['publisher_id'] ?>" <?= ($old['publisherId'] ?? 0) === (int)$p['publisher_id'] ? 'selected' : '' ?>>
            <?= e($p['publisher_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-grid-2">
    <div class="form-row">
      <label for="category_id">Category</label>
      <select class="input" id="category_id" name="category_id">
        <option value="">No category</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['category_id'] ?>" <?= ($old['categoryId'] ?? 0) === (int)$c['category_id'] ? 'selected' : '' ?>>
            <?= e($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="release_date">Release date</label>
      <input class="input" type="date" id="release_date" name="release_date" required value="<?= e($old['releaseDate'] ?? '') ?>">
    </div>
  </div>

  <div class="form-grid-2">
    <div class="form-row">
      <label for="price">Price (USD)</label>
      <input class="input" type="number" id="price" name="price" step="0.01" min="0" required value="<?= e($old['price'] ?? '') ?>">
    </div>
    <div class="form-row">
      <label for="stock">Stock</label>
      <input class="input" type="number" id="stock" name="stock" min="0" required value="<?= e($old['stock'] ?? 0) ?>">
    </div>
  </div>

  <div class="form-row">
    <label>Genres</label>
    <div class="chip-row">
      <?php foreach ($genres as $g): ?>
        <label class="chip" style="cursor:pointer">
          <input type="checkbox" name="genres[]" value="<?= (int)$g['genre_id'] ?>"> <?= e($g['genre_name']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-row">
    <label>Platforms</label>
    <div class="chip-row">
      <?php foreach ($platforms as $p): ?>
        <label class="chip" style="cursor:pointer">
          <input type="checkbox" name="platforms[]" value="<?= (int)$p['platform_id'] ?>"> <?= e($p['platform_name']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-grid-2">
    <div class="form-row">
      <label for="cover">Cover image</label>
      <input class="input" type="file" id="cover" name="cover" accept="image/*">
      <p class="form-hint">Optional. A placeholder is shown if you skip it.</p>
    </div>
    <div class="form-row">
      <label for="status">Status</label>
      <select class="input" id="status" name="status">
        <option value="active">Active, visible in the store</option>
        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive, hidden</option>
      </select>
    </div>
  </div>

  <button class="btn btn-primary btn-lg" type="submit">Add game</button>
  <a class="btn btn-ghost btn-lg" href="<?= url('admin/games.php') ?>">Cancel</a>
</form>
<?php require __DIR__ . '/footer.php'; ?>
