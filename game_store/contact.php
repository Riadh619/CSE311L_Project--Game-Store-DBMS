<?php
/** FILE: contact.php */
require_once __DIR__ . '/backend/functions.php';
$pageTitle = 'Contact';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $message = clean($_POST['message'] ?? '');

    if ($name === '' || !valid_email($email) || $message === '') {
        set_flash('error', 'Fill in your name, a valid email and a message.');
    } else {
        // A production build would store or email this. The project keeps it simple.
        set_flash('success', 'Thanks ' . $name . ', your message has been received.');
        $sent = true;
    }
}
require __DIR__ . '/backend/header.php';
?>
<div class="wrap page-head">
  <h1>Contact the store</h1>
  <p>Questions about an order, a licence key or a refund? Send them here.</p>
</div>

<div class="wrap cart-layout">
  <div class="panel">
    <h3>Send a message</h3>
    <form method="post" action="<?= url('contact.php') ?>">
      <?= csrf_field() ?>
      <div class="form-grid-2">
        <div class="form-row">
          <label for="name">Your name</label>
          <input class="input" type="text" id="name" name="name" required
                 value="<?= $sent ? '' : e(current_user_name()) ?>">
        </div>
        <div class="form-row">
          <label for="email">Your email</label>
          <input class="input" type="email" id="email" name="email" required>
        </div>
      </div>
      <div class="form-row">
        <label for="message">Message</label>
        <textarea class="input" id="message" name="message" required placeholder="How can we help?"></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Send message</button>
    </form>
  </div>

  <div>
    <div class="panel">
      <h3>Support hours</h3>
      <div class="summary-line"><span>Sunday to Thursday</span><span>10:00 - 18:00</span></div>
      <div class="summary-line"><span>Friday</span><span>Closed</span></div>
      <div class="summary-line"><span>Saturday</span><span>12:00 - 16:00</span></div>
    </div>
    <div class="panel">
      <h3>Reach us</h3>
      <div class="summary-line"><span>Email</span><span>support@nexusgames.test</span></div>
      <div class="summary-line"><span>Phone</span><span>+880 1711 000000</span></div>
      <div class="summary-line"><span>Office</span><span>Dhaka, Bangladesh</span></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/backend/footer.php'; ?>
