<?php
require 'koneksi.php';
require 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
  $stmt = $pdo->prepare("INSERT INTO mekanik (nama, kelas, keahlian) VALUES (?, ?, ?)");
  $stmt->execute([$_POST['nama'], $_POST['kelas'], $_POST['keahlian']]);
  header('Location: mekanik.php?pesan=berhasil');
  exit;
}

if (isset($_GET['hapus'])) {
  $pdo->prepare("DELETE FROM mekanik WHERE id=?")->execute([$_GET['hapus']]);
  header('Location: mekanik.php');
  exit;
}

$mekanik = $pdo->query("
  SELECT m.*, COUNT(s.id) AS jumlah_tugas
  FROM mekanik m
  LEFT JOIN servis s ON s.mekanik_id = m.id AND s.status != 'selesai'
  GROUP BY m.id
  ORDER BY m.nama
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1>Mekanik & Tugas</h1>
  <button class="btn btn-primary" onclick="document.getElementById('modal').style.display='flex'">
    + Tambah Mekanik
  </button>
</div>

<?php if(isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
  Mekanik berhasil ditambahkan!
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <?php foreach($mekanik as $m):
    $persen = min($m['jumlah_tugas'] * 33, 100);
  ?>
  <div class="kartu">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <div style="width:42px;height:42px;border-radius:50%;background:#dbeafe;
                  display:flex;align-items:center;justify-content:center;
                  font-weight:500;font-size:14px;color:#1e40af;flex-shrink:0">
        <?= strtoupper(substr($m['nama'],0,1)) . (strpos($m['nama'],' ')!==false ? strtoupper(substr($m['nama'],strpos($m['nama'],' ')+1,1)) : '') ?>
      </div>
      <div>
        <div style="font-weight:500"><?= htmlspecialchars($m['nama']) ?></div>
        <div style="font-size:12px;color:#888"><?= $m['kelas'] ?></div>
      </div>
      <a href="?hapus=<?= $m['id'] ?>" style="margin-left:auto;font-size:12px;color:#ef4444;text-decoration:none"
         onclick="return confirm('Hapus mekanik ini?')">Hapus</a>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:12px;background:#ede9fe;color:#5b21b6;padding:2px 8px;border-radius:20px">
        <?= $m['keahlian'] ?>
      </span>
      <span style="font-size:12px;color:#888"><?= $m['jumlah_tugas'] ?> tugas aktif</span>
    </div>
    <div style="font-size:11px;color:#888;margin-bottom:4px">
      Beban kerja <?= $persen ?>%
    </div>
    <div style="background:#f0f0f0;border-radius:20px;height:6px">
      <div style="height:6px;border-radius:20px;background:#2563eb;width:<?= $persen ?>%"></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(empty($mekanik)): ?>
  <div style="color:#888;padding:20px">Belum ada mekanik terdaftar.</div>
  <?php endif; ?>
</div>

<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
     z-index:99;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:400px;max-width:95vw">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <strong>Tambah Mekanik</strong>
      <button onclick="document.getElementById('modal').style.display='none'"
              style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>
    </div>
    <form method="POST">
      <div class="form-group"><label>Nama Mekanik</label><input name="nama" required></div>
      <div class="form-group"><label>Kelas / NIS</label><input name="kelas" placeholder="XII TKR 1 / 12345"></div>
      <div class="form-group">
        <label>Keahlian</label>
        <select name="keahlian">
          <option>Mesin 4-tak</option>
          <option>Mesin 2-tak</option>
          <option>Sistem Bahan Bakar (Karburator)</option>
          <option>Sistem Bahan Bakar (Injeksi/FI)</option>
          <option>Kelistrikan Motor</option>
          <option>Transmisi & CVT</option>
          <option>Sistem Rem</option>
          <option>Semua Bidang</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="btn" onclick="document.getElementById('modal').style.display='none'">Batal</button>
        <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php require 'footer.php'; ?>