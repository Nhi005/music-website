<?php
/* =========================
   DATABASE CONFIG
========================= */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'music_website');

define('SITE_NAME', 'THIISNTREAL');
/* =========================
   AUTO DETECT SITE URL
   (works for port 80 / 8080)
========================= */

// http or https
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// domain (localhost / example.com)
$host = $_SERVER['SERVER_NAME'];

// port
$port = $_SERVER['SERVER_PORT'];

// add port ONLY if not standard
if ($port != 80 && $port != 443) {
    $host .= ':' . $port;
}

// project folder
$projectFolder = '/music-website';

// FINAL URL
define('SITE_URL', $scheme . '://' . $host . $projectFolder);
// define('BASE_URL', $projectFolder);
define('BASE_URL', SITE_URL);



/* =========================
   DATABASE CONNECTION
========================= */
function getDB() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("DB Connection failed: " . $e->getMessage());
    }
}


/* =========================
   SESSION
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================
   AUTH HELPERS
========================= */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}


/* =========================
   REDIRECT
========================= */
function redirect($path = '') {
    header("Location: " . SITE_URL . '/' . ltrim($path, '/'));
    exit();
}
