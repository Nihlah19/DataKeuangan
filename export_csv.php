<?php
/**
 * RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
 * Export Data Keuangan ke CSV (PHP Native)
 */

require_once 'config.php';

// Pastikan hanya admin yang sudah login yang bisa mengekspor data
login_required();

// Ambil seluruh data riwayat keuangan dari MySQL
$query = mysqli_query($koneksi, "SELECT tanggal, jenis, keterangan, kategori, nominal, created_at FROM finances ORDER BY tanggal DESC, id DESC");

if (!$query) {
    die("Gagal mengambil data keuangan: " . mysqli_error($koneksi));
}

// Set header response untuk file CSV
$filename = 'rayyan-kidswear-keuangan-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Tulis langsung ke output stream
$output = fopen('php://output', 'w');

// Tulis UTF-8 BOM agar terbaca rapi oleh Microsoft Excel
fwrite($output, "\xEF\xBB\xBF");

// Tulis baris header kolom CSV (gunakan pemisah titik-koma ';' sesuai format regional Excel di Indonesia)
fputcsv($output, ['Tanggal', 'Jenis', 'Keterangan', 'Kategori', 'Nominal Rupiah', 'Waktu Input'], ';');

// Tulis setiap baris data
while ($row = mysqli_fetch_assoc($query)) {
    fputcsv($output, [
        $row['tanggal'],
        $row['jenis'],
        $row['keterangan'],
        $row['kategori'],
        $row['nominal'],
        $row['created_at']
    ], ';');
}

fclose($output);
exit;