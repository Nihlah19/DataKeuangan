<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * Halaman Utama & Dashboard (PHP Native MySQLi)
 */

require_once 'config.php';

// Pastikan hanya admin yang login yang bisa mengakses
login_required();

// Daftar menu dan halaman yang tersedia
$pages = [
    'dashboard'  => 'Dashboard',
    'stok'       => 'Data Stok Barang',
    'terpakai'   => 'Barang Terpakai',
    'keuangan'   => 'Manajemen Keuangan',
    'pengaturan' => 'Pengaturan'
];

$page = $_GET['page'] ?? 'dashboard';
if (!array_key_exists($page, $pages)) {
    $page = 'dashboard';
}

// Ambil dan hapus pesan notifikasi flash
$flash = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

// 1. Data seluruh produk
$q_products = mysqli_query($koneksi, "SELECT * FROM products ORDER BY nama ASC");
$products = [];
while ($row = mysqli_fetch_assoc($q_products)) {
    $products[] = $row;
}

// 2. Data riwayat barang terpakai (20 catatan terakhir)
$q_logs = mysqli_query($koneksi, "SELECT s.*, p.nama AS nama_barang 
                                  FROM stock_out s 
                                  JOIN products p ON p.id = s.product_id 
                                  ORDER BY s.tanggal DESC, s.id DESC 
                                  LIMIT 20");
$logs = [];
while ($row = mysqli_fetch_assoc($q_logs)) {
    $logs[] = $row;
}

// 3. Data riwayat keuangan (100 catatan terakhir untuk tampilan tabel)
$q_fin = mysqli_query($koneksi, "SELECT * FROM finances ORDER BY tanggal DESC, id DESC LIMIT 100");
$fin = [];
while ($row = mysqli_fetch_assoc($q_fin)) {
    $fin[] = $row;
}

// 4. Statistik Total Uang Masuk
$q_income = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal), 0) AS total FROM finances WHERE jenis = 'Masuk'");
$row_income = mysqli_fetch_assoc($q_income);
$income = (float)($row_income['total'] ?? 0);

// 5. Statistik Total Uang Keluar
$q_expense = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal), 0) AS total FROM finances WHERE jenis = 'Keluar'");
$row_expense = mysqli_fetch_assoc($q_expense);
$expense = (float)($row_expense['total'] ?? 0);

// 6. Statistik Total Unit Stok Tersedia
$q_units = mysqli_query($koneksi, "SELECT COALESCE(SUM(stok), 0) AS total FROM products");
$row_units = mysqli_fetch_assoc($q_units);
$units = (int)($row_units['total'] ?? 0);

// 7. Daftar stok barang yang mencapai / di bawah batas minimum
$q_low = mysqli_query($koneksi, "SELECT nama, stok, batas_minimum FROM products WHERE stok <= batas_minimum ORDER BY stok ASC LIMIT 8");
$low = [];
while ($row = mysqli_fetch_assoc($q_low)) {
    $low[] = $row;
}

// Helper navigasi link
function nav($p) {
    return 'index.php?page=' . urlencode($p);
}

// Helper format tanggal Indonesia
function dateid($d) {
    return date('d M Y', strtotime($d));
}

// Helper render tabel keuangan
function tablefin($rows, $actions = false) {
    ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>TANGGAL</th>
                    <th>JENIS</th>
                    <th>KETERANGAN</th>
                    <th>KATEGORI</th>
                    <th class="right">NOMINAL</th>
                    <?php if ($actions): ?>
                        <th>AKSI</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= $actions ? 6 : 5 ?>" class="empty">Belum ada transaksi keuangan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= dateid($r['tanggal']) ?></td>
                            <td>
                                <span class="tag <?= $r['jenis'] === 'Masuk' ? 'in' : 'out' ?>">
                                    <?= e($r['jenis']) ?>
                                </span>
                            </td>
                            <td><?= e($r['keterangan']) ?></td>
                            <td><?= e($r['kategori']) ?></td>
                            <td class="right amount"><?= rp($r['nominal']) ?></td>
                            <?php if ($actions): ?>
                                <td>
                                    <form method="post" action="action.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan keuangan ini?');" style="display:inline;">
                                        <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                                        <input type="hidden" name="action" value="delete_finance">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn danger small">Hapus</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pages[$page]) ?> — Rayyan KidsWear</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="layout">
        <!-- Sidebar Menu Navigasi -->
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="index.php">
                <img src="assets/logo-rayyan.jpg" alt="Rayyan">
                <span>RAYYAN<small>KIDSWEAR · ADMIN</small></span>
            </a>

            <div class="nav-label">MENU TOKO</div>

            <nav>
                <?php
                $icons = [
                    'dashboard'  => '⌂',
                    'stok'       => '▦',
                    'terpakai'   => '⇢',
                    'keuangan'   => 'Rp',
                    'pengaturan' => '⚙'
                ];
                foreach ($pages as $key => $label):
                ?>
                    <a class="nav-item <?= $page === $key ? 'active' : '' ?>" href="<?= e(nav($key)) ?>">
                        <span class="nav-icon"><?= $icons[$key] ?? '•' ?></span>
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="side-note">
                <b>Kelola toko lebih mudah</b>
                <p>Pantau stok, barang terpakai, dan arus kas dalam satu tempat.</p>
            </div>

            <a class="nav-item logout" href="logout.php">
                <span class="nav-icon">↪</span>
                Keluar
            </a>
        </aside>

        <!-- Area Konten Utama -->
        <div class="main-area">
            <!-- Topbar Header -->
            <header class="topbar">
                <button type="button" class="menu-toggle" id="toggle" aria-label="Buka Menu">☰</button>
                <div>
                    <small class="eyebrow">RAYYAN KIDSWEAR</small>
                    <div class="crumb">Beranda / <?= e($pages[$page]) ?></div>
                </div>
                <div class="userbox">
                    <span class="avatar"><?= e(strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1))) ?></span>
                    <div>
                        <b><?= e($_SESSION['nama'] ?? 'Admin') ?></b>
                        <small>Administrator</small>
                    </div>
                </div>
            </header>

            <!-- Isi Halaman Dinamis -->
            <main class="content">
                <?php if ($flash): ?>
                    <div class="alert <?= e($flash[0]) ?>">
                        <?= e($flash[1]) ?>
                    </div>
                <?php endif; ?>

                <div class="page-title">
                    <div>
                        <h1><?= e($pages[$page]) ?></h1>
                        <p>Kelola stok dan keuangan Rayyan KidsWear dengan lebih teratur.</p>
                    </div>
                    <?php if ($page === 'stok' || $page === 'terpakai' || $page === 'keuangan'): ?>
                        <a class="btn primary" href="#form">＋ Tambah Data</a>
                    <?php endif; ?>
                </div>

                <!-- 1. HALAMAN DASHBOARD -->
                <?php if ($page === 'dashboard'): ?>
                    <div class="stats">
                        <article class="stat">
                            <div class="stat-label">Jenis Barang</div>
                            <div class="stat-value"><?= count($products) ?></div>
                            <div class="stat-caption">produk terdata</div>
                        </article>
                        <article class="stat">
                            <div class="stat-label">Total Stok</div>
                            <div class="stat-value"><?= $units ?></div>
                            <div class="stat-caption">unit tersedia</div>
                        </article>
                        <article class="stat">
                            <div class="stat-label">Uang Masuk</div>
                            <div class="stat-value"><?= rp($income) ?></div>
                            <div class="stat-caption">semua catatan masuk</div>
                        </article>
                        <article class="stat">
                            <div class="stat-label">Uang Keluar</div>
                            <div class="stat-value"><?= rp($expense) ?></div>
                            <div class="stat-caption">semua catatan keluar</div>
                        </article>
                    </div>

                    <div class="dashboard-grid">
                        <section class="panel">
                            <div class="panel-head">
                                <div>
                                    <h2>Ringkasan Keuangan</h2>
                                    <p>Total uang masuk dan keluar</p>
                                </div>
                            </div>
                            <div class="chart-holder">
                                <canvas id="chart"></canvas>
                            </div>
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <div>
                                    <h2>Perhatian Stok</h2>
                                    <p>Barang yang perlu diperiksa</p>
                                </div>
                                <span class="pill"><?= count($low) ?> barang</span>
                            </div>
                            <?php if (empty($low)): ?>
                                <p class="muted">Belum ada stok yang mencapai batas minimum.</p>
                            <?php else: ?>
                                <?php foreach ($low as $p): ?>
                                    <div class="low-item">
                                        <b>
                                            <?= e($p['nama']) ?>
                                            <small>Batas minimum: <?= (int)$p['batas_minimum'] ?></small>
                                        </b>
                                        <strong><?= (int)$p['stok'] ?> unit</strong>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <a class="inline-link" href="?page=stok">Lihat data stok →</a>
                        </section>
                    </div>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Transaksi Keuangan Terbaru</h2>
                                <p>Catatan yang terakhir ditambahkan</p>
                            </div>
                            <a class="inline-link" href="?page=keuangan">Lihat semua →</a>
                        </div>
                        <?php tablefin(array_slice($fin, 0, 8), false); ?>
                    </section>

                    <script>
                        window.moneyData = {
                            in: <?= json_encode($income) ?>,
                            out: <?= json_encode($expense) ?>
                        };
                    </script>

                <!-- 2. HALAMAN DATA STOK BARANG -->
                <?php elseif ($page === 'stok'): ?>
                    <section class="panel" id="form">
                        <div class="panel-head">
                            <div>
                                <h2>Tambah / Tambah Stok Barang</h2>
                                <p>Jika kode barang yang sama dimasukkan, jumlah stok akan ditambahkan secara otomatis.</p>
                            </div>
                        </div>

                        <form class="form-grid" action="action.php" method="post">
                            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                            <input type="hidden" name="action" value="product">

                            <label>
                                Nama barang
                                <input type="text" name="nama" required placeholder="Misal: Gamis Anak Polos">
                            </label>

                            <label>
                                Kode barang (opsional)
                                <input type="text" name="kode" placeholder="Misal: GM-001">
                            </label>

                            <label>
                                Ukuran / varian
                                <input type="text" name="varian" placeholder="S, M, L, Navy, Maroon">
                            </label>

                            <label>
                                Jumlah stok awal/tambahan
                                <input type="number" name="jumlah" min="0" value="0" required>
                            </label>

                            <label>
                                Harga jual per unit
                                <input type="text" inputmode="numeric" class="rupiah-input" name="harga" value="0" placeholder="Misal: 50.000" required>
                            </label>

                            <label>
                                Batas minimum stok
                                <input type="number" name="minimum" min="0" value="3" required>
                            </label>

                            <div class="form-actions">
                                <button type="submit" class="btn primary">Simpan Barang</button>
                            </div>
                        </form>
                    </section>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Data Stok Barang</h2>
                                <p><?= count($products) ?> produk tersimpan di MySQL</p>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>KODE</th>
                                        <th>NAMA BARANG</th>
                                        <th>VARIAN</th>
                                        <th>STOK</th>
                                        <th>HARGA JUAL</th>
                                        <th>STATUS</th>
                                        <th>AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="7" class="empty">Belum ada data barang.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $p): ?>
                                            <tr>
                                                <td><?= e($p['kode'] ?: '—') ?></td>
                                                <td><b><?= e($p['nama']) ?></b></td>
                                                <td><?= e($p['varian'] ?: '—') ?></td>
                                                <td><?= (int)$p['stok'] ?></td>
                                                <td><?= rp($p['harga_jual']) ?></td>
                                                <td>
                                                    <span class="tag <?= $p['stok'] <= $p['batas_minimum'] ? 'warn' : 'in' ?>">
                                                        <?= $p['stok'] <= $p['batas_minimum'] ? 'Stok rendah' : 'Tersedia' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="post" action="action.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini beserta seluruh riwayat pemakaiannya?');" style="display:inline;">
                                                        <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                                        <button type="submit" class="btn danger small">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <!-- 3. HALAMAN BARANG TERPAKAI -->
                <?php elseif ($page === 'terpakai'): ?>
                    <section class="panel" id="form">
                        <div class="panel-head">
                            <div>
                                <h2>Catat Barang Terpakai</h2>
                                <p>Stok produk akan otomatis berkurang setelah data disimpan ke sistem.</p>
                            </div>
                        </div>

                        <form class="form-grid" method="post" action="action.php">
                            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                            <input type="hidden" name="action" value="stock_out">

                            <label>
                                Barang
                                <select name="product_id" required>
                                    <option value="">-- Pilih barang --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>">
                                            <?= e($p['nama']) ?><?= $p['varian'] ? ' (' . e($p['varian']) . ')' : '' ?> — Stok tersedia: <?= (int)$p['stok'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                Tanggal
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                            </label>

                            <label>
                                Jumlah terpakai
                                <input type="number" name="jumlah" min="1" required placeholder="Jumlah unit">
                            </label>

                            <label>
                                Keperluan / keterangan
                                <input type="text" name="keterangan" required placeholder="Contoh: Barang cacat, sampel foto, pemakaian operasional">
                            </label>

                            <div class="form-actions">
                                <button type="submit" class="btn primary">Simpan Barang Terpakai</button>
                            </div>
                        </form>
                    </section>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Riwayat Barang Terpakai</h2>
                                <p>20 catatan pemakaian terbaru</p>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>TANGGAL</th>
                                        <th>BARANG</th>
                                        <th>JUMLAH</th>
                                        <th>KETERANGAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="4" class="empty">Belum ada catatan barang terpakai.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $r): ?>
                                            <tr>
                                                <td><?= dateid($r['tanggal']) ?></td>
                                                <td><b><?= e($r['nama_barang'] ?? $r['nama'] ?? '') ?></b></td>
                                                <td><?= (int)$r['jumlah'] ?> unit</td>
                                                <td><?= e($r['keterangan']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                <!-- 4. HALAMAN MANAJEMEN KEUANGAN -->
                <?php elseif ($page === 'keuangan'): ?>
                    <div class="stats stats-three">
                        <article class="stat">
                            <div class="stat-label">Total Uang Masuk</div>
                            <div class="stat-value"><?= rp($income) ?></div>
                        </article>
                        <article class="stat">
                            <div class="stat-label">Total Uang Keluar</div>
                            <div class="stat-value"><?= rp($expense) ?></div>
                        </article>
                        <article class="stat">
                            <div class="stat-label">Selisih Kas</div>
                            <div class="stat-value"><?= rp($income - $expense) ?></div>
                        </article>
                    </div>

                    <section class="panel" id="form">
                        <div class="panel-head">
                            <div>
                                <h2>Catat Keuangan</h2>
                                <p>Catatan disimpan di MySQL dan dapat diunduh sebagai file CSV (Excel).</p>
                            </div>
                        </div>

                        <form class="form-grid" method="post" action="action.php">
                            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                            <input type="hidden" name="action" value="finance">

                            <label>
                                Jenis transaksi
                                <select name="jenis" required>
                                    <option value="Masuk">Masuk</option>
                                    <option value="Keluar">Keluar</option>
                                </select>
                            </label>

                            <label>
                                Tanggal
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                            </label>

                            <label>
                                Keterangan
                                <input type="text" name="keterangan" required placeholder="Misal: Penjualan tunai, beli bahan, dll.">
                            </label>

                            <label>
                                Kategori
                                <input type="text" name="kategori" required placeholder="Misal: Penjualan, Operasional, Pengadaan Stok">
                            </label>

                            <label>
                                Nominal rupiah
                                <input type="text" inputmode="numeric" class="rupiah-input" name="nominal" placeholder="Misal: 50.000" required>
                            </label>

                            <div class="form-actions">
                                <button type="submit" class="btn primary">Simpan Catatan</button>
                            </div>
                        </form>
                    </section>

                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Data Keuangan</h2>
                                <p>100 catatan terbaru di layar; tombol CSV mengunduh seluruh data riwayat.</p>
                            </div>
                            <a class="btn outline" href="export_csv.php">⇩ Download CSV</a>
                        </div>

                        <?php tablefin($fin, true); ?>
                    </section>

                <!-- 5. HALAMAN PENGATURAN AKUN -->
                <?php else: ?>
                    <section class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Pengaturan Akun</h2>
                                <p>Ubah nama pengguna atau kata sandi administrator.</p>
                            </div>
                        </div>

                        <form class="form-grid" method="post" action="action.php">
                            <input type="hidden" name="csrf" value="<?= e(token()) ?>">
                            <input type="hidden" name="action" value="profile">

                            <label>
                                Nama pengguna
                                <input type="text" name="nama" value="<?= e($_SESSION['nama'] ?? '') ?>" required>
                            </label>

                            <label>
                                Password saat ini
                                <input type="password" name="password_lama" required placeholder="Masukkan password saat ini">
                            </label>

                            <label>
                                Password baru (opsional)
                                <input type="password" name="password_baru" minlength="8" placeholder="Kosongkan jika tidak ingin mengganti password">
                            </label>

                            <div class="form-actions">
                                <button type="submit" class="btn primary">Simpan Pengaturan</button>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>

                <footer class="footer">
                    <div>© <?= date('Y') ?> <b>Sahabat Ikmi</b></div>
                    <span>Kelola stok. Pantau keuangan.</span>
                </footer>
            </main>
        </div>
    </div>

    <!-- Overlay latar belakang untuk tampilan mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- Script fungsionalitas UI -->
    <script src="assets/app.js"></script>
    <script>
        // Format otomatis input mata uang Rupiah
        document.querySelectorAll('.rupiah-input').forEach(function(el) {
            el.addEventListener('input', function() {
                let val = this.value.replace(/[^0-9]/g, '');
                this.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
            });
            if (el.form) {
                el.form.addEventListener('submit', function() {
                    el.value = el.value.replace(/\./g, '');
                });
            }
        });
    </script>
</body>
</html>