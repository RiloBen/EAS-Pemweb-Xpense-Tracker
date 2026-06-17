-- =====================================================
-- Xpense Tracker — Database Schema
-- Import file ini melalui phpMyAdmin: Import > pilih file ini
-- =====================================================

CREATE DATABASE IF NOT EXISTS xpense_tracker
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE xpense_tracker;

-- -----------------------------------------------------
-- Tabel: users
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabel: transaksi
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi (
    id          INT                              AUTO_INCREMENT PRIMARY KEY,
    user_id     INT                              NOT NULL,
    jenis       ENUM('pendapatan','pengeluaran') NOT NULL,
    jumlah      DECIMAL(12,2)                    NOT NULL,
    tanggal     DATE                             NOT NULL,
    keterangan  VARCHAR(255)                     NOT NULL,
    foto_nota   VARCHAR(255)                     NULL,
    created_at  TIMESTAMP                        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
