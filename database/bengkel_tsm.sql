-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 03:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bengkel_tsm`
--

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `id` int(11) NOT NULL,
  `servis_id` int(11) NOT NULL,
  `biaya_jasa` int(11) DEFAULT 0,
  `biaya_part` int(11) DEFAULT 0,
  `ppn_persen` int(11) DEFAULT 0,
  `ppn_nominal` decimal(10,2) DEFAULT 0.00,
  `status` enum('belum_lunas','lunas') DEFAULT 'belum_lunas',
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`id`, `servis_id`, `biaya_jasa`, `biaya_part`, `ppn_persen`, `ppn_nominal`, `status`, `dibuat`) VALUES
(6, 8, 85000, 45000, 0, 0.00, 'lunas', '2026-04-30 17:43:36'),
(7, 14, 120000, 53000, 0, 0.00, 'lunas', '2026-05-01 13:28:06'),
(8, 13, 39999, 28000, 5, 3400.00, 'lunas', '2026-05-01 14:28:23'),
(9, 17, 120000, 0, 4, 4800.00, 'lunas', '2026-05-06 11:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `jasa_servis`
--

CREATE TABLE `jasa_servis` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama_jasa` varchar(100) NOT NULL,
  `kategori` enum('ringan','sedang','berat') NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jasa_servis`
--

INSERT INTO `jasa_servis` (`id`, `kode`, `nama_jasa`, `kategori`, `harga`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'JS-R01', 'Servis Ringan - Ganti Oli + Cek Umum', 'ringan', 55000.00, 'Ganti oli mesin, cek rem, ban, lampu, dan sistem kelistrikan', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(2, 'JS-R02', 'Servis Ringan - Tune Up Mesin', 'ringan', 75000.00, 'Setel klep, bersihkan karburator/injeksi, cek pengapian', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(3, 'JS-R03', 'Servis Ringan - CVT Matic', 'ringan', 65000.00, 'Bersihkan CVT, cek roller & belt', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(4, 'JS-S01', 'Servis Sedang - Ganti Kampas Rem', 'sedang', 85000.00, 'Ganti kampas rem depan + belakang, bleeding', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(5, 'JS-S02', 'Servis Sedang - Turun Mesin Ringan', 'sedang', 150000.00, 'Bongkar pasang head, setel klep, gasket', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(6, 'JS-S03', 'Servis Sedang - Overhaul Karburator', 'sedang', 120000.00, 'Bongkar bersih karburator, ganti seal & pelampung', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(7, 'JS-B01', 'Servis Berat - Overhaul Mesin', 'berat', 300000.00, 'Bongkar total mesin, ganti piston, ring, klep', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(8, 'JS-B02', 'Servis Berat - Turun Mesin Full', 'berat', 350000.00, 'Overhaul + ganti rantai keteng, tensioner', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(9, 'JS-B03', 'Servis Berat - Ganti CVT Set', 'berat', 200000.00, 'Ganti roller, belt, kampas ganda, per CVT', '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(10, 'JS-R04', 'Servis Ringan-Ganti ban luar', 'ringan', 60000.00, 'mengganti ban luar', '2026-04-28 23:00:25', '2026-04-28 23:00:25');

-- --------------------------------------------------------

--
-- Table structure for table `mekanik`
--

CREATE TABLE `mekanik` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `keahlian` varchar(100) DEFAULT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mekanik`
--

INSERT INTO `mekanik` (`id`, `nama`, `kelas`, `keahlian`, `dibuat`) VALUES
(1, 'Andi', 'XII TSM / 43215', 'Sistem Rem', '2026-04-28 14:22:55');

-- --------------------------------------------------------

--
-- Table structure for table `motor`
--

CREATE TABLE `motor` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `plat` varchar(15) NOT NULL,
  `merk` varchar(50) DEFAULT NULL,
  `tipe` varchar(100) DEFAULT NULL,
  `tahun` int(11) DEFAULT NULL,
  `no_rangka` varchar(50) DEFAULT NULL,
  `no_mesin` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motor`
--

INSERT INTO `motor` (`id`, `pelanggan_id`, `plat`, `merk`, `tipe`, `tahun`, `no_rangka`, `no_mesin`, `created_at`) VALUES
(4, 7, 'BN 2122 XO', 'Honda', 'Vario 115', 2008, NULL, NULL, '2026-04-30 17:14:41'),
(5, 7, 'BN 5180 XY', 'Suzuki', 'Smash 110', 2008, NULL, NULL, '2026-04-30 18:38:03'),
(6, 8, 'BN 3276 KL', 'Yamaha', 'Jupiter MX', 2015, NULL, NULL, '2026-05-04 03:36:56');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tipe` enum('siswa','umum') DEFAULT 'umum',
  `kelas` varchar(20) DEFAULT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `nama`, `telepon`, `alamat`, `tipe`, `kelas`, `dibuat`) VALUES
(7, 'Hadiqa', '0876549321', 'Kelubi', 'siswa', 'XII RPL', '2026-04-30 17:14:41'),
(8, 'Dio Pratama', '0879654321', 'Bentaian Jaya', 'siswa', 'XII RPL', '2026-05-04 03:36:56');

-- --------------------------------------------------------

--
-- Table structure for table `servis`
--

CREATE TABLE `servis` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `motor_id` int(11) DEFAULT NULL,
  `mekanik_id` int(11) NOT NULL,
  `km_sekarang` int(11) DEFAULT NULL,
  `km_servis_selanjutnya` int(11) DEFAULT NULL,
  `tipe_pelanggan` enum('siswa','umum') DEFAULT 'umum',
  `keluhan` text DEFAULT NULL,
  `biaya_jasa` decimal(10,2) DEFAULT 0.00,
  `layanan` varchar(100) DEFAULT NULL,
  `jasa_id` int(11) DEFAULT NULL,
  `kategori_servis` enum('ringan','sedang','berat','custom') DEFAULT NULL,
  `deskripsi_jasa` text DEFAULT NULL,
  `harga_jasa_custom` decimal(10,2) DEFAULT 0.00,
  `status` enum('menunggu','proses','selesai') DEFAULT 'menunggu',
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servis`
--

INSERT INTO `servis` (`id`, `pelanggan_id`, `motor_id`, `mekanik_id`, `km_sekarang`, `km_servis_selanjutnya`, `tipe_pelanggan`, `keluhan`, `biaya_jasa`, `layanan`, `jasa_id`, `kategori_servis`, `deskripsi_jasa`, `harga_jasa_custom`, `status`, `dibuat`) VALUES
(8, 7, 4, 1, 20000, 23000, 'siswa', 'rem blong', 85000.00, '', 4, 'sedang', 'ganti rem depan', 0.00, 'selesai', '2026-04-30 17:14:41'),
(9, 7, 4, 1, 23000, 26000, 'siswa', 'rem belakang blong', 85000.00, '', 4, 'sedang', '', 0.00, 'selesai', '2026-04-30 17:58:00'),
(10, 7, 4, 1, 22999, 25999, 'siswa', 'rem blong', 85000.00, '', 4, 'sedang', '', 0.00, 'selesai', '2026-04-30 18:17:32'),
(11, 7, 4, 1, 22999, 25999, 'siswa', 'rem blong', 85000.00, '', 4, 'sedang', '', 0.00, 'selesai', '2026-04-30 18:18:31'),
(12, 7, 4, 1, 30000, 33000, 'siswa', 'rem blong', 85000.00, '', 4, 'sedang', '', 0.00, 'selesai', '2026-04-30 18:21:18'),
(13, 7, 5, 1, 15000, 18000, 'siswa', 'tidak hidup', 39999.00, '', NULL, 'ringan', 'ganti busi', 39999.00, 'selesai', '2026-04-30 18:38:03'),
(14, 7, 5, 1, 14000, 17000, 'siswa', 'mesin kasar', 120000.00, '', 6, 'sedang', '', 0.00, 'selesai', '2026-05-01 13:26:58'),
(15, 7, 5, 1, 14999, 17999, 'siswa', 'susah starter', 0.00, '', NULL, 'ringan', 'ganti aki', 0.00, 'selesai', '2026-05-04 03:06:03'),
(16, 8, 6, 1, 15000, 18000, 'siswa', 'rem blong', 85000.00, 'ganti kampas rem', 4, 'sedang', '', 0.00, 'selesai', '2026-05-04 03:36:56'),
(17, 8, 6, 1, 14999, 17999, 'siswa', 'mesin kasar', 120000.00, 'membersihkan karburator', 6, 'sedang', '', 0.00, 'selesai', '2026-05-06 11:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `servis_part`
--

CREATE TABLE `servis_part` (
  `id` int(11) NOT NULL,
  `servis_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servis_part`
--

INSERT INTO `servis_part` (`id`, `servis_id`, `part_id`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(3, 8, 6, 1, 45000.00, 45000.00),
(5, 9, 6, 1, 45000.00, 45000.00),
(6, 10, 7, 1, 42000.00, 42000.00),
(7, 10, 6, 1, 45000.00, 45000.00),
(10, 11, 6, 1, 45000.00, 45000.00),
(11, 11, 7, 1, 42000.00, 42000.00),
(15, 12, 7, 1, 42000.00, 42000.00),
(16, 12, 6, 1, 45000.00, 45000.00),
(17, 13, 5, 1, 28000.00, 28000.00),
(19, 16, 7, 1, 42000.00, 42000.00),
(20, 16, 6, 1, 45000.00, 45000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sparepart_custom`
--

CREATE TABLE `sparepart_custom` (
  `id` int(11) NOT NULL,
  `servis_id` int(11) NOT NULL,
  `nama_part` varchar(150) NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `harga_beli` decimal(10,2) DEFAULT 0.00,
  `harga_jual` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sparepart_custom`
--

INSERT INTO `sparepart_custom` (`id`, `servis_id`, `nama_part`, `jumlah`, `harga_beli`, `harga_jual`, `subtotal`, `created_at`) VALUES
(5, 14, 'baut 10', 3, 10000.00, 12000.00, 36000.00, '2026-05-01 13:27:46'),
(6, 14, 'baut 12', 1, 15000.00, 17000.00, 17000.00, '2026-05-01 13:27:46'),
(7, 15, 'aki kering GTZ5S', 1, 150000.00, 153000.00, 153000.00, '2026-05-04 03:06:03');

-- --------------------------------------------------------

--
-- Table structure for table `suku_cadang`
--

CREATE TABLE `suku_cadang` (
  `id` int(11) NOT NULL,
  `kode_part` varchar(20) NOT NULL,
  `nama_part` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `merk` varchar(50) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `harga_beli` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suku_cadang`
--

INSERT INTO `suku_cadang` (`id`, `kode_part`, `nama_part`, `kategori`, `merk`, `stok`, `harga_beli`, `harga_jual`, `created_at`, `updated_at`) VALUES
(1, 'OLI-MPX2', 'Oli AHM MPX-2 0.8L', 'Oli Mesin', 'AHM', 29, 42000.00, 55000.00, '2026-04-28 14:58:20', '2026-04-28 16:20:44'),
(2, 'OLI-YML', 'Yamalube Matic 0.8L', 'Oli Mesin', 'Yamaha', 25, 55000.00, 70000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(3, 'OLI-ECS', 'Ecstar 10W-40 0.8L', 'Oli Mesin', 'Suzuki', 15, 58000.00, 75000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(4, 'BUS-NGK', 'Busi NGK CR7HSA', 'Kelistrikan', 'NGK', 50, 18000.00, 25000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(5, 'BUS-DEN', 'Busi Denso U24ESR-N', 'Kelistrikan', 'Denso', 39, 20000.00, 28000.00, '2026-04-28 14:58:20', '2026-04-30 18:38:03'),
(6, 'REM-KDP', 'Kampas Rem Depan (Set)', 'Rem', 'Indoparts', 14, 35000.00, 45000.00, '2026-04-28 14:58:20', '2026-05-04 03:38:15'),
(7, 'REM-KBL', 'Kampas Rem Belakang (Set)', 'Rem', 'Indoparts', 16, 32000.00, 42000.00, '2026-04-28 14:58:20', '2026-05-04 03:38:15'),
(8, 'FLT-UDR', 'Filter Udara Vario/Beat', 'Filter', 'AHM', 25, 28000.00, 35000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(9, 'FLT-UDM', 'Filter Udara Mio M3', 'Filter', 'Yamaha', 15, 30000.00, 38000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(10, 'CVT-BLT', 'V-Belt Matic Honda', 'CVT', 'Bando', 10, 85000.00, 110000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(11, 'CVT-RLR', 'Roller Set 6pc', 'CVT', 'Kitaco', 15, 45000.00, 60000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(12, 'RAN-RRT', 'Rantai Keteng', 'Mesin', 'TKRJ', 8, 95000.00, 125000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(13, 'GAS-HD', 'Gasket Head Set', 'Mesin', 'NOK', 12, 55000.00, 75000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20'),
(14, 'BAN-LUAR', 'Ban Luar (60/90-17)', 'Ban', 'IRC', 9, 150000.00, 195000.00, '2026-04-28 14:58:20', '2026-04-28 22:48:17'),
(15, 'BAN-DLM', 'Ban Dalam (60/90-17)', 'Ban', 'IRC', 20, 35000.00, 45000.00, '2026-04-28 14:58:20', '2026-04-28 14:58:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','kasir') NOT NULL DEFAULT 'kasir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(1, 'admin', 'password', 'Administrator TEFA', 'admin'),
(2, 'kasir1', 'password', 'Kasir Satu', 'kasir');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `servis_id` (`servis_id`);

--
-- Indexes for table `jasa_servis`
--
ALTER TABLE `jasa_servis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `mekanik`
--
ALTER TABLE `mekanik`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `motor`
--
ALTER TABLE `motor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plat` (`plat`),
  ADD KEY `pelanggan_id` (`pelanggan_id`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `servis`
--
ALTER TABLE `servis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_id` (`pelanggan_id`),
  ADD KEY `mekanik_id` (`mekanik_id`),
  ADD KEY `jasa_id` (`jasa_id`),
  ADD KEY `motor_id` (`motor_id`);

--
-- Indexes for table `servis_part`
--
ALTER TABLE `servis_part`
  ADD PRIMARY KEY (`id`),
  ADD KEY `servis_id` (`servis_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `sparepart_custom`
--
ALTER TABLE `sparepart_custom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `servis_id` (`servis_id`);

--
-- Indexes for table `suku_cadang`
--
ALTER TABLE `suku_cadang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_part` (`kode_part`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `jasa_servis`
--
ALTER TABLE `jasa_servis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `mekanik`
--
ALTER TABLE `mekanik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `motor`
--
ALTER TABLE `motor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `servis`
--
ALTER TABLE `servis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `servis_part`
--
ALTER TABLE `servis_part`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sparepart_custom`
--
ALTER TABLE `sparepart_custom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suku_cadang`
--
ALTER TABLE `suku_cadang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`servis_id`) REFERENCES `servis` (`id`);

--
-- Constraints for table `motor`
--
ALTER TABLE `motor`
  ADD CONSTRAINT `motor_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `servis`
--
ALTER TABLE `servis`
  ADD CONSTRAINT `servis_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`),
  ADD CONSTRAINT `servis_ibfk_2` FOREIGN KEY (`mekanik_id`) REFERENCES `mekanik` (`id`),
  ADD CONSTRAINT `servis_ibfk_3` FOREIGN KEY (`jasa_id`) REFERENCES `jasa_servis` (`id`),
  ADD CONSTRAINT `servis_ibfk_4` FOREIGN KEY (`motor_id`) REFERENCES `motor` (`id`);

--
-- Constraints for table `servis_part`
--
ALTER TABLE `servis_part`
  ADD CONSTRAINT `servis_part_ibfk_1` FOREIGN KEY (`servis_id`) REFERENCES `servis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servis_part_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `suku_cadang` (`id`);

--
-- Constraints for table `sparepart_custom`
--
ALTER TABLE `sparepart_custom`
  ADD CONSTRAINT `sparepart_custom_ibfk_1` FOREIGN KEY (`servis_id`) REFERENCES `servis` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
