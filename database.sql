-- ==============================================================
-- RAYYAN KIDSWEAR - SISTEM STOK DAN KEUANGAN
-- Database Schema & Initial Data
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `rayyan_kidswear` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `rayyan_kidswear`;

-- -------------------------------------------------------------
-- 1. Tabel users (Admin / Pengguna)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Tabel products (Data Stok Produk / Barang)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kode` VARCHAR(50) UNIQUE NULL,
    `nama` VARCHAR(120) NOT NULL,
    `varian` VARCHAR(100) NULL,
    `stok` INT UNSIGNED NOT NULL DEFAULT 0,
    `harga_jual` DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
    `batas_minimum` INT UNSIGNED NOT NULL DEFAULT 3,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Tabel stock_out (Pencatatan Barang Terpakai)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_out` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `tanggal` DATE NOT NULL,
    `jumlah` INT UNSIGNED NOT NULL,
    `keterangan` VARCHAR(255) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Tabel finances (Pencatatan Keuangan Masuk / Keluar)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finances` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tanggal` DATE NOT NULL,
    `jenis` ENUM('Masuk', 'Keluar') NOT NULL,
    `keterangan` VARCHAR(255) NOT NULL,
    `kategori` VARCHAR(100) NOT NULL,
    `nominal` DECIMAL(14,2) UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX (`tanggal`),
    INDEX (`jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Data Awal: Akun Administrator Default
-- Username: admin | Password: Rayyan123!
-- -------------------------------------------------------------
INSERT IGNORE INTO `users` (`id`, `nama`, `username`, `password_hash`, `role`) 
VALUES (1, 'Admin Rayyan', 'admin', '$2y$10$IocKiPCz4Da64hC/xdD6Q.FMeR4GmBSWjigWj..xTEafm7yf15X0e', 'admin');
