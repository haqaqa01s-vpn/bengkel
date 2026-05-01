<?php
require_once 'koneksi.php';
cekLogin();

$halaman = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEFA TSM - Form Servis</title>
    <link rel="stylesheet" href="/bengkel.tsm/assets/css/style.css">
    <script src="/bengkel.tsm/assets/js/script.js" defer></script>
    <style>
        /* Override khusus halaman form */
        .main-wrapper {
            padding-top: 80px !important; /* hanya topbar */
        }
    </style>
</head>
<body>

<!-- TOPBAR MINIMAL -->
<header class="topbar" style="position:fixed;top:0;left:0;right:0;z-index:100;">
    <div class="topbar-inner">
        <div class="topbar-brand">
            <div class="icon">🏍️</div>
            <div class="text">
                <h3>Bengkel TEFA TSM</h3>
                <span>Form Pendaftaran Servis</span>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info">
                <div class="nama">👤 <?= htmlspecialchars($user['nama_lengkap']) ?></div>
                <span class="role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-kasir' ?>">
                    <?= $user['role'] === 'admin' ? '🔑 Administrator' : '💳 Kasir' ?>
                </span>
            </div>
            <a href="/bengkel.tsm/antrian.php" class="btn" style="font-size:12px;text-decoration:none;">
                ← Kembali
            </a>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<div class="main-wrapper">