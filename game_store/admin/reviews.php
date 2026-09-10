<?php
/** FILE: admin/reviews.php - moderate customer reviews */
require_once __DIR__ . '/../backend/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $pdo->prepare('DELETE FROM reviews WHERE review_id = ?')->execute([(int)$_POST['review_id']]);
    set_flash('success', 'Review removed.');
    redirect('admin/reviews.php');
}

$pageTitle = 'Reviews';
$minRating = (int)($_GET['rating'] ?? 0);

$sql = 'SELECT r.review_id, r.rating, r.comment, r.review_date,
               u.name AS reviewer, u.email, g.title, g.game_id
          FROM reviews r
          JOIN users u ON u.user_id = r.user_id
          JOIN games g ON g.game_id = r.game_id';
$params = [];
if ($minRating > 0) { $sql .= ' WHERE r.rating = ?'; $params[] = $minRating; }
$sql .= ' ORDER BY r.review_date DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$avg = $pdo->query('SELECT ROUND(AVG(rating),2) FROM reviews')->fetchColumn();
require __DIR__ . '/header.php';
?>
<div class="admin-toolbar">
  <div class="tab-row" style="margin:0">
    <a class="chip <?= $minRating === 0 ? 'active' : '' ?>" href="<?= url('admin/reviews.php') ?>">All</a>
    <?php for ($s = 5; $s >= 1; $s--): ?>
      <a class="chip <?= $minRating === $s ? 'active' : '' ?>" href="<?= url('admin/reviews.php?rating=' . $s) ?>"><?= $s ?> star</a>
    <?php endfor; ?>
  </div>
  <span class="result-count">Store average <strong><?= $avg ?: '0' ?></strong> from <strong><?= count($reviews) ?></strong> shown</span>
</div>

<div class="table-wrap">
  <table class="data">
    <thead><tr><th>Game</th><th>Reviewer</th><th>Rating</th><th>Comment</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reviews as $r): ?>
      <tr>
        <td><a href="<?= url('game_details.php?id=' . (int)$r['game_id']) ?>"><strong><?= e($r['title']) ?></strong></a></td>
        <td>
          <?= e($r['reviewer']) ?>
          <div style="font-size:.78rem;color:var(--text-faint)"><?= e($r['email']) ?></div>
        </td>
        <td style="white-space:nowrap"><?= star_html($r['rating']) ?></td>
        <td style="color:var(--text-dim);font-size:.87rem;max-width:340px"><?= e($r['comment'] ?: 'No comment') ?></td>
        <td style="font-size:.83rem"><?= date('d M Y', strtotime($r['review_date'])) ?></td>
        <td>
          <form class="inline-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
            <button class="btn btn-danger btn-sm" data-confirm="Delete this review permanently?">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
