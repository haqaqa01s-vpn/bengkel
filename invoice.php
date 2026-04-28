<?php
require 'koneksi.php';
require 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat'])) {
  $stmt = $pdo->prepare("INSERT INTO invoice (servis_id, biaya_jasa, biaya_part) VALUES (?, ?, ?)");
  $stmt->execute([$_POST['servis_id'], $_POST['biaya_jasa'], $_POST['biaya_part']]);
  header('Location: invoice.php?pesan=berhasil');
  exit;
}

if (isset($_GET['lunas'])) {
  $pdo->prepare("UPDATE invoice SET status='lunas' WHERE id=?")->execute([$_GET['lunas']]);
  header('Location: invoice.php');
  exit;
}

// Servis selesai yang belum punya invoice
$servis_selesai = $pdo->query("
  SELECT s.id, p.nama AS nama_pelanggan, s.layanan, s.plat
  FROM servis s
  JOIN pelanggan p ON s.pelanggan_id = p.id
  LEFT JOIN invoice i ON i.servis_id = s.id
  WHERE s.status = 'selesai' AND i.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

$invoice = $pdo->query("
  SELECT i.*, p.nama AS nama_pelanggan, s.layanan, s.plat,
         (i.biaya_jasa + i.biaya_part) AS total
  FROM invoice i
  JOIN servis s ON i.servis_id = s.id
  JOIN pelanggan p ON s.pelanggan_id = p.id
  ORDER BY i.dibuat DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
  <h1>Invoice & Pembayaran</h1>
  <button class="btn btn-primary" onclick="document.getElementById('modal').style.display='flex'">
    + Buat Invoice
  </button>
</div>

<?php if(isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
  Invoice berhasil dibuat!
</div>
<?php endif; ?>

<div class="kartu">
  <table>
    <thead>
      <tr><th>No Invoice</th><th>Pelanggan</th><th>Layanan</th><th>Biaya Jasa</th><th>Suku Cadang</th><th>Total</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach($invoice as $inv): ?>
      <tr>
        <td style="font-weight:500;font-size:12px">INV-<?= str_pad($inv['id'],4,'0',STR_PAD_LEFT) ?></td>
        <td><?= htmlspecialchars($inv['nama_pelanggan']) ?>
          <div style="font-size:11px;color:#888"><?= $inv['plat'] ?></div></td>
        <td><?= $inv['layanan'] ?></td>
        <td>Rp <?= number_format($inv['biaya_jasa'],0,',','.') ?></td>
        <td>Rp <?= number_format($inv['biaya_part'],0,',','.') ?></td>
        <td style="font-weight:500">Rp <?= number_format($inv['total'],0,',','.') ?></td>
        <td><span class="badge badge-<?= $inv['status']==='lunas'?'lunas':'belum' ?>">
          <?= $inv['status']==='lunas'?'Lunas':'Belum lunas' ?></span></td>
        <td>
          <?php if($inv['status'] !== 'lunas'): ?>
          <a href="?lunas=<?= $inv['id'] ?>" class="btn btn-sm btn-primary">Tandai Lunas</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($invoice)): ?>
      <tr><td colspan="8" style="text-align:center;color:#888;padding:20px">Belum ada invoice</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
     z-index:99;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:460px;max-width:95vw">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <strong>Buat Invoice</strong>
      <button onclick="document.getElementById('modal').style.display='none'"
              style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>Pilih Servis (sudah selesai)</label>
        <select name="servis_id" required>
          <option value="">-- Pilih servis --</option>
          <?php foreach($servis_selesai as $s): ?>
          <option value="<?= $s['id'] ?>">
            <?= htmlspecialchars($s['nama_pelanggan']) ?> — <?= $s['layanan'] ?> (<?= $s['plat'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
        <?php if(empty($servis_selesai)): ?>
        <small style="color:#ef4444">Belum ada servis yang selesai tanpa invoice.</small>
        <?php endif; ?>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Biaya Jasa (Rp)</label>
          <input type="number" name="biaya_jasa" id="biaya_jasa" value="0" oninput="hitungTotal()">
        </div>
        <div class="form-group">
          <label>Biaya Suku Cadang (Rp)</label>
          <input type="number" name="biaya_part" id="biaya_part" value="0" oninput="hitungTotal()">
        </div>
      </div>
      <div style="background:#f9f9f9;border-radius:8px;padding:12px;margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
          <span>Jasa</span><span id="show-jasa">Rp 0</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px">
          <span>Suku Cadang</span><span id="show-part">Rp 0</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:500;
                    border-top:1px solid #e5e5e5;padding-top:8px">
          <span>Total</span><span id="show-total">Rp 0</span>
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end">
        <button type="button" class="btn" onclick="document.getElementById('modal').style.display='none'">Batal</button>
        <button type="submit" name="buat" class="btn btn-primary">Simpan Invoice</button>
      </div>
    </form>
  </div>
</div>

<script>
function hitungTotal() {
  const j = parseInt(document.getElementById('biaya_jasa').value) || 0;
  const p = parseInt(document.getElementById('biaya_part').value) || 0;
  const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
  document.getElementById('show-jasa').textContent  = fmt(j);
  document.getElementById('show-part').textContent  = fmt(p);
  document.getElementById('show-total').textContent = fmt(j + p);
}
</script>

<?php require 'footer.php'; ?>