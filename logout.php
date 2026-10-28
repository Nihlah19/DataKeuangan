<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * Halaman Logout (PHP Native)
 */

require_once 'config.php';

// Bersihkan data sesi
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Alihkan kembali ke halaman login
header('Location: login.php');
exit;