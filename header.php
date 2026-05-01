<?php
require_once 'koneksi.php';
cekLogin();

$halaman = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'];

// Hitung notifikasi untuk badge
$antrian_menunggu = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='menunggu'")->fetchColumn();
$invoice_belum = $pdo->query("SELECT COUNT(*) FROM invoice WHERE status!='lunas'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEFA TSM - Bengkel Teaching Factory</title>
    <link rel="stylesheet" href="/bengkel.tsm/assets/css/style.css">
    <script src="/bengkel.tsm/assets/js/script.js" defer></script>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
    <div class="topbar-inner">
        <div class="topbar-brand">
            <div class="icon">🏍️</div>
            <div class="text">
                <h3>Bengkel TEFA TSM</h3>
                <span>Teknik Sepeda Motor • Teaching Factory</span>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info">
                <div class="nama">👤 <?= htmlspecialchars($user['nama_lengkap']) ?></div>
                <span class="role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-kasir' ?>">
                    <?= $user['role'] === 'admin' ? '🔑 Administrator' : '💳 Kasir' ?>
                </span>
            </div>
            <a href="/bengkel.tsm/logout.php" class="btn-logout">🚪 Keluar</a>
        </div>
    </div>
</header>

<!-- NAVBAR GRID -->
<nav class="navbar-grid">
    <div class="navbar-grid-inner">
        <h2>Menu Utama</h2>
        <div class="nav-grid">
            <a href="/bengkel.tsm/index.php" class="nav-card <?= $halaman==='index.php' ? 'aktif' : '' ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="/bengkel.tsm/antrian.php" class="nav-card <?= $halaman==='antrian.php' ? 'aktif' : '' ?>">
                <?php if($antrian_menunggu > 0): ?>
                <span class="nav-badge"><?= $antrian_menunggu ?></span>
                <?php endif; ?>
                <span class="nav-icon">🔧</span>
                <span class="nav-label">Antrian Servis</span>
            </a>
            <a href="/bengkel.tsm/invoice.php" class="nav-card <?= $halaman==='invoice.php' ? 'aktif' : '' ?>">
                <?php if($invoice_belum > 0): ?>
                <span class="nav-badge"><?= $invoice_belum ?></span>
                <?php endif; ?>
                <span class="nav-icon">💳</span>
                <span class="nav-label">Invoice</span>
            </a>
            <a href="/bengkel.tsm/mekanik.php" class="nav-card <?= $halaman==='mekanik.php' ? 'aktif' : '' ?>">
                <span class="nav-icon">👨‍🔧</span>
                <span class="nav-label">Mekanik</span>
            </a>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
        <h2 style="margin-top:8px">Master Data & Laporan</h2>
        <div class="nav-grid">
            <a href="/bengkel.tsm/jasa.php" class="nav-card <?= $halaman==='jasa.php' ? 'aktif' : '' ?>">
                <span class="nav-icon">📋</span>
                <span class="nav-label">Master Jasa</span>
                <span class="nav-admin-tag">admin</span>
            </a>
            <a href="/bengkel.tsm/sparepart.php" class="nav-card <?= $halaman==='sparepart.php' ? 'aktif' : '' ?>">
                <span class="nav-icon">📦</span>
                <span class="nav-label">Suku Cadang</span>
                <span class="nav-admin-tag">admin</span>
            </a>
            <a href="/bengkel.tsm/laporan.php" class="nav-card <?= $halaman==='laporan.php' ? 'aktif' : '' ?>">
                <span class="nav-icon">📈</span>
                <span class="nav-label">Laporan</span>
                <span class="nav-admin-tag">admin</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-wrapper">