<?php
require 'koneksi.php';
require 'header.php';

// Proses tambah servis baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
  // Cek / tambah pelanggan
  $cek = $pdo->prepare("SELECT id FROM pelanggan WHERE nama=? AND telepon=?");
  $cek->execute([$_POST['nama'], $_POST['telepon']]);
  $pel = $cek->fetch();

  if ($pel) {
    $pel_id = $pel['id'];
  } else {
    $ins = $pdo->prepare("INSERT INTO pelanggan (nama, telepon, tipe, kelas) VALUES (?,?,?,?)");
    $ins->execute([
      $_POST['nama'],
      $_POST['telepon'],
      $_POST['tipe_pelanggan'],
      $_POST['kelas'] ?? null
    ]);
    $pel_id = $pdo->lastInsertId();
  }

  $stmt = $pdo->prepare("INSERT INTO servis
    (pelanggan_id, mekanik_id, kendaraan, plat, merk, tahun, no_rangka, no_mesin, keluhan, layanan, tipe_pelanggan)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)");
  $stmt->execute([
    $pel_id,
    $_POST['mekanik_id'],
    $_POST['tipe_motor'],
    strtoupper($_POST['plat']),
    $_POST['merk'],
    $_POST['tahun'] ?: null,
    $_POST['no_rangka'] ?: null,
    $_POST['no_mesin'] ?: null,
    $_POST['keluhan'],
    $_POST['layanan'],
    $_POST['tipe_pelanggan']
  ]);
  header('Location: antrian.php?pesan=berhasil');
  exit;
}

// Proses ubah status
if (isset($_GET['ubah_status'])) {
  $stmt = $pdo->prepare("UPDATE servis SET status=? WHERE id=?");
  $stmt->execute([$_GET['status'], $_GET['ubah_status']]);
  header('Location: antrian.php');
  exit;
}

// Ambil data
$antrian  = $pdo->query("
  SELECT s.*, p.nama AS nama_pelanggan, m.nama AS nama_mekanik
  FROM servis s
  JOIN pelanggan p ON s.pelanggan_id = p.id
  JOIN mekanik m ON s.mekanik_id = m.id
  ORDER BY s.dibuat DESC
")->fetchAll(PDO::FETCH_ASSOC);

$mekanik = $pdo->query("SELECT id, nama FROM mekanik ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1>Antrian Servis</h1>
  <button class="btn btn-primary" onclick="document.getElementById('modal').style.display='flex'">
    + Daftar Servis Baru
  </button>
</div>

<?php if(isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
  Servis berhasil didaftarkan!
</div>
<?php endif; ?>

<div class="kartu">
  <table>
    <thead>
      <tr><th>Pelanggan</th><th>Kendaraan</th><th>Keluhan</th><th>Mekanik</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach($antrian as $a): ?>
      <tr>
        <td><?= htmlspecialchars($a['nama_pelanggan']) ?></td>
        <td>
          <span style="font-weight:500"><?= $a['merk'] ?> <?= $a['kendaraan'] ?></span>
          <div style="font-size:11px;color:#888"><?= $a['plat'] ?> · <?= $a['tahun'] ?></div>
        </td>
        <td style="max-width:180px;font-size:12px"><?= htmlspecialchars($a['keluhan']) ?></td>
        <td><?= htmlspecialchars($a['nama_mekanik']) ?></td>
        <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
        <td>
          <?php if($a['status'] === 'menunggu'): ?>
            <a href="?ubah_status=<?= $a['id'] ?>&status=proses" class="btn btn-sm btn-primary">Mulai</a>
          <?php elseif($a['status'] === 'proses'): ?>
            <a href="?ubah_status=<?= $a['id'] ?>&status=selesai" class="btn btn-sm">Selesai</a>
          <?php else: ?>
            <span style="font-size:11px;color:#888">✓ Done</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal form -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
     z-index:99;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:480px;max-width:95vw">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <strong>Daftar Servis Baru</strong>
      <button onclick="document.getElementById('modal').style.display='none'"
              style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>
    </div>
    <form method="POST">
      <p style="font-size:12px;font-weight:500;color:#555;margin-bottom:10px">Data Pelanggan</p>
      <div class="form-row">
        <div class="form-group"><label>Nama Pelanggan</label><input name="nama" required></div>
        <div class="form-group"><label>No. Telepon</label><input name="telepon"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tipe Pelanggan</label>
          <select name="tipe_pelanggan" id="sel-tipe" onchange="toggleKelas()">
            <option value="umum">Umum</option>
            <option value="siswa">Siswa</option>
          </select>
        </div>
        <div class="form-group" id="grup-kelas" style="display:none">
          <label>Kelas</label>
          <input name="kelas" placeholder="XI TSM 1">
        </div>
      </div>

      <p style="font-size:12px;font-weight:500;color:#555;margin:14px 0 10px">Data Kendaraan</p>
      <div class="form-row">
        <div class="form-group">
          <label>Merk Motor</label>
          <select name="merk">
            <option>Honda</option><option>Yamaha</option><option>Suzuki</option>
            <option>Kawasaki</option><option>TVS</option><option>Lainnya</option>
          </select>
        </div>
        <div class="form-group"><label>Tipe / Model</label><input name="tipe_motor" placeholder="Vario 125, Mio M3..."></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Tahun</label><input name="tahun" type="number" min="1990" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>"></div>
        <div class="form-group"><label>Plat Nomor</label><input name="plat" required placeholder="BP 1234 AB"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>No. Rangka</label><input name="no_rangka" placeholder="MH1JF..."></div>
        <div class="form-group"><label>No. Mesin</label><input name="no_mesin" placeholder="JF50E..."></div>
      </div>

      <p style="font-size:12px;font-weight:500;color:#555;margin:14px 0 10px">Data Servis</p>
      <div class="form-group">
        <label>Keluhan</label>
        <textarea name="keluhan" rows="2" placeholder="Mesin kasar, susah starter, rem blong..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Jenis Servis</label>
          <select name="layanan">
            <option>Tune Up</option>
            <option>Ganti Oli Mesin</option>
            <option>Ganti Oli Gardan</option>
            <option>Servis Karburator / Injeksi</option>
            <option>Servis Rem</option>
            <option>Servis Kelistrikan</option>
            <option>Ganti Kampas Rem</option>
            <option>Ganti Ban</option>
            <option>Servis CVT / Transmisi</option>
            <option>Overhaul Mesin</option>
            <option>Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label>Mekanik</label>
          <select name="mekanik_id" required>
            <option value="">-- Pilih mekanik --</option>
            <?php foreach($mekanik as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
        <button type="button" class="btn"
          onclick="document.getElementById('modal').style.display='none'">Batal</button>
        <button type="submit" name="tambah" class="btn btn-primary">Daftar Sekarang</button>
      </div>
    </form>

    <script>
    function toggleKelas() {
      const tipe = document.getElementById('sel-tipe').value;
      document.getElementById('grup-kelas').style.display = tipe === 'siswa' ? 'block' : 'none';
    }
    </script>
  </div>
</div>

<?php require 'footer.php'; ?>