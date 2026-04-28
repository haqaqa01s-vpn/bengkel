<?php
session_start();
$halaman = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TEFA TSM</title>
  <link rel="stylesheet" href="/bengkel/assets/css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="logo">
      <h2>TEFA TSM</h2>
      <p>Teknik Sepeda Motor</p>
    </div>
    <nav>
      <a href="/bengkel/index.php"   class="<?= $halaman==='index.php'  ?'aktif':'' ?>">Dashboard</a>
      <a href="/bengkel/antrian.php" class="<?= $halaman==='antrian.php'?'aktif':'' ?>">Antrian Servis</a>
      <a href="/bengkel/mekanik.php" class="<?= $halaman==='mekanik.php'?'aktif':'' ?>">Mekanik</a>
      <a href="/bengkel/invoice.php" class="<?= $halaman==='invoice.php'?'aktif':'' ?>">Invoice</a>
      <a href="/bengkel/laporan.php" class="<?= $halaman==='laporan.php'?'aktif':'' ?>">Laporan</a>
    </nav>
  </aside>
  <main class="konten">