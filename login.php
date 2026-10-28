<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * Halaman Login Admin (PHP Native)
 */

require_once 'config.php';

// Jika sudah login, langsung alihkan ke dashboard utama
if (!empty($_SESSION['uid'])) {
    go('index.php');
}

$err = '';

// Proses formulir saat metode POST diterima
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_token();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Ambil data user dari MySQL secara aman dengan prepared statement
    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, username, password_hash FROM users WHERE username = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Verifikasi kecocokan password dengan hash yang tersimpan
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            go('index.php');
        }
    }

    $err = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Rayyan KidsWear</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-bg">
    <section class="login-card">
        <img class="login-logo" src="assets/logo-rayyan.jpg" alt="Rayyan Moeslem Collection">
        <small class="eyebrow">SISTEM MANAJEMEN TOKO</small>
        <h1>Selamat datang</h1>
        <p>Masuk untuk mengelola stok dan keuangan Rayyan KidsWear.</p>

        <?php if (!empty($err)): ?>
            <div class="alert error"><?= e($err) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="form">
            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
            
            <label>
                Username
                <input type="text" name="username" required autocomplete="username" autofocus>
            </label>

            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <button type="submit" class="btn primary wide">Masuk ke Dashboard</button>
        </form>
    </section>
</body>
</html>