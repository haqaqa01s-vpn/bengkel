<?php
require 'koneksi.php';
require 'header.php';

$bulan = $_GET['bulan'] ?? date('Y-m');

$total_servis = $pdo->prepare("
  SELECT COUNT(*) FROM servis
  WHERE DATE_FORMAT(dibuat, '%Y-%m') = ?
");
$total_servis->execute([$bulan]);
$total_servis = $total_servis->fetchColumn();

$pendapatan = $pdo->prepare("
  SELECT COALESCE(SUM(biaya_jasa + biaya_part), 0)
  FROM invoice i
  JOIN servis s ON i.servis_id = s.id
  WHERE i.status = 'lunas' AND DATE_FORMAT(s.dibuat, '%Y-%m') = ?
");
$pendapatan->execute([$bulan]);
$pendapatan = $pendapatan->fetchColumn();

$rata = $total_servis > 0 ? round($pendapatan / $total_servis) : 0;
// Tambah query ini setelah query $rata
$pelanggan_umum = $pdo->prepare("
  SELECT COUNT(*) FROM servis
  WHERE tipe_pelanggan='umum' AND DATE_FORMAT(dibuat,'%Y-%m')=?
");
$pelanggan_umum->execute([$bulan]);
$pelanggan_umum = $pelanggan_umum->fetchColumn();

$pelanggan_siswa = $pdo->prepare("
  SELECT COUNT(*) FROM servis
  WHERE tipe_pelanggan='siswa' AND DATE_FORMAT(dibuat,'%Y-%m')=?
");
$pelanggan_siswa->execute([$bulan]);
$pelanggan_siswa = $pelanggan_siswa->fetchColumn();
$total_mekanik = $pdo->query("SELECT COUNT(*) FROM mekanik")->fetchColumn();

// Servis per layanan
$per_layanan = $pdo->prepare("
  SELECT layanan, COUNT(*) AS jumlah
  FROM servis
  WHERE DATE_FORMAT(dibuat, '%Y-%m') = ?
  GROUP BY layanan ORDER BY jumlah DESC
");
$per_layanan->execute([$bulan]);
$per_layanan = $per_layanan->fetchAll(PDO::FETCH_ASSOC);

// Kinerja mekanik
$kinerja = $pdo->prepare("
  SELECT m.nama, COUNT(s.id) AS jumlah
  FROM mekanik m
  LEFT JOIN servis s ON s.mekanik_id = m.id
    AND s.status = 'selesai'
    AND DATE_FORMAT(s.dibuat, '%Y-%m') = ?
  GROUP BY m.id ORDER BY jumlah DESC
");
$kinerja->execute([$bulan]);
$kinerja = $kinerja->fetchAll(PDO::FETCH_ASSOC);

$max_layanan = !empty($per_layanan) ? $per_layanan[0]['jumlah'] : 1;
$max_kinerja = !empty($kinerja) ? $kinerja[0]['jumlah'] : 1;
?>

<div class="page-header">
  <h1>Laporan & Statistik</h1>
  <form method="GET" style="display:flex;gap:8px;align-items:center">
    <label style="font-size:13px;color:#888">Bulan:</label>
    <input type="month" name="bulan" value="<?= $bulan ?>" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:7px;font-size:13px">
    <button type="submit" class="btn">Tampilkan</button>
  </form>
</div>

<div class="grid-metrik" style="grid-template-columns:repeat(3,1fr)">
  <div class="metrik"><label>Total servis</label><span><?= $total_servis ?></span></div>
  <div class="metrik"><label>Pelanggan umum</label><span><?= $pelanggan_umum ?></span></div>
  <div class="metrik"><label>Pelanggan siswa</label><span><?= $pelanggan_siswa ?></span></div>
  <div class="metrik"><label>Pendapatan</label><span style="font-size:16px">Rp <?= number_format($pendapatan,0,',','.') ?></span></div>
  <div class="metrik"><label>Rata-rata/servis</label><span style="font-size:16px">Rp <?= number_format($rata,0,',','.') ?></span></div>
  <div class="metrik"><label>Mekanik aktif</label><span><?= $total_mekanik ?></span></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div class="kartu">
    <strong style="display:block;margin-bottom:14px">Jenis layanan terbanyak</strong>
    <?php if(empty($per_layanan)): ?>
      <p style="color:#888;font-size:13px">Belum ada data bulan ini.</p>
    <?php endif; ?>
    <?php foreach($per_layanan as $l):
      $pct = round(($l['jumlah'] / $max_layanan) * 100);
    ?>
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
        <span><?= $l['layanan'] ?></span>
        <span style="color:#888"><?= $l['jumlah'] ?> servis</span>
      </div>
      <div style="background:#f0f0f0;border-radius:20px;height:6px">
        <div style="height:6px;border-radius:20px;background:#2563eb;width:<?= $pct ?>%"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="kartu">
    <strong style="display:block;margin-bottom:14px">Kinerja mekanik</strong>
    <?php if(empty($kinerja)): ?>
      <p style="color:#888;font-size:13px">Belum ada data.</p>
    <?php endif; ?>
    <?php foreach($kinerja as $k):
      $pct = $max_kinerja > 0 ? round(($k['jumlah'] / $max_kinerja) * 100) : 0;
    ?>
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
        <span><?= htmlspecialchars($k['nama']) ?></span>
        <span style="color:#888"><?= $k['jumlah'] ?> selesai</span>
      </div>
      <div style="background:#f0f0f0;border-radius:20px;height:6px">
        <div style="height:6px;border-radius:20px;background:#7c3aed;width:<?= $pct ?>%"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="kartu" style="margin-top:0">
  <strong style="display:block;margin-bottom:14px">Detail servis bulan <?= date('F Y', strtotime($bulan.'-01')) ?></strong>
  <?php
  $detail = $pdo->prepare("
    SELECT s.*, p.nama AS nama_pelanggan, m.nama AS nama_mekanik,
           i.biaya_jasa, i.biaya_part, i.status AS status_bayar
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    JOIN mekanik m ON s.mekanik_id = m.id
    LEFT JOIN invoice i ON i.servis_id = s.id
    WHERE DATE_FORMAT(s.dibuat, '%Y-%m') = ?
    ORDER BY s.dibuat DESC
  ");
  $detail->execute([$bulan]);
  $detail = $detail->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <table>
    <thead>
      <tr><th>Tanggal</th><th>Pelanggan</th><th>Motor</th><th>Layanan</th><th>Mekanik</th><th>Tipe</th><th>Total</th><th>Bayar</th></tr>
    </thead>
    <tbody>
      <?php foreach($detail as $d):
        $total = ($d['biaya_jasa'] ?? 0) + ($d['biaya_part'] ?? 0);
      ?>
      <tr>
        <td style="font-size:12px"><?= date('d M', strtotime($d['dibuat'])) ?></td>
        <td><?= htmlspecialchars($d['nama_pelanggan']) ?></td>
        <td><?= $d['merk'] ?> <?= $d['kendaraan'] ?><div style="font-size:11px;color:#888"><?= $d['plat'] ?></div></td>
        <td><?= $d['layanan'] ?></td>
        <td>
          <span style="font-size:11px;padding:2px 7px;border-radius:20px;
            background:<?= $d['tipe_pelanggan']==='siswa'?'#ede9fe':'#e0f2fe' ?>;
            color:<?= $d['tipe_pelanggan']==='siswa'?'#5b21b6':'#0369a1' ?>">
            <?= ucfirst($d['tipe_pelanggan']) ?>
          </span>
        </td>
        <td><?= htmlspecialchars($d['nama_mekanik']) ?></td>
        <td><?= $total ? 'Rp '.number_format($total,0,',','.') : '-' ?></td>
        <td>
          <?php if($d['status_bayar']): ?>
          <span class="badge badge-<?= $d['status_bayar']==='lunas'?'lunas':'belum' ?>">
            <?= $d['status_bayar']==='lunas'?'Lunas':'Belum' ?>
          </span>
          <?php else: ?>
          <span style="color:#aaa;font-size:12px">-</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($detail)): ?>
      <tr><td colspan="7" style="text-align:center;color:#888;padding:20px">Tidak ada data bulan ini</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require 'footer.php'; ?>