<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * Skrip Setup Akun Administrator Awal (PHP Native)
 * Buka file ini sekali di browser untuk membuat akun admin default.
 */

require_once 'config.php';

$username = 'admin';
$password = 'Rayyan123!';
$nama     = 'Admin Rayyan';
$role     = 'admin';

// Cek apakah tabel users sudah ada
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($check_table) === 0) {
    die("Tabel 'users' belum ditemukan. Silakan impor file 'database.sql' terlebih dahulu melalui phpMyAdmin.");
}

// Cek apakah akun admin sudah terdaftar
$stmt = mysqli_prepare($koneksi, "SELECT id, username, nama FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$existing = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$msg_status = '';
$is_success = true;

if ($existing) {
    $msg_status = "Akun admin sudah terdaftar di sistem dengan Username: <strong>admin</strong>.";
} else {
    // Buat password hash yang aman
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO users (nama, username, password_hash, role) VALUES (?, ?, ?, ?)");
    if ($stmt_insert) {
        mysqli_stmt_bind_param($stmt_insert, "ssss", $nama, $username, $hash, $role);
        if (mysqli_stmt_execute($stmt_insert)) {
            $msg_status = "Akun admin berhasil dibuat!";
        } else {
            $is_success = false;
            $msg_status = "Gagal membuat akun: " . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt_insert);
    } else {
        $is_success = false;
        $msg_status = "Gagal mempersiapkan query: " . mysqli_error($koneksi);
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Admin — Rayyan KidsWear</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-bg">
    <section class="login-card">
        <img class="login-logo" src="assets/logo-rayyan.jpg" alt="Rayyan KidsWear">
        <small class="eyebrow">SETUP AWAL SISTEM</small>
        <h1>Inisialisasi Admin</h1>
        
        <div class="alert <?= $is_success ? 'success' : 'error' ?>">
            <?= $msg_status ?>
        </div>

        <div style="font-size: 12px; line-height: 1.8; color: #555; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div><strong>Username:</strong> <code>admin</code></div>
            <div><strong>Password:</strong> <code>Rayyan123!</code></div>
        </div>

        <a href="login.php" class="btn primary wide" style="text-align: center; text-decoration: none;">
            Lanjut ke Halaman Login
        </a>
    </section>
</body>
</html>
