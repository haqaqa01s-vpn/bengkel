<?php
require 'koneksi.php';
require 'header.php';

// Ambil data ringkasan
$menunggu = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='menunggu'")->fetchColumn();
$proses   = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='proses'")->fetchColumn();
$selesai  = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='selesai'")->fetchColumn();
$pendapatan = $pdo->query("SELECT COALESCE(SUM(biaya_jasa + biaya_part),0) FROM invoice WHERE status='lunas'")->fetchColumn();

// Ambil antrian terbaru
$antrian = $pdo->query("
  SELECT s.*, p.nama AS nama_pelanggan, m.nama AS nama_mekanik
  FROM servis s
  JOIN pelanggan p ON s.pelanggan_id = p.id
  JOIN mekanik m ON s.mekanik_id = m.id
  ORDER BY s.dibuat DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1>Dashboard</h1>
  <span style="font-size:12px;color:#888"><?= date('l, d F Y') ?></span>
</div>

<div class="grid-metrik">
  <div class="metrik"><label>Antrian</label><span><?= $menunggu ?></span></div>
  <div class="metrik"><label>Sedang dikerjakan</label><span><?= $proses ?></span></div>
  <div class="metrik"><label>Selesai hari ini</label><span><?= $selesai ?></span></div>
  <div class="metrik"><label>Pendapatan</label><span>Rp <?= number_format($pendapatan,0,',','.') ?></span></div>
</div>

<div class="kartu">
  <div class="page-header" style="margin-bottom:12px">
    <strong>Antrian terbaru</strong>
    <a href="antrian.php" class="btn btn-sm">Lihat semua</a>
  </div>
  <table>
    <thead><tr><th>Pelanggan</th><th>Kendaraan</th><th>Layanan</th><th>Mekanik</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach($antrian as $a): ?>
      <tr>
        <td><?= htmlspecialchars($a['nama_pelanggan']) ?></td>
        <td><?= $a['kendaraan'] ?> · <?= $a['plat'] ?></td>
        <td><?= $a['layanan'] ?></td>
        <td><?= htmlspecialchars($a['nama_mekanik']) ?></td>
        <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($antrian)): ?>
      <tr><td colspan="5" style="text-align:center;color:#888;padding:20px">Belum ada data</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require 'footer.php'; ?>