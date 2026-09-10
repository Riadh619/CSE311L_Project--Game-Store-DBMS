<?php
/**
 * FILE: backend/database.php
 * Reusable PDO connection. Every page includes this (through functions.php).
 * Default XAMPP credentials: user root, empty password, port 3306.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'game_store');
define('DB_USER', 'root');
define('DB_PASS', '');

// Root URL of the project. Change this if you rename the htdocs folder.
define('BASE_URL', '/game_store/');
define('SITE_NAME', 'NEXUS Games');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die(
        '<div style="font-family:system-ui;background:#0b0d17;color:#e6e8f0;padding:40px">'
        . '<h2 style="color:#ff4d6d">Database connection failed</h2>'
        . '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>Start MySQL in the XAMPP control panel, then import <code>database/game_store.sql</code> through phpMyAdmin.</p>'
        . '</div>'
    );
}
