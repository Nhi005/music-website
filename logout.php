<?php
require_once 'includes/config.php'; // nếu header.php đã include config thì có thể đổi thành header.php
// Hoặc: require_once 'includes/header.php'; (nhưng logout thường không cần header)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xoá toàn bộ session
$_SESSION = [];

// Xoá cookie session (nếu có)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Huỷ session
session_destroy();

// Quay về trang chủ
header("Location: index.php");
exit;
