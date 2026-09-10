<?php
/** FILE: logout.php */
require_once __DIR__ . '/backend/functions.php';
$_SESSION = [];
session_destroy();
session_start();
set_flash('success', 'You are logged out.');
redirect('index.php');
