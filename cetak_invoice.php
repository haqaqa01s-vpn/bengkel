<?php
require 'koneksi.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT i.*, p.nama AS nama_pelanggan, p.telepon, p.alamat, p.tipe, p.kelas,
           s.layanan, s.keluhan, s.km_sekarang, s.km_servis_selanjutnya,
           s.kategori_servis, s.deskripsi_jasa, j.nama_jasa,
           s.tipe_pelanggan,
           mot.tipe AS kendaraan, mot.plat, mot.merk, mot.tahun,
           mk.nama AS nama_mekanik,
           (i.biaya_jasa + i.biaya_part) AS total
    FROM invoice i
    JOIN servis s ON i.servis_id = s.id
    JOIN pelanggan p ON s.pelanggan_id = p.id
    JOIN mekanik mk ON s.mekanik_id = mk.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    LEFT JOIN jasa_servis j ON s.jasa_id = j.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Invoice tidak ditemukan. ID: " . $id);
}

// Ambil sparepart
$part_master = $pdo->prepare("
    SELECT sp.*, sc.kode_part, sc.nama_part
    FROM servis_part sp
    JOIN suku_cadang sc ON sp.part_id = sc.id
    WHERE sp.servis_id = ?
");
$part_master->execute([$data['servis_id']]);
$part_master = $part_master->fetchAll(PDO::FETCH_ASSOC);

$part_custom = $pdo->prepare("SELECT * FROM sparepart_custom WHERE servis_id = ?");
$part_custom->execute([$data['servis_id']]);
$part_custom = $part_custom->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Invoice INV-<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1f2937;
            padding: 30px;
            max-width: 800px;
            margin: auto;
            background: #fff;
        }
        .no-print { text-align: right; margin-bottom: 16px; }
        .no-print button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 8px;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-close { background: #6b7280; color: #fff; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header .logo .kotak {
            width: 48px; height: 48px;
            background: #1e3a5f; color: #fff;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            font-weight: bold; font-size: 11px; text-align: center;
        }
        .header .sekolah h3 { font-size: 16px; color: #1e3a5f; }
        .header .sekolah p { font-size: 11px; color: #6b7280; }
        .no-invoice { text-align: right; font-size: 18px; font-weight: 700; color: #1e3a5f; }
        
        .badge-lunas { background: #059669; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 12px; }
        .badge-belum { background: #d97706; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 12px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .info-box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; }
        .info-box h4 { font-size: 11px; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; }
        .info-box p { font-size: 13px; margin-bottom: 4px; }
        .info-box .label { font-size: 10px; color: #9ca3af; }
        
        table.detail { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.detail thead th {
            background: #f3f4f6; padding: 10px; text-align: left;
            font-size: 11px; text-transform: uppercase; color: #6b7280;
        }
        table.detail tbody td { padding: 12px 10px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
        
        .total-box { display: flex; justify-content: flex-end; margin-bottom: 32px; }
        .total-box .rincian { width: 280px; }
        .total-box .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .total-box .row.akhir {
            border-top: 2px solid #1e3a5f;
            margin-top: 6px; padding-top: 10px;
            font-weight: 700; font-size: 16px;
        }
        
        .reminder {
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 20px;
        }
        .reminder strong { color: #c2410c; }
        .reminder p { margin: 4px 0 0; font-size: 13px; color: #7c2d12; }
        
        .footer-cetak {
            text-align: center; color: #9ca3af; font-size: 11px;
            border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 40px;
        }
        
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak / Print</button>
    <button onclick="window.close()" class="btn-close">✕ Tutup</button>
</div>

<!-- Kop -->
<div class="header">
    <div class="logo">
        <div class="kotak">SMK<br>TEFA</div>
        <div class="sekolah">
            <h3>Bengkel TEFA TSM</h3>
            <p>Teknik Sepeda Motor • Teaching Factory</p>
            <p style="font-size:10px">Jl. Pendidikan No. 1 • Telp: (021) 123-4567</p>
        </div>
    </div>
    <div>
        <div class="no-invoice">INV-<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></div>
        <?php if ($data['status'] === 'lunas'): ?>
            <span class="badge-lunas">✓ LUNAS</span>
        <?php else: ?>
            <span class="badge-belum">◷ Belum Lunas</span>
        <?php endif; ?>
    </div>
</div>

<!-- Info -->
<div class="info-grid">
    <div class="info-box">
        <h4>👤 Data Pelanggan</h4>
        <p><strong><?= htmlspecialchars($data['nama_pelanggan']) ?></strong></p>
        <p><span class="label">Telp:</span> <?= htmlspecialchars($data['telepon'] ?: '-') ?></p>
        <p><span class="label">Alamat:</span> <?= htmlspecialchars($data['alamat'] ?: '-') ?></p>
        <p><span class="label">Tipe:</span> <?= ucfirst($data['tipe_pelanggan']) ?>
           <?= $data['tipe_pelanggan'] === 'siswa' && $data['kelas'] ? ' • Kelas ' . htmlspecialchars($data['kelas']) : '' ?></p>
    </div>
    <div class="info-box">
        <h4>🏍️ Data Kendaraan</h4>
        <p><strong><?= htmlspecialchars($data['merk'] ?? '') ?> <?= htmlspecialchars($data['kendaraan'] ?? '') ?></strong></p>
        <p><span class="label">Plat:</span> <?= strtoupper($data['plat'] ?? '-') ?></p>
        <p><span class="label">Tahun:</span> <?= $data['tahun'] ?: '-' ?></p>
        <?php if($data['km_sekarang']): ?>
        <p><span class="label">KM Saat Ini:</span> <?= number_format($data['km_sekarang'], 0, ',', '.') ?> KM</p>
        <?php endif; ?>
    </div>
</div>

<!-- Detail Servis -->
<table class="detail">
    <thead>
        <tr><th>Jenis Layanan</th><th>Keluhan</th><th>Mekanik</th></tr>
    </thead>
    <tbody>
        <tr>
            <td><strong><?= htmlspecialchars($data['layanan'] ?? '-') ?></strong></td>
            <td style="font-size:12px"><?= htmlspecialchars($data['keluhan'] ?: '-') ?></td>
            <td><?= htmlspecialchars($data['nama_mekanik']) ?></td>
        </tr>
    </tbody>
</table>

<!-- Jasa Servis -->
<h4 style="margin:20px 0 10px;font-size:14px;color:#1e3a5f">🔧 Jasa Servis</h4>
<table class="detail">
    <thead>
        <tr><th>Kategori</th><th>Deskripsi Jasa</th><th>Biaya</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php
                $kat = $data['kategori_servis'] ?? '';
                $icon = ['ringan'=>'🔵','sedang'=>'🟡','berat'=>'🔴'];
                echo ($icon[$kat] ?? '') . ' ' . ucfirst($kat ?: '-');
                ?>
            </td>
            <td>
                <?php
                if (!empty($data['deskripsi_jasa'])) {
                    echo htmlspecialchars($data['deskripsi_jasa']) . ' <small style="color:#f59e0b">(custom)</small>';
                } elseif (!empty($data['nama_jasa'])) {
                    echo htmlspecialchars($data['nama_jasa']) . ' <small style="color:#888">(master)</small>';
                } else {
                    echo htmlspecialchars($data['layanan'] ?: '-');
                }
                ?>
            </td>
            <td style="font-weight:500">Rp <?= number_format($data['biaya_jasa'], 0, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<!-- Sparepart -->
<?php if (!empty($part_master) || !empty($part_custom)): ?>
<h4 style="margin:20px 0 10px;font-size:14px;color:#1e3a5f">🛠️ Suku Cadang</h4>
<table class="detail">
    <thead><tr><th>Kode/Nama</th><th>Jml</th><th>Harga</th><th>Subtotal</th></tr></thead>
    <tbody>
        <?php foreach($part_master as $pm): ?>
        <tr>
            <td><?= htmlspecialchars($pm['kode_part']) ?> — <?= htmlspecialchars($pm['nama_part']) ?></td>
            <td><?= $pm['jumlah'] ?></td>
            <td>Rp <?= number_format($pm['harga_satuan'],0,',','.') ?></td>
            <td>Rp <?= number_format($pm['subtotal'],0,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php foreach($part_custom as $pc): ?>
        <tr>
            <td style="color:#f59e0b">🛒 <?= htmlspecialchars($pc['nama_part']) ?></td>
            <td><?= $pc['jumlah'] ?></td>
            <td>Rp <?= number_format($pc['harga_jual'],0,',','.') ?></td>
            <td>Rp <?= number_format($pc['subtotal'],0,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Reminder -->
<?php if($data['km_servis_selanjutnya']): ?>
<div class="reminder">
    <strong>⏰ Servis Berkala Selanjutnya</strong>
    <p>Saat kilometer mencapai <strong><?= number_format($data['km_servis_selanjutnya'], 0, ',', '.') ?> KM</strong> (setiap 3.000 KM)</p>
</div>
<?php endif; ?>

<!-- Total -->
<div class="total-box">
    <div class="rincian">
        <div class="row"><span>Biaya Jasa</span><span>Rp <?= number_format($data['biaya_jasa'], 0, ',', '.') ?></span></div>
        <div class="row"><span>Biaya Suku Cadang</span><span>Rp <?= number_format($data['biaya_part'], 0, ',', '.') ?></span></div>
        <?php if ($data['ppn_nominal'] > 0): ?>
        <div class="row" style="color:#d97706"><span>PPN (<?= $data['ppn_persen'] ?>%)</span><span>Rp <?= number_format($data['ppn_nominal'], 0, ',', '.') ?></span></div>
        <?php endif; ?>
        <div class="row akhir"><span>TOTAL</span><span>Rp <?= number_format($data['total'], 0, ',', '.') ?></span></div>
    </div>
</div>

<div style="font-size:11px;color:#6b7280;margin-bottom:16px">
    <strong>Tanggal:</strong> <?= date('d F Y', strtotime($data['dibuat'])) ?>
</div>

<div class="footer-cetak">
    Terima kasih • Dokumen dicetak: <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>