<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * File Pemrosesan Aksi Formulir (PHP Native MySQLi)
 */

require_once 'config.php';

// Wajib login untuk menjalankan aksi apapun
login_required();

// Validasi token keamanan CSRF
check_token();

$action = $_POST['action'] ?? '';

try {
    // -------------------------------------------------------------
    // 1. TAMBAH / UPDATE STOK BARANG
    // -------------------------------------------------------------
    if ($action === 'product') {
        $nama    = trim($_POST['nama'] ?? '');
        $kode    = trim($_POST['kode'] ?? '');
        $varian  = trim($_POST['varian'] ?? '');
        $jumlah  = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
        $harga   = filter_var($_POST['harga'] ?? null, FILTER_VALIDATE_INT);
        $minimum = filter_var($_POST['minimum'] ?? null, FILTER_VALIDATE_INT);

        // Validasi input
        if (!$nama || $jumlah === false || $jumlah === null || $jumlah < 0 ||
            $harga === false || $harga === null || $harga < 0 ||
            $minimum === false || $minimum === null || $minimum < 0) {
            throw new Exception('Data barang, stok, harga, atau batas minimum tidak valid.');
        }

        // Cek apakah kode barang sudah ada di database
        $old_product = false;
        if ($kode !== '') {
            $stmt = mysqli_prepare($koneksi, "SELECT id FROM products WHERE kode = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $kode);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $old_product = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
            }
        }

        if ($old_product) {
            // Update barang yang sudah ada dan akumulasikan stoknya
            $stmt = mysqli_prepare($koneksi, "UPDATE products SET nama = ?, varian = ?, stok = stok + ?, harga_jual = ?, batas_minimum = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Gagal mempersiapkan query update: ' . mysqli_error($koneksi));
            }
            mysqli_stmt_bind_param($stmt, "sssdii", $nama, $varian, $jumlah, $harga, $minimum, $old_product['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            msg('success', 'Barang diperbarui dan stok ditambahkan.');
        } else {
            // Tambahkan barang baru
            $kode_param = ($kode !== '') ? $kode : null;
            $stmt = mysqli_prepare($koneksi, "INSERT INTO products (kode, nama, varian, stok, harga_jual, batas_minimum) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Gagal mempersiapkan query simpan: ' . mysqli_error($koneksi));
            }
            mysqli_stmt_bind_param($stmt, "sssidi", $kode_param, $nama, $varian, $jumlah, $harga, $minimum);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            msg('success', 'Barang berhasil ditambahkan.');
        }

        go('index.php?page=stok');
    }

    // -------------------------------------------------------------
    // 2. HAPUS BARANG
    // -------------------------------------------------------------
    if ($action === 'delete_product') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID barang tidak valid.');
        }

        $stmt = mysqli_prepare($koneksi, "DELETE FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Gagal mempersiapkan query hapus: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        msg('success', 'Barang dan riwayat terkait berhasil dihapus.');
        go('index.php?page=stok');
    }

    // -------------------------------------------------------------
    // 3. CATAT BARANG TERPAKAI
    // -------------------------------------------------------------
    if ($action === 'stock_out') {
        $pid        = (int)($_POST['product_id'] ?? 0);
        $jumlah     = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
        $tanggal    = $_POST['tanggal'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');

        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$pid || !$jumlah || $jumlah < 1 || !$d || $d->format('Y-m-d') !== $tanggal || !$keterangan) {
            throw new Exception('Lengkapi data barang terpakai dengan benar.');
        }

        // Gunakan transaksi untuk menjamin konsistensi stok
        mysqli_begin_transaction($koneksi);

        // Kunci baris produk yang akan dikurangi stoknya
        $stmt = mysqli_prepare($koneksi, "SELECT stok FROM products WHERE id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception('Gagal query stok: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt, "i", $pid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$product) {
            throw new Exception('Barang tidak ditemukan di database.');
        }

        if ($product['stok'] < $jumlah) {
            throw new Exception('Stok tidak mencukupi. Stok yang tersedia saat ini: ' . $product['stok']);
        }

        // Kurangi stok barang
        $stmt_update = mysqli_prepare($koneksi, "UPDATE products SET stok = stok - ? WHERE id = ?");
        if (!$stmt_update) {
            throw new Exception('Gagal update stok: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt_update, "ii", $jumlah, $pid);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);

        // Catat ke tabel riwayat barang terpakai (stock_out)
        $uid = (int)($_SESSION['uid'] ?? 0);
        $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO stock_out (product_id, tanggal, jumlah, keterangan, user_id) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt_insert) {
            throw new Exception('Gagal mencatat pemakaian: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt_insert, "isisi", $pid, $tanggal, $jumlah, $keterangan, $uid);
        mysqli_stmt_execute($stmt_insert);
        mysqli_stmt_close($stmt_insert);

        // Selesai transaksi
        mysqli_commit($koneksi);

        msg('success', 'Barang terpakai berhasil dicatat dan stok telah berkurang.');
        go('index.php?page=terpakai');
    }

    // -------------------------------------------------------------
    // 4. CATAT KEUANGAN (UANG MASUK / KELUAR)
    // -------------------------------------------------------------
    if ($action === 'finance') {
        $jenis      = $_POST['jenis'] ?? '';
        $tanggal    = $_POST['tanggal'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');
        $kategori   = trim($_POST['kategori'] ?? '');
        $nominal    = filter_var($_POST['nominal'] ?? null, FILTER_VALIDATE_INT);

        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!in_array($jenis, ['Masuk', 'Keluar'], true) ||
            !$d || $d->format('Y-m-d') !== $tanggal ||
            !$keterangan || !$kategori ||
            $nominal === false || $nominal === null || $nominal < 1) {
            throw new Exception('Periksa jenis, tanggal, keterangan, kategori, dan nominal transaksi.');
        }

        $uid = (int)($_SESSION['uid'] ?? 0);
        $stmt = mysqli_prepare($koneksi, "INSERT INTO finances (tanggal, jenis, keterangan, kategori, nominal, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Gagal menyimpan transaksi: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt, "ssssdi", $tanggal, $jenis, $keterangan, $kategori, $nominal, $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        msg('success', 'Catatan keuangan tersimpan ke MySQL.');
        go('index.php?page=keuangan');
    }

    // -------------------------------------------------------------
    // 5. HAPUS CATATAN KEUANGAN
    // -------------------------------------------------------------
    if ($action === 'delete_finance') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID transaksi tidak valid.');
        }

        $stmt = mysqli_prepare($koneksi, "DELETE FROM finances WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Gagal menghapus transaksi: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        msg('success', 'Catatan keuangan berhasil dihapus.');
        go('index.php?page=keuangan');
    }

    // -------------------------------------------------------------
    // 6. PENGATURAN PROFIL & PASSWORD
    // -------------------------------------------------------------
    if ($action === 'profile') {
        $nama          = trim($_POST['nama'] ?? '');
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $uid           = (int)($_SESSION['uid'] ?? 0);

        // Ambil password saat ini
        $stmt = mysqli_prepare($koneksi, "SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('Gagal query user: ' . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$nama || !$user || !password_verify($password_lama, $user['password_hash'])) {
            throw new Exception('Nama wajib diisi dan password saat ini harus benar.');
        }

        if ($password_baru !== '' && strlen($password_baru) < 8) {
            throw new Exception('Password baru minimal 8 karakter.');
        }

        if ($password_baru !== '') {
            $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_update = mysqli_prepare($koneksi, "UPDATE users SET nama = ?, password_hash = ? WHERE id = ?");
            if (!$stmt_update) {
                throw new Exception('Gagal memperbarui profil: ' . mysqli_error($koneksi));
            }
            mysqli_stmt_bind_param($stmt_update, "ssi", $nama, $hash_baru, $uid);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        } else {
            $stmt_update = mysqli_prepare($koneksi, "UPDATE users SET nama = ? WHERE id = ?");
            if (!$stmt_update) {
                throw new Exception('Gagal memperbarui profil: ' . mysqli_error($koneksi));
            }
            mysqli_stmt_bind_param($stmt_update, "si", $nama, $uid);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }

        $_SESSION['nama'] = $nama;
        msg('success', 'Pengaturan akun berhasil disimpan.');
        go('index.php?page=pengaturan');
    }

    // Jika parameter action tidak cocok
    throw new Exception('Aksi yang diminta tidak dikenali.');

} catch (Throwable $e) {
    // Batalkan transaksi jika terjadi kesalahan
    @mysqli_rollback($koneksi);

    $error_msg = $e->getMessage();
    if (str_contains($error_msg, 'Duplicate entry') || str_contains($error_msg, 'kode')) {
        $error_msg = 'Operasi database gagal. Periksa apakah kode barang sudah dipakai oleh produk lain.';
    }

    msg('error', $error_msg);

    // Tentukan halaman kembali berdasarkan aksi yang dijalankan
    $halaman_tujuan = 'keuangan';
    if ($action === 'product' || $action === 'delete_product') {
        $halaman_tujuan = 'stok';
    } elseif ($action === 'stock_out') {
        $halaman_tujuan = 'terpakai';
    } elseif ($action === 'profile') {
        $halaman_tujuan = 'pengaturan';
    }

    go('index.php?page=' . $halaman_tujuan);
}