RAYYAN KIDSWEAR — SISTEM STOK DAN KEUANGAN PHP + MYSQL

FITUR:
1. Login admin dengan akun di MySQL; password di-hash.
2. Data stok: tambah barang, tambah stok bila kode sama, hapus barang, varian, harga, batas minimum.
3. Barang terpakai harian: tanggal, jumlah, keterangan; stok otomatis berkurang dan tidak bisa minus.
4. Uang masuk/keluar: simpan ke MySQL, ringkasan dashboard, hapus catatan, unduh semua catatan sebagai CSV.
5. Pengaturan nama dan password akun.
6. Logo Rayyan dari gambar yang dikirim.
7. Layout responsif untuk laptop dan HP.

CARA INSTAL:
1. Ekstrak folder rayyan_kidswear ke C:\xampp\htdocs\
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka http://localhost/phpmyadmin
4. Klik Import, pilih file database.sql dalam folder ini, lalu klik Import/Go.
5. Pastikan config.php cocok dengan setelan MySQL XAMPP. Default: root tanpa password.
6. Buka http://localhost/rayyan_kidswear/setup_admin.php SEKALI.
7. Login di http://localhost/rayyan_kidswear/login.php
   Username: admin
   Password: Rayyan123!
8. Setelah akun berhasil dibuat, HAPUS setup_admin.php demi keamanan.
9. Tambahkan barang, catat barang terpakai, catat keuangan, dan unduh CSV.

CATATAN:
- Data dari formulir disimpan ke MySQL.
- CSV memuat semua catatan keuangan; tabel hanya menampilkan 100 catatan terakhir.
- Grafik memakai Chart.js CDN sehingga perlu internet untuk memuat grafik.
- Menghapus produk juga menghapus riwayat barang terpakai yang terkait.
- Pencatatan barang terpakai belum otomatis membuat transaksi uang keluar; catat biaya tersebut di Manajemen Keuangan bila relevan.
- Sebelum website dipasang online, hapus setup_admin.php, ganti kredensial database, aktifkan HTTPS, dan lakukan backup rutin.
