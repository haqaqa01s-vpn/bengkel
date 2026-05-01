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
  SELECT s.*, p.nama AS nama_pelanggan, mk.nama AS nama_mekanik,
         mot.plat, mot.merk, mot.tipe AS kendaraan, mot.tahun
  FROM servis s
  JOIN pelanggan p ON s.pelanggan_id = p.id
  JOIN mekanik mk ON s.mekanik_id = mk.id
  LEFT JOIN motor mot ON s.motor_id = mot.id
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

<?php
// Ambil servis yang sudah melewati km_servis_selanjutnya
$notif_servis = $pdo->query("
    SELECT s.*, p.nama AS nama_pelanggan, p.telepon, mk.nama AS nama_mekanik,
           mot.plat, mot.merk, mot.tipe AS kendaraan, mot.tahun
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    LEFT JOIN mekanik mk ON s.mekanik_id = mk.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    WHERE s.km_servis_selanjutnya IS NOT NULL
      AND s.status = 'selesai'
    ORDER BY s.km_servis_selanjutnya ASC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Cek apakah ada yang sudah waktunya servis (asumsi: km sekarang sudah lewat)
// Untuk demo, kita tampilkan semua yang punya catatan km_servis_selanjutnya
?>

<?php if(!empty($notif_servis)): ?>
<div class="kartu">
    <div class="page-header" style="margin-bottom:12px">
        <strong>⏰ Pengingat Servis Berkala</strong>
        <a href="laporan.php" class="btn btn-sm">Lihat Semua</a>
    </div>
    <table>
        <thead>
            <tr><th>Pelanggan</th><th>Motor</th><th>KM Saat Servis Terakhir</th><th>Servis Selanjutnya di KM</th><th>Telepon</th></tr>
        </thead>
        <tbody>
            <?php foreach($notif_servis as $ns): ?>
            <tr>
                <td><?= htmlspecialchars($ns['nama_pelanggan']) ?></td>
                <td><?= $ns['merk'] ?> <?= $ns['kendaraan'] ?>
                    <div style="font-size:11px;color:#888"><?= $ns['plat'] ?></div></td>
                <td style="font-weight:500"><?= number_format($ns['km_sekarang'], 0, ',', '.') ?> KM</td>
                <td>
                    <span style="background:#fff7ed;color:#c2410c;padding:3px 10px;border-radius:20px;font-weight:500;font-size:12px">
                        <?= number_format($ns['km_servis_selanjutnya'], 0, ',', '.') ?> KM
                    </span>
                </td>
                <td>
                    <?php if($ns['telepon']): ?>
                    <a href="https://wa.me/62<?= ltrim($ns['telepon'], '0') ?>?text=Halo%20<?= urlencode($ns['nama_pelanggan']) ?>%2C%20motor%20<?= urlencode($ns['plat']) ?>%20Anda%20sudah%20saatnya%20servis%20berkala%20di%20km%20<?= number_format($ns['km_servis_selanjutnya'],0,',','.') ?>.%20Silakan%20datang%20ke%20Bengkel%20TEFA%20TSM."
                       target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff;font-size:11px">
                        📱 WA
                    </a>
                    <?php else: ?>
                    <span style="color:#888;font-size:11px">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>