<?php
require 'koneksi.php';
cekRole('admin');  // <-- tambahin ini
require 'header.php';

// Tambah jasa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $stmt = $pdo->prepare("INSERT INTO jasa_servis (kode, nama_jasa, kategori, harga, deskripsi) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        strtoupper($_POST['kode']),
        $_POST['nama_jasa'],
        $_POST['kategori'],
        $_POST['harga'],
        $_POST['deskripsi']
    ]);
    header('Location: jasa.php?pesan=berhasil');
    exit;
}

// Update jasa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE jasa_servis SET kode=?, nama_jasa=?, kategori=?, harga=?, deskripsi=? WHERE id=?");
    $stmt->execute([
        strtoupper($_POST['kode']),
        $_POST['nama_jasa'],
        $_POST['kategori'],
        $_POST['harga'],
        $_POST['deskripsi'],
        $_POST['id']
    ]);
    header('Location: jasa.php?pesan=update');
    exit;
}

// Hapus jasa
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM jasa_servis WHERE id=?")->execute([$_GET['hapus']]);
    header('Location: jasa.php?pesan=hapus');
    exit;
}

// Ambil data untuk edit
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM jasa_servis WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Ambil semua data
$jasa = $pdo->query("SELECT * FROM jasa_servis ORDER BY FIELD(kategori, 'ringan','sedang','berat'), kode")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1>Master Jasa Servis</h1>
    <button class="btn btn-primary" onclick="bukaModal()">
        + Tambah Jasa
    </button>
</div>

<?php if (isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    <?php
    $pesan = ['berhasil'=>'Jasa berhasil ditambahkan!', 'update'=>'Jasa berhasil diupdate!', 'hapus'=>'Jasa berhasil dihapus!'];
    echo $pesan[$_GET['pesan']] ?? 'Operasi berhasil!';
    ?>
</div>
<?php endif; ?>

<div class="kartu">
    <table>
        <thead>
            <tr><th>Kode</th><th>Nama Jasa</th><th>Kategori</th><th>Harga</th><th>Deskripsi</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($jasa as $j): ?>
            <tr>
                <td style="font-weight:500;font-size:12px"><?= $j['kode'] ?></td>
                <td><?= htmlspecialchars($j['nama_jasa']) ?></td>
                <td>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;
                        background:<?= $j['kategori']=='ringan' ? '#dbeafe' : ($j['kategori']=='sedang' ? '#fef3c7' : '#fce7f3') ?>;
                        color:<?= $j['kategori']=='ringan' ? '#1e40af' : ($j['kategori']=='sedang' ? '#92400e' : '#9d174d') ?>">
                        <?= ucfirst($j['kategori']) ?>
                    </span>
                </td>
                <td style="font-weight:500">Rp <?= number_format($j['harga'], 0, ',', '.') ?></td>
                <td style="font-size:12px;max-width:200px"><?= htmlspecialchars($j['deskripsi']) ?></td>
                <td>
                    <a href="?edit=<?= $j['id'] ?>" class="btn btn-sm" onclick="bukaModal()" style="background:#f0f4ff;color:#2563eb">Edit</a>
                    <a href="?hapus=<?= $j['id'] ?>" class="btn btn-sm" style="background:#fef2f2;color:#dc2626"
                       onclick="return confirm('Hapus <?= htmlspecialchars($j['nama_jasa']) ?>?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($jasa)): ?>
            <tr><td colspan="6" style="text-align:center;color:#888;padding:20px">Belum ada data jasa servis</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
     z-index:99;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;width:500px;max-width:95vw">
        <div style="display:flex;justify-content:space-between;margin-bottom:16px">
            <strong><?= $edit ? 'Edit Jasa' : 'Tambah Jasa Servis' ?></strong>
            <button onclick="tutupModal()" style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>
        </div>
        <form method="POST">
            <?php if($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Kode Jasa</label>
                <input name="kode" value="<?= $edit['kode'] ?? '' ?>" oninput="this.value = this.value.toUpperCase()" placeholder="JS-R01" required>
            </div>
            <div class="form-group">
                <label>Nama Jasa</label>
                <input name="nama_jasa" value="<?= $edit['nama_jasa'] ?? '' ?>" placeholder="Servis Ringan - Ganti Oli" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" required>
                        <option value="ringan" <?= ($edit['kategori']??'')=='ringan'? 'selected':'' ?>>Ringan (Rp 50rb-75rb)</option>
                        <option value="sedang" <?= ($edit['kategori']??'')=='sedang'? 'selected':'' ?>>Sedang (Rp 80rb-150rb)</option>
                        <option value="berat" <?= ($edit['kategori']??'')=='berat'? 'selected':'' ?>>Berat (Rp 200rb-350rb)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Harga Jasa (Rp)</label>
                    <input type="number" name="harga" value="<?= $edit['harga'] ?? '' ?>" placeholder="55000" required>
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="2" placeholder="Detail pekerjaan yang dilakukan..."><?= $edit['deskripsi'] ?? '' ?></textarea>
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
// Buka modal otomatis saat mode edit
document.addEventListener('DOMContentLoaded', bukaModal);
<?php endif; ?>
</script>

<?php require 'footer.php'; ?>