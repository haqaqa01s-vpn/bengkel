<?php
require 'koneksi.php';
cekRole('admin');  // <-- tambahin ini
require 'header.php';

// Tambah part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $stmt = $pdo->prepare("INSERT INTO suku_cadang (kode_part, nama_part, kategori, merk, stok, harga_beli, harga_jual) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        strtoupper($_POST['kode_part']),
        $_POST['nama_part'],
        $_POST['kategori'],
        $_POST['merk'],
        $_POST['stok'],
        $_POST['harga_beli'] ?: 0,
        $_POST['harga_jual']
    ]);
    header('Location: sparepart.php?pesan=berhasil');
    exit;
}

// Update part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE suku_cadang SET kode_part=?, nama_part=?, kategori=?, merk=?, stok=?, harga_beli=?, harga_jual=? WHERE id=?");
    $stmt->execute([
        strtoupper($_POST['kode_part']),
        $_POST['nama_part'],
        $_POST['kategori'],
        $_POST['merk'],
        $_POST['stok'],
        $_POST['harga_beli'] ?: 0,
        $_POST['harga_jual'],
        $_POST['id']
    ]);
    header('Location: sparepart.php?pesan=update');
    exit;
}

// Hapus part
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM suku_cadang WHERE id=?")->execute([$_GET['hapus']]);
    header('Location: sparepart.php?pesan=hapus');
    exit;
}

// Ambil data untuk edit
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM suku_cadang WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Filter kategori
$filter_kategori = $_GET['filter'] ?? '';
$where = $filter_kategori ? "WHERE kategori = :kategori" : "";
$params = $filter_kategori ? [':kategori' => $filter_kategori] : [];

$stmt = $pdo->prepare("SELECT * FROM suku_cadang $where ORDER BY kategori, kode_part");
$stmt->execute($params);
$parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daftar kategori untuk filter
$kategori_list = $pdo->query("SELECT DISTINCT kategori FROM suku_cadang ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
    <h1>Master Suku Cadang</h1>
    <button class="btn btn-primary" onclick="bukaModal()">
        + Tambah Suku Cadang
    </button>
</div>

<?php if (isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    <?php
    $pesan = ['berhasil'=>'Suku cadang berhasil ditambahkan!', 'update'=>'Suku cadang berhasil diupdate!', 'hapus'=>'Suku cadang berhasil dihapus!'];
    echo $pesan[$_GET['pesan']] ?? 'Operasi berhasil!';
    ?>
</div>
<?php endif; ?>

<!-- Filter -->
<div style="display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
    <span style="font-size:13px;color:#888">Filter:</span>
    <a href="sparepart.php" class="btn btn-sm <?= !$filter_kategori ? 'btn-primary' : '' ?>">Semua</a>
    <?php foreach($kategori_list as $k): ?>
    <a href="?filter=<?= urlencode($k) ?>" class="btn btn-sm <?= $filter_kategori==$k ? 'btn-primary' : '' ?>">
        <?= htmlspecialchars($k) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="kartu">
    <table>
        <thead>
            <tr><th>Kode</th><th>Nama Part</th><th>Kategori</th><th>Merk</th><th>Stok</th><th>Harga Jual</th><th>Margin</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($parts as $p): 
                $margin = $p['harga_beli'] > 0 ? round((($p['harga_jual'] - $p['harga_beli']) / $p['harga_beli']) * 100) : 0;
            ?>
            <tr>
                <td style="font-weight:500;font-size:12px"><?= $p['kode_part'] ?></td>
                <td><?= htmlspecialchars($p['nama_part']) ?></td>
                <td><span style="background:#f3f4f6;padding:2px 8px;border-radius:20px;font-size:11px"><?= $p['kategori'] ?></span></td>
                <td><?= $p['merk'] ?></td>
                <td>
                    <?php if($p['stok'] <= 5): ?>
                        <span style="color:#ef4444;font-weight:600"><?= $p['stok'] ?></span>
                    <?php elseif($p['stok'] <= 15): ?>
                        <span style="color:#d97706;font-weight:500"><?= $p['stok'] ?></span>
                    <?php else: ?>
                        <span style="color:#059669"><?= $p['stok'] ?></span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:500">Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></td>
                <td style="font-size:12px;color:#6b7280"><?= $p['harga_beli'] > 0 ? $margin . '%' : '-' ?></td>
                <td>
                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm" onclick="bukaModal()" style="background:#f0f4ff;color:#2563eb">Edit</a>
                    <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm" style="background:#fef2f2;color:#dc2626"
                       onclick="return confirm('Hapus <?= htmlspecialchars($p['nama_part']) ?>?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($parts)): ?>
            <tr><td colspan="8" style="text-align:center;color:#888;padding:20px">Belum ada data suku cadang</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
     z-index:99;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;width:520px;max-width:95vw">
        <div style="display:flex;justify-content:space-between;margin-bottom:16px">
            <strong><?= $edit ? 'Edit Suku Cadang' : 'Tambah Suku Cadang' ?></strong>
            <button onclick="tutupModal()" style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>
        </div>
        <form method="POST">
            <?php if($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Kode Part</label>
                    <input name="kode_part" value="<?= $edit['kode_part'] ?? '' ?>" placeholder="OLI-MPX2" required>
                </div>
                <div class="form-group">
                    <label>Merk</label>
                    <input name="merk" value="<?= $edit['merk'] ?? '' ?>" placeholder="AHM / NGK / Denso">
                </div>
            </div>
            <div class="form-group">
                <label>Nama Part</label>
                <input name="nama_part" value="<?= $edit['nama_part'] ?? '' ?>" placeholder="Oli AHM MPX-2 0.8L" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kategori</label>
                    <input name="kategori" value="<?= $edit['kategori'] ?? '' ?>" list="list-kategori" placeholder="Oli Mesin">
                    <datalist id="list-kategori">
                        <?php foreach($kategori_list as $k): ?>
                        <option value="<?= htmlspecialchars($k) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" value="<?= $edit['stok'] ?? '0' ?>" min="0" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" value="<?= $edit['harga_beli'] ?? '' ?>" placeholder="42000">
                </div>
                <div class="form-group">
                    <label>Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" value="<?= $edit['harga_jual'] ?? '' ?>" placeholder="55000" required>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
                <button type="button" class="btn" onclick="tutupModal()">Batal</button>
                <button type="submit" name="<?= $edit ? 'update' : 'tambah' ?>" class="btn btn-primary">
                    <?= $edit ? 'Simpan Perubahan' : 'Simpan' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal() { document.getElementById('modal').style.display = 'flex'; }
function tutupModal() { document.getElementById('modal').style.display = 'none'; }
<?php if($edit): ?>
document.addEventListener('DOMContentLoaded', bukaModal);
<?php endif; ?>
</script>

<?php require 'footer.php'; ?>