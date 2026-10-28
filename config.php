<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * File Konfigurasi dan Koneksi Database (PHP Native MySQLi)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rayyan_kidswear');

// Koneksi ke Database menggunakan PHP Native MySQLi
$koneksi = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    http_response_code(500);
    die('Koneksi Database Gagal: ' . mysqli_connect_error() . '. Pastikan MySQL XAMPP aktif dan database rayyan_kidswear sudah diimpor.');
}

// Set karakter encoding UTF-8
mysqli_set_charset($koneksi, 'utf8mb4');

// Alias untuk kompatibilitas umum ($conn atau $koneksi)
$conn = $koneksi;

/**
 * Fungsi pembantu (Helper Functions)
 */

// Escape output HTML untuk mencegah XSS
function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

// Format mata uang Rupiah
function rp($nominal) {
    return 'Rp ' . number_format((float)$nominal, 0, ',', '.');
}

// Cek autentikasi login pengguna
function login_required() {
    if (empty($_SESSION['uid'])) {
        header('Location: login.php');
        exit;
    }
}

// Buat atau ambil CSRF Token untuk keamanan formulir
function token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Validasi CSRF Token pada request POST
function check_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $session_token = $_SESSION['csrf'] ?? '';
        $post_token = $_POST['csrf'] ?? '';
        if (!hash_equals($session_token, $post_token)) {
            http_response_code(419);
            exit('Token formulir tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
        }
    }
}

// Set pesan flash notifikasi (success / error)
function msg($type, $text) {
    $_SESSION['msg'] = [$type, $text];
}

// Redirect ke URL tujuan
function go($url) {
    header('Location: ' . $url);
    exit;
}
