<?php
/**
 * FILE: backend/auth.php
 * Handles registration, login and logout for customers and admins.
 * Passwords are hashed with password_hash() and checked with password_verify().
 */
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}
verify_csrf();

$action = $_POST['action'] ?? '';

/* ----------------------------- REGISTER ----------------------------- */
if ($action === 'register') {

    $name     = clean($_POST['name'] ?? '');
    $email    = strtolower(clean($_POST['email'] ?? ''));
    $phone    = clean($_POST['phone'] ?? '');
    $address  = clean($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $errors = [];
    if ($name === '' || str_len($name) < 3)  $errors[] = 'Enter your full name (at least 3 characters).';
    if (!valid_email($email))                  $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6)                 $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                $errors[] = 'The two passwords do not match.';
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) $errors[] = 'Phone number looks invalid.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'That email is already registered. Try logging in.';
        }
    }

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old']    = ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address];
        redirect('register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, "customer")'
    );
    $stmt->execute([$name, $email, $hash, $phone ?: null, $address ?: null]);
    $userId = (int)$pdo->lastInsertId();

    get_or_create_cart($pdo, $userId);

    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['role']      = 'customer';

    set_flash('success', 'Welcome aboard, ' . $name . '. Your account is ready.');
    redirect('index.php');
}

/* ------------------------------- LOGIN ------------------------------ */
if ($action === 'login') {

    $email    = strtolower(clean($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $adminGate = !empty($_POST['admin_only']);

    $stmt = $pdo->prepare('SELECT user_id, name, password, role, status FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        set_flash('error', 'Email or password is incorrect.');
        $_SESSION['form_old'] = ['email' => $email];
        redirect($adminGate ? 'admin/login.php' : 'login.php');
    }

    if ($user['status'] === 'blocked') {
        set_flash('error', 'This account has been blocked. Contact support.');
        redirect($adminGate ? 'admin/login.php' : 'login.php');
    }

    if ($adminGate && $user['role'] !== 'admin') {
        set_flash('error', 'That account does not have admin access.');
        redirect('admin/login.php');
    }

    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role']      = $user['role'];

    get_or_create_cart($pdo, (int)$user['user_id']);

    set_flash('success', 'Logged in as ' . $user['name'] . '.');

    if ($adminGate || $user['role'] === 'admin') {
        redirect($adminGate ? 'admin/dashboard.php' : 'index.php');
    }

    $target = $_SESSION['redirect_after_login'] ?? url('index.php');
    unset($_SESSION['redirect_after_login']);
    redirect($target);
}

/* ------------------------- UPDATE PROFILE --------------------------- */
if ($action === 'update_profile') {
    require_login();

    $name    = clean($_POST['name'] ?? '');
    $phone   = clean($_POST['phone'] ?? '');
    $address = clean($_POST['address'] ?? '');

    if ($name === '') {
        set_flash('error', 'Name cannot be empty.');
        redirect('profile.php');
    }

    $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?');
    $stmt->execute([$name, $phone ?: null, $address ?: null, current_user_id()]);
    $_SESSION['user_name'] = $name;

    set_flash('success', 'Profile updated.');
    redirect('profile.php');
}

/* ------------------------- CHANGE PASSWORD -------------------------- */
if ($action === 'change_password') {
    require_login();

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
    $stmt->execute([current_user_id()]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        set_flash('error', 'Your current password is not correct.');
        redirect('profile.php');
    }
    if (strlen($new) < 6) {
        set_flash('error', 'New password must be at least 6 characters.');
        redirect('profile.php');
    }
    if ($new !== $confirm) {
        set_flash('error', 'New passwords do not match.');
        redirect('profile.php');
    }

    $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), current_user_id()]);

    set_flash('success', 'Password changed.');
    redirect('profile.php');
}

redirect('index.php');
