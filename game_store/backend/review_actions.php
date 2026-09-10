<?php
/**
 * FILE: backend/review_actions.php
 * Customers may only review a game that exists in their library.
 */
require_once __DIR__ . '/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf();

$userId = current_user_id();
$gameId = (int)($_POST['game_id'] ?? 0);
$action = $_POST['action'] ?? 'save';
$return = $_POST['return'] ?? ('game_details.php?id=' . $gameId);

if ($action === 'delete') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    // A customer can delete only their own review; an admin can delete any.
    if (is_admin()) {
        $pdo->prepare('DELETE FROM reviews WHERE review_id = ?')->execute([$reviewId]);
    } else {
        $pdo->prepare('DELETE FROM reviews WHERE review_id = ? AND user_id = ?')->execute([$reviewId, $userId]);
    }
    set_flash('success', 'Review deleted.');
    redirect($return);
}

$rating  = (int)($_POST['rating'] ?? 0);
$comment = clean($_POST['comment'] ?? '');

if ($rating < 1 || $rating > 5) {
    set_flash('error', 'Pick a rating between 1 and 5 stars.');
    redirect($return);
}
if (str_len($comment) > 1000) {
    set_flash('error', 'Keep your review under 1000 characters.');
    redirect($return);
}
if (!owns_game($pdo, $userId, $gameId)) {
    set_flash('error', 'You can only review games you have purchased.');
    redirect($return);
}

// One review per user per game: insert, or update the existing row.
$pdo->prepare(
    'INSERT INTO reviews (user_id, game_id, rating, comment)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), review_date = NOW()'
)->execute([$userId, $gameId, $rating, $comment !== '' ? $comment : null]);

set_flash('success', 'Thanks, your review is live.');
redirect($return);
