<?php
require 'koneksi.php';
require 'header.php';

// Ubah status (dari dashboard)
if (isset($_GET['ubah_status'])) {
    $stmt = $pdo->prepare("UPDATE servis SET status=? WHERE id=?");
    $stmt->execute([$_GET['status'], $_GET['ubah_status']]);
    $redirect = $_GET['redirect'] ?? 'index';
    header('Location: ' . ($redirect === 'antrian' ? 'antrian.php' : 'index.php'));
    exit;
}

// Pagination
$halaman = isset($_GET['halaman']) ? max(1, intval($_GET['halaman'])) : 1;
$per_halaman = 6;
$offset = ($halaman - 1) * $per_halaman;

// Hitung total data
$total_data = $pdo->query("SELECT COUNT(*) FROM servis WHERE status = 'selesai'")->fetchColumn();
$total_halaman = ceil($total_data / $per_halaman);

// Ambil data sesuai halaman
$stmt = $pdo->query("
    SELECT s.*, p.nama AS nama_pelanggan, mk.nama AS nama_mekanik, j.nama_jasa,
           mot.plat, mot.merk, mot.tipe AS kendaraan, mot.tahun
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    JOIN mekanik mk ON s.mekanik_id = mk.id
    LEFT JOIN jasa_servis j ON s.jasa_id = j.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    WHERE s.status = 'selesai'
    ORDER BY s.dibuat DESC
    LIMIT $per_halaman OFFSET $offset
");
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1>📋 Riwayat Servis</h1>
    <a href="form-servis.php" class="btn btn-primary">➕ Servis Baru</a>
</div>

<?php if(isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    <?= $_GET['pesan'] === 'berhasil' ? '✅ Servis berhasil!' : '✅ Servis diupdate!' ?>
</div>
<?php endif; ?>

<div class="kartu">
    <?php if (!empty($riwayat)): ?>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Kendaraan</th>
                <th>Jasa</th>
                <th>Mekanik</th>
                <th>Biaya</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($riwayat as $r): ?>
            <tr>
                <td style="font-size:12px;white-space:nowrap"><?= date('d/m/Y', strtotime($r['dibuat'])) ?></td>
                <td><?= htmlspecialchars($r['nama_pelanggan']) ?></td>
                <td>
                    <span style="font-weight:500"><?= htmlspecialchars($r['merk'] ?? '') ?> <?= htmlspecialchars($r['kendaraan'] ?? '') ?></span>
                    <div style="font-size:11px;color:#888"><?= htmlspecialchars($r['plat'] ?? '-') ?> · <?= $r['tahun'] ?? '-' ?></div>
                </td>
                <td style="font-size:12px"><?= htmlspecialchars($r['nama_jasa'] ?? $r['layanan'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['nama_mekanik']) ?></td>
                <td style="font-weight:500;font-size:12px">
                    <?= $r['biaya_jasa'] > 0 ? 'Rp '.number_format($r['biaya_jasa'],0,',','.') : '-' ?>
                </td>
                <td>
                    <span class="badge badge-selesai">✓ Selesai</span>
                    <a href="detail-servis.php?id=<?= $r['id'] ?>" class="btn btn-sm" style="margin-left:4px;background:#f3f4f6" title="Detail">🔍</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_halaman > 1): ?>
    <div style="display:flex;justify-content:center;align-items:center;gap:6px;margin-top:16px;padding-top:12px;border-top:1px solid #f0f0f0">
        
        <?php if ($halaman > 1): ?>
        <a href="?halaman=<?= $halaman - 1 ?>" class="btn btn-sm">← Prev</a>
        <?php endif; ?>

        <?php
        // Tampilkan nomor halaman
        $start = max(1, $halaman - 2);
        $end = min($total_halaman, $halaman + 2);
        
        if ($start > 1): ?>
            <a href="?halaman=1" class="btn btn-sm">1</a>
            <?php if ($start > 2): ?>
            <span style="color:#ccc">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?halaman=<?= $i ?>" class="btn btn-sm <?= $i === $halaman ? 'btn-primary' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($end < $total_halaman): ?>
            <?php if ($end < $total_halaman - 1): ?>
            <span style="color:#ccc">...</span>
            <?php endif; ?>
            <a href="?halaman=<?= $total_halaman ?>" class="btn btn-sm"><?= $total_halaman ?></a>
        <?php endif; ?>

        <?php if ($halaman < $total_halaman): ?>
        <a href="?halaman=<?= $halaman + 1 ?>" class="btn btn-sm">Next →</a>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#888">
        <p style="font-size:48px;margin-bottom:12px">📭</p>
        <p style="font-size:15px">Belum ada riwayat servis yang selesai.</p>
        <p style="font-size:12px;margin-top:4px">Servis akan muncul di sini setelah statusnya diubah menjadi "Selesai".</p>
    </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>