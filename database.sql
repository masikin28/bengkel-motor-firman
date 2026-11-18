-- =====================================================================
-- Database : db_bengkel_firman
-- Sistem Informasi Pengelolaan Data Pembayaran Servis Motor
-- Bengkel Motor Firman
-- Import lewat phpMyAdmin (tab Import) atau jalankan di tab SQL.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS db_bengkel_firman
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_bengkel_firman;

DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS sparepart;
DROP TABLE IF EXISTS admin;

-- ---------------------------------------------------------------------
-- Tabel admin (pengguna sistem: Admin / Kasir)
-- ---------------------------------------------------------------------
CREATE TABLE admin (
  id_admin     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_lengkap VARCHAR(100) NOT NULL,
  username     VARCHAR(50)  NOT NULL,
  password     VARCHAR(255) NOT NULL,
  role         ENUM('Admin','Kasir') NOT NULL DEFAULT 'Kasir',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_admin),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel sparepart (suku cadang)
-- ---------------------------------------------------------------------
CREATE TABLE sparepart (
  id_sparepart   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_sparepart VARCHAR(20)  NOT NULL,
  nama_sparepart VARCHAR(120) NOT NULL,
  harga          INT UNSIGNED NOT NULL DEFAULT 0,
  stok           INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id_sparepart),
  UNIQUE KEY uq_kode (kode_sparepart)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel transaksi (pembayaran servis)
-- ---------------------------------------------------------------------
CREATE TABLE transaksi (
  id_transaksi     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_nota          VARCHAR(25)  NOT NULL,
  tanggal          DATETIME     NOT NULL,
  nama_pelanggan   VARCHAR(100) NOT NULL,
  no_polisi        VARCHAR(15)  NOT NULL,
  jenis_servis     VARCHAR(255) NOT NULL,
  biaya_jasa       INT UNSIGNED NOT NULL DEFAULT 0,
  id_sparepart     INT UNSIGNED NULL,
  jumlah_sparepart INT UNSIGNED NOT NULL DEFAULT 0,
  harga_sparepart  INT UNSIGNED NOT NULL DEFAULT 0,
  total_bayar      INT UNSIGNED NOT NULL DEFAULT 0,
  id_admin         INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_transaksi),
  UNIQUE KEY uq_nota (no_nota),
  KEY idx_tanggal (tanggal),
  CONSTRAINT fk_trx_sparepart FOREIGN KEY (id_sparepart)
    REFERENCES sparepart (id_sparepart) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_trx_admin FOREIGN KEY (id_admin)
    REFERENCES admin (id_admin) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Data awal
-- Login: admin / admin123  (Admin)   |   kasir / kasir123  (Kasir)
-- Password disimpan sebagai hash bcrypt, bukan teks biasa.
-- ---------------------------------------------------------------------
INSERT INTO admin (nama_lengkap, username, password, role) VALUES
('Firman (Pemilik)', 'admin', '$2b$12$YLVY/rOlkMRvuplcfOTsZupDtKcD2U5zsS7c/1tOGHVaWMU9U4sF.', 'Admin'),
('Kasir Bengkel',    'kasir', '$2b$12$HHwILsfcRUid6qOmZ.PwWuj4l25kT6t4e4BxDCe16jy7xF5rzEsDC', 'Kasir');

INSERT INTO sparepart (kode_sparepart, nama_sparepart, harga, stok) VALUES
('SP-001', 'Oli Mesin Matic 0.8L',            48000, 40),
('SP-002', 'Oli Mesin Bebek/Sport 1L',        55000, 35),
('SP-003', 'Oli Gardan Matic',                17000, 30),
('SP-004', 'Kampas Rem Depan',                45000, 25),
('SP-005', 'Kampas Rem Belakang',             40000, 25),
('SP-006', 'Busi Standar',                    22000, 50),
('SP-007', 'Filter Udara',                    35000, 20),
('SP-008', 'Rantai Roda + Gir Set',          185000, 12),
('SP-009', 'V-Belt Matic',                   130000, 15),
('SP-010', 'Roller CVT Set',                  75000, 15),
('SP-011', 'Ban Dalam 80/90-14',              40000, 18),
('SP-012', 'Ban Luar Tubeless 80/90-14',     210000, 10),
('SP-013', 'Aki Kering 12V 5Ah',             165000,  8),
('SP-014', 'Lampu Depan LED',                 85000, 14),
('SP-015', 'Kabel Kopling',                   28000, 20);

INSERT INTO transaksi
(no_nota, tanggal, nama_pelanggan, no_polisi, jenis_servis, biaya_jasa, id_sparepart, jumlah_sparepart, harga_sparepart, total_bayar, id_admin)
VALUES
('FRM-20260101-001', '2026-01-01 09:15:00', 'Budi Santoso', 'B 4521 KJT', 'Ganti oli dan servis rutin', 25000, 1, 1, 48000, 73000, 2);
UPDATE sparepart SET stok = stok - 1 WHERE id_sparepart = 1;
