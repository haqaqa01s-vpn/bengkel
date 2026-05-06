<?php
require 'koneksi.php';
require 'header.php';

$id = $_GET['id'] ?? 0;

// Ambil data servis lengkap
$stmt = $pdo->prepare("
    SELECT s.*, p.nama AS nama_pelanggan, p.telepon, p.alamat, p.tipe, p.kelas,
           mk.nama AS nama_mekanik, j.nama_jasa,
           mot.plat, mot.merk, mot.tipe AS tipe_motor, mot.tahun, mot.no_rangka, mot.no_mesin
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    JOIN mekanik mk ON s.mekanik_id = mk.id
    LEFT JOIN jasa_servis j ON s.jasa_id = j.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$servis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servis) {
    echo "<div style='padding:40px;text-align:center;color:#888'>Servis tidak ditemukan.</div>";
    require 'footer.php';
    exit;
}

// Ambil sparepart master
$part_master = $pdo->prepare("
    SELECT sp.*, sc.kode_part, sc.nama_part
    FROM servis_part sp
    JOIN suku_cadang sc ON sp.part_id = sc.id
    WHERE sp.servis_id = ?
");
$part_master->execute([$id]);
$part_master = $part_master->fetchAll(PDO::FETCH_ASSOC);

// Ambil sparepart custom
$part_custom = $pdo->prepare("SELECT * FROM sparepart_custom WHERE servis_id = ?");
$part_custom->execute([$id]);
$part_custom = $part_custom->fetchAll(PDO::FETCH_ASSOC);

// Cek invoice
$invoice = $pdo->prepare("SELECT * FROM invoice WHERE servis_id = ?");
$invoice->execute([$id]);
$invoice = $invoice->fetch(PDO::FETCH_ASSOC);

// Hitung total
$total_part_master = array_sum(array_column($part_master, 'subtotal'));
$total_part_custom = array_sum(array_column($part_custom, 'subtotal'));
$total_part = $total_part_master + $total_part_custom;
$total_biaya = $servis['biaya_jasa'] + $total_part;
?>

<div class="page-header">
    <h1>📋 Detail Servis #<?= $id ?></h1>
    <div style="display:flex;gap:8px">
        <a href="antrian.php" class="btn btn-sm">← Kembali</a>
        <?php if ($invoice): ?>
        <a href="cetak_invoice.php?id=<?= $invoice['id'] ?>" target="_blank" class="btn btn-sm btn-primary">🖨️ Cetak Invoice</a>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    
    <!-- Status -->
    <div class="kartu">
        <strong style="display:block;margin-bottom:12px">📌 Status Servis</strong>
        <div style="display:flex;align-items:center;gap:12px">
            <span class="badge badge-<?= $servis['status'] ?>" style="font-size:14px;padding:6px 16px">
                <?= $servis['status'] === 'menunggu' ? '⏳ Menunggu' : ($servis['status'] === 'proses' ? '🔧 Sedang Dikerjakan' : '✅ Selesai') ?>
            </span>
            <div style="font-size:12px;color:#888">
                Dibuat: <?= date('d M Y H:i', strtotime($servis['dibuat'])) ?>
            </div>
        </div>
    </div>

    <!-- Invoice -->
    <div class="kartu">
        <strong style="display:block;margin-bottom:12px">💳 Invoice</strong>
        <?php if ($invoice): ?>
        <div>
            <span style="font-weight:500">INV-<?= str_pad($invoice['id'], 4, '0', STR_PAD_LEFT) ?></span>
            <span class="badge badge-<?= $invoice['status'] === 'lunas' ? 'lunas' : 'belum' ?>" style="margin-left:8px">
                <?= $invoice['status'] === 'lunas' ? 'Lunas' : 'Belum Lunas' ?>
            </span>
            <div style="font-size:12px;color:#888;margin-top:4px">
                Total: Rp <?= number_format($invoice['biaya_jasa'] + $invoice['biaya_part'] + ($invoice['ppn_nominal'] ?? 0), 0, ',', '.') ?>
            </div>
        </div>
        <?php else: ?>
        <span style="color:#888;font-size:13px">Belum ada invoice</span>
        <?php endif; ?>
    </div>
</div>

<!-- Pelanggan & Motor -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <div class="kartu">
        <strong style="display:block;margin-bottom:12px">👤 Pelanggan</strong>
        <table style="font-size:13px">
            <tr><td style="color:#888;width:80px">Nama</td><td><strong><?= htmlspecialchars($servis['nama_pelanggan']) ?></strong></td></tr>
            <tr><td style="color:#888">Telepon</td><td><?= htmlspecialchars($servis['telepon'] ?: '-') ?></td></tr>
            <tr><td style="color:#888">Alamat</td><td><?= htmlspecialchars($servis['alamat'] ?: '-') ?></td></tr>
            <tr><td style="color:#888">Tipe</td><td><?= ucfirst($servis['tipe_pelanggan']) ?><?= $servis['tipe_pelanggan'] === 'siswa' && $servis['kelas'] ? ' · ' . htmlspecialchars($servis['kelas']) : '' ?></td></tr>
        </table>
    </div>

    <div class="kartu">
        <strong style="display:block;margin-bottom:12px">🏍️ Motor</strong>
        <table style="font-size:13px">
            <tr><td style="color:#888;width:80px">Plat</td><td><strong><?= strtoupper(htmlspecialchars($servis['plat'] ?? '-')) ?></strong></td></tr>
            <tr><td style="color:#888">Merk/Tipe</td><td><?= htmlspecialchars($servis['merk'] ?? '') ?> <?= htmlspecialchars($servis['tipe_motor'] ?? '') ?></td></tr>
            <tr><td style="color:#888">Tahun</td><td><?= $servis['tahun'] ?: '-' ?></td></tr>
            <?php if($servis['no_rangka']): ?>
            <tr><td style="color:#888">No. Rangka</td><td style="font-size:11px"><?= htmlspecialchars($servis['no_rangka']) ?></td></tr>
            <?php endif; ?>
            <?php if($servis['no_mesin']): ?>
            <tr><td style="color:#888">No. Mesin</td><td style="font-size:11px"><?= htmlspecialchars($servis['no_mesin']) ?></td></tr>
            <?php endif; ?>
            <?php if($servis['km_sekarang']): ?>
            <tr><td style="color:#888">KM</td><td><?= number_format($servis['km_sekarang'], 0, ',', '.') ?> KM</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Jasa -->
<div class="kartu" style="margin-bottom:16px">
    <strong style="display:block;margin-bottom:12px">🔧 Jasa Servis</strong>
    <table style="font-size:13px">
        <tr>
            <td style="color:#888;width:100px">Kategori</td>
            <td>
                <?php
                $icon = ['ringan'=>'🔵','sedang'=>'🟡','berat'=>'🔴'];
                echo ($icon[$servis['kategori_servis']] ?? '') . ' ' . ucfirst($servis['kategori_servis'] ?? '-');
                ?>
            </td>
        </tr>
        <tr>
            <td style="color:#888">Deskripsi</td>
            <td>
                <?php if ($servis['jasa_id']): ?>
                    <?= htmlspecialchars($servis['nama_jasa']) ?> <small style="color:#888">(master)</small>
                <?php elseif ($servis['harga_jasa_custom'] > 0): ?>
                    <?= htmlspecialchars($servis['deskripsi_jasa'] ?? $servis['layanan']) ?> <small style="color:#f59e0b">(custom)</small>
                <?php else: ?>
                    <?= htmlspecialchars($servis['layanan'] ?: '-') ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td style="color:#888">Biaya</td>
            <td><strong>Rp <?= number_format($servis['biaya_jasa'], 0, ',', '.') ?></strong></td>
        </tr>
        <tr>
            <td style="color:#888">Mekanik</td>
            <td>👨‍🔧 <?= htmlspecialchars($servis['nama_mekanik']) ?></td>
        </tr>
        <?php if($servis['keluhan']): ?>
        <tr>
            <td style="color:#888">Keluhan</td>
            <td style="font-style:italic">"<?= htmlspecialchars($servis['keluhan']) ?>"</td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<!-- Sparepart -->
<?php if (!empty($part_master) || !empty($part_custom)): ?>
<div class="kartu" style="margin-bottom:16px">
    <strong style="display:block;margin-bottom:12px">🛠️ Suku Cadang</strong>
    <table>
        <thead>
            <tr><th>Part</th><th>Jml</th><th>Harga</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php foreach($part_master as $pm): ?>
            <tr>
                <td><?= htmlspecialchars($pm['kode_part']) ?> — <?= htmlspecialchars($pm['nama_part']) ?></td>
                <td><?= $pm['jumlah'] ?></td>
                <td>Rp <?= number_format($pm['harga_satuan'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($pm['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php foreach($part_custom as $pc): ?>
            <tr>
                <td style="color:#f59e0b">🛒 <?= htmlspecialchars($pc['nama_part']) ?> <small>(custom)</small></td>
                <td><?= $pc['jumlah'] ?></td>
                <td>Rp <?= number_format($pc['harga_jual'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($pc['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:500;border-top:2px solid #e5e5e5">
                <td colspan="3">Total Suku Cadang</td>
                <td>Rp <?= number_format($total_part, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>

<!-- Ringkasan Biaya -->
<div class="kartu">
    <strong style="display:block;margin-bottom:12px">💰 Ringkasan Biaya</strong>
    <div style="display:flex;justify-content:flex-end">
        <div style="width:300px">
            <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px">
                <span>Biaya Jasa</span>
                <span>Rp <?= number_format($servis['biaya_jasa'], 0, ',', '.') ?></span>
            </div>
            <?php if($total_part > 0): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px">
                <span>Suku Cadang</span>
                <span>Rp <?= number_format($total_part, 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <?php if($invoice && $invoice['ppn_nominal'] > 0): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;color:#d97706">
                <span>PPN (<?= $invoice['ppn_persen'] ?>%)</span>
                <span>Rp <?= number_format($invoice['ppn_nominal'], 0, ',', '.') ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-weight:700;font-size:16px;border-top:2px solid #1e3a5f;margin-top:4px">
                <span>TOTAL</span>
                <span>Rp <?= number_format($total_biaya + ($invoice['ppn_nominal'] ?? 0), 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>