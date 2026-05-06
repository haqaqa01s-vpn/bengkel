<?php
require 'koneksi.php';
require 'header.php';

// Data ringkasan
$menunggu = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='menunggu'")->fetchColumn();
$proses   = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='proses'")->fetchColumn();
$selesai  = $pdo->query("SELECT COUNT(*) FROM servis WHERE status='selesai' AND DATE(dibuat) = CURDATE()")->fetchColumn();
$pendapatan = $pdo->query("SELECT COALESCE(SUM(biaya_jasa + biaya_part + ppn_nominal),0) FROM invoice WHERE status='lunas' AND DATE(dibuat) = CURDATE()")->fetchColumn();

// Servis menunggu & proses (detail)
$servis_aktif = $pdo->query("
    SELECT s.*, p.nama AS nama_pelanggan, mk.nama AS nama_mekanik, j.nama_jasa,
           mot.plat, mot.merk, mot.tipe AS kendaraan, mot.tahun
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    JOIN mekanik mk ON s.mekanik_id = mk.id
    LEFT JOIN jasa_servis j ON s.jasa_id = j.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    WHERE s.status IN ('menunggu', 'proses')
    ORDER BY FIELD(s.status, 'proses', 'menunggu'), s.dibuat ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Pengingat servis berkala
$notif_servis = $pdo->query("
    SELECT s.*, p.nama AS nama_pelanggan, p.telepon,
           mot.plat, mot.merk, mot.tipe AS kendaraan, mot.tahun
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    WHERE s.km_servis_selanjutnya IS NOT NULL
      AND s.status = 'selesai'
    ORDER BY s.km_servis_selanjutnya ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if(isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    <?= $_GET['pesan'] === 'berhasil' ? '✅ Servis berhasil ditambahkan!' : '✅ Servis berhasil diupdate!' ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h1>📊 Dashboard</h1>
    <div style="display:flex;gap:8px;align-items:center">
        <span style="font-size:12px;color:#888"><?= date('l, d F Y') ?></span>
        <a href="form-servis.php" class="btn btn-primary">➕ Servis Baru</a>
    </div>
</div>

<!-- Metrik -->
<div class="grid-metrik">
    <div class="metrik" style="background:#fef3c7;border-left:4px solid #f59e0b">
        <label>⏳ Menunggu</label>
        <span><?= $menunggu ?></span>
    </div>
    <div class="metrik" style="background:#dbeafe;border-left:4px solid #3b82f6">
        <label>🔧 Sedang Dikerjakan</label>
        <span><?= $proses ?></span>
    </div>
    <div class="metrik" style="background:#dcfce7;border-left:4px solid #22c55e">
        <label>✅ Selesai Hari Ini</label>
        <span><?= $selesai ?></span>
    </div>
    <div class="metrik" style="background:#ede9fe;border-left:4px solid #8b5cf6">
        <label>💰 Pendapatan Hari Ini</label>
        <span style="font-size:18px">Rp <?= number_format($pendapatan,0,',','.') ?></span>
    </div>
</div>

<!-- Servis Aktif (Menunggu & Proses) -->
<div class="kartu">
    <div class="page-header" style="margin-bottom:12px">
        <strong>🔧 Servis Aktif (Menunggu & Proses)</strong>
        <span style="font-size:12px;color:#888"><?= count($servis_aktif) ?> servis</span>
    </div>
    
    <?php if (!empty($servis_aktif)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(350px, 1fr));gap:12px">
        <?php foreach($servis_aktif as $sa): ?>
        <div style="border:1px solid <?= $sa['status']=='proses' ? '#dbeafe' : '#fef3c7' ?>;border-radius:10px;padding:14px;background:<?= $sa['status']=='proses' ? '#f8faff' : '#fffcf0' ?>">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
                <div>
                    <strong style="font-size:14px"><?= htmlspecialchars($sa['nama_pelanggan']) ?></strong>
                    <div style="font-size:12px;color:#888">
                        <?= htmlspecialchars($sa['merk'] ?? '') ?> <?= htmlspecialchars($sa['kendaraan'] ?? '') ?> 
                        · <?= htmlspecialchars($sa['plat'] ?? '-') ?>
                    </div>
                </div>
                <span class="badge badge-<?= $sa['status'] ?>"><?= $sa['status']=='proses' ? '🔧 Proses' : '⏳ Menunggu' ?></span>
            </div>
            
            <div style="font-size:12px;color:#555;margin-bottom:6px">
                <strong>Jasa:</strong> <?= htmlspecialchars($sa['nama_jasa'] ?? $sa['layanan'] ?? '-') ?>
            </div>
            
            <?php if($sa['keluhan']): ?>
            <div style="font-size:11px;color:#999;margin-bottom:8px;background:#f9f9f9;padding:6px 8px;border-radius:6px">
                "<?= htmlspecialchars($sa['keluhan']) ?>"
            </div>
            <?php endif; ?>
            
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div style="font-size:11px;color:#888">
                    👨‍🔧 <?= htmlspecialchars($sa['nama_mekanik']) ?> · 
                    <?php if($sa['biaya_jasa'] > 0): ?>
                    Rp <?= number_format($sa['biaya_jasa'],0,',','.') ?>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:4px">
                    <a href="edit-servis.php?id=<?= $sa['id'] ?>" class="btn btn-sm" style="background:#f3f4f6">✏️</a>
                    <?php if($sa['status'] === 'menunggu'): ?>
                        <a href="antrian.php?ubah_status=<?= $sa['id'] ?>&status=proses&redirect=index" class="btn btn-sm btn-primary">▶ Mulai</a>
                    <?php else: ?>
                        <a href="antrian.php?ubah_status=<?= $sa['id'] ?>&status=selesai&redirect=index" class="btn btn-sm" style="background:#22c55e;color:#fff;border-color:#22c55e">✓ Selesai</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:30px;color:#888">
        <p style="font-size:40px;margin-bottom:8px">🎉</p>
        <p>Tidak ada servis aktif. Semua sudah selesai!</p>
        <a href="form-servis.php" class="btn btn-primary" style="margin-top:12px">➕ Tambah Servis Baru</a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($notif_servis)): ?>
<!-- Pengingat Servis Berkala -->
<div class="kartu">
    <div class="page-header" style="margin-bottom:12px">
        <strong>⏰ Pengingat Servis Berkala</strong>
        <a href="laporan.php" class="btn btn-sm">Lihat Semua</a>
    </div>
    <table>
        <thead>
            <tr><th>Pelanggan</th><th>Motor</th><th>KM Terakhir</th><th>Servis di KM</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($notif_servis as $ns): ?>
            <tr>
                <td><?= htmlspecialchars($ns['nama_pelanggan']) ?></td>
                <td>
                    <?= htmlspecialchars($ns['merk'] ?? '') ?> <?= htmlspecialchars($ns['kendaraan'] ?? '') ?>
                    <div style="font-size:11px;color:#888"><?= htmlspecialchars($ns['plat'] ?? '') ?></div>
                </td>
                <td><?= number_format($ns['km_sekarang'], 0, ',', '.') ?> KM</td>
                <td><span style="background:#fff7ed;color:#c2410c;padding:3px 10px;border-radius:20px;font-weight:500;font-size:12px"><?= number_format($ns['km_servis_selanjutnya'], 0, ',', '.') ?> KM</span></td>
                <td>
                    <?php if($ns['telepon']): ?>
                    <a href="https://wa.me/62<?= ltrim($ns['telepon'], '0') ?>?text=Halo%20<?= urlencode($ns['nama_pelanggan']) ?>%2C%20motor%20<?= urlencode($ns['plat']) ?>%20Anda%20sudah%20saatnya%20servis%20berkala.%20Silakan%20datang%20ke%20Bengkel%20TEFA%20TSM." target="_blank" class="btn btn-sm" style="background:#25d366;color:#fff">📱 WA</a>
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