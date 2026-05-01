<?php
require 'koneksi.php';
require 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat'])) {
  $stmt = $pdo->prepare("INSERT INTO invoice (servis_id, biaya_jasa, biaya_part, ppn_persen, ppn_nominal) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([
      $_POST['servis_id'], 
      $_POST['biaya_jasa'], 
      $_POST['biaya_part'],
      $_POST['ppn_persen'] ?? 0,
      $_POST['ppn_nominal'] ?? 0
  ]);
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
  SELECT s.id, p.nama AS nama_pelanggan, s.layanan, mot.plat,
         s.biaya_jasa,
         COALESCE((SELECT SUM(subtotal) FROM servis_part WHERE servis_id = s.id), 0) +
         COALESCE((SELECT SUM(subtotal) FROM sparepart_custom WHERE servis_id = s.id), 0) AS total_part
  FROM servis s
  JOIN pelanggan p ON s.pelanggan_id = p.id
  LEFT JOIN motor mot ON s.motor_id = mot.id
  LEFT JOIN invoice i ON i.servis_id = s.id
  WHERE s.status = 'selesai' AND i.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

$invoice = $pdo->query("
  SELECT i.*, p.nama AS nama_pelanggan, s.layanan, mot.plat,
         (i.biaya_jasa + i.biaya_part + i.ppn_nominal) AS total
  FROM invoice i
  JOIN servis s ON i.servis_id = s.id
  JOIN pelanggan p ON s.pelanggan_id = p.id
  LEFT JOIN motor mot ON s.motor_id = mot.id
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
      <tr><th>No</th><th>Pelanggan</th><th>Layanan</th><th>Jasa</th><th>Part</th><th>PPN</th><th>Total</th><th>Status</th><th>Aksi</th><th>Cetak</th></tr>    </thead>
    <tbody>
      <?php foreach($invoice as $inv): ?>
      <tr>
        <td style="font-weight:500;font-size:12px">INV-<?= str_pad($inv['id'],4,'0',STR_PAD_LEFT) ?></td>
        <td><?= htmlspecialchars($inv['nama_pelanggan']) ?>
          <div style="font-size:11px;color:#888"><?= $inv['plat'] ?? '-' ?></div></td>
        <td><?= htmlspecialchars($inv['layanan'] ?? '-') ?></td>
        <td>Rp <?= number_format($inv['biaya_jasa'],0,',','.') ?></td>
        <td>Rp <?= number_format($inv['biaya_part'],0,',','.') ?></td>
        <td><?= $inv['ppn_nominal'] > 0 ? 'Rp '.number_format($inv['ppn_nominal'],0,',','.').' <small>('.$inv['ppn_persen'].'%)</small>' : '-' ?></td>
        <td style="font-weight:500">Rp <?= number_format($inv['total'],0,',','.') ?></td>
        <td><span class="badge badge-<?= $inv['status']==='lunas'?'lunas':'belum' ?>">
          <?= $inv['status']==='lunas'?'Lunas':'Belum lunas' ?></span></td>
        <td>
          <?php if($inv['status'] !== 'lunas'): ?>
          <a href="?lunas=<?= $inv['id'] ?>" class="btn btn-sm btn-primary">Tandai Lunas</a>
          <?php else: ?>
          <span style="font-size:11px;color:#888">✓</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="cetak_invoice.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-sm" style="background:#f3f4f6">🖨️ Cetak</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($invoice)): ?>
      <tr><td colspan="9" style="text-align:center;color:#888;padding:20px">Belum ada invoice</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);
     z-index:200;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:24px;width:500px;max-width:95vw">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <strong>Buat Invoice</strong>
      <button onclick="document.getElementById('modal').style.display='none'" style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>Pilih Servis (sudah selesai)</label>
        <select name="servis_id" onchange="autoFill(this)" required>
          <option value="">-- Pilih servis --</option>
          <?php foreach($servis_selesai as $s): ?>
          <option value="<?= $s['id'] ?>" data-jasa="<?= $s['biaya_jasa'] ?>" data-part="<?= $s['total_part'] ?>">
            <?= htmlspecialchars($s['nama_pelanggan']) ?> — <?= htmlspecialchars($s['layanan'] ?? 'Servis') ?> (<?= $s['plat'] ?? 'No Plat' ?>)
            | Jasa: Rp <?= number_format($s['biaya_jasa'],0,',','.') ?>
            | Part: Rp <?= number_format($s['total_part'],0,',','.') ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if(empty($servis_selesai)): ?>
        <small style="color:#ef4444">Belum ada servis selesai tanpa invoice.</small>
        <?php endif; ?>
      </div>
      <div class="form-row">
          <div class="form-group"><label>Biaya Jasa (Rp)</label><input type="number" name="biaya_jasa" id="biaya_jasa" value="0" oninput="hitungTotal()" readonly style="background:#f9fafb"></div>
          <div class="form-group"><label>Biaya Suku Cadang (Rp)</label><input type="number" name="biaya_part" id="biaya_part" value="0" oninput="hitungTotal()" readonly style="background:#f9fafb"></div>
      </div>
      <div class="form-row">
          <div class="form-group">
              <label>PPN / Pajak (%) — Opsional</label>
              <input type="number" name="ppn_persen" id="ppn_persen" value="0" min="0" max="100" oninput="hitungTotal()" placeholder="0">
              <small style="color:#888;font-size:11px">Kosongkan jika tidak ada pajak</small>
          </div>
          <div class="form-group">
              <label>Nominal Pajak (Rp)</label>
              <input type="number" name="ppn_nominal" id="ppn_nominal" value="0" readonly style="background:#f9fafb">
          </div>
      </div>
      <div style="background:#f9f9f9;border-radius:8px;padding:12px;margin-bottom:12px">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Jasa</span><span id="show-jasa">Rp 0</span></div>
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Suku Cadang</span><span id="show-part">Rp 0</span></div>
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;color:#d97706"><span>PPN (<span id="show-ppn-pct">0</span>%)</span><span id="show-ppn">Rp 0</span></div>
          <div style="display:flex;justify-content:space-between;font-weight:600;border-top:2px solid #1e3a5f;padding-top:8px;font-size:15px"><span>TOTAL</span><span id="show-total">Rp 0</span></div>
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
  const ppnPct = parseInt(document.getElementById('ppn_persen').value) || 0;
  const ppn = Math.round((j + p) * ppnPct / 100);
  const total = j + p + ppn;
  
  const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
  
  document.getElementById('ppn_nominal').value = ppn;
  document.getElementById('show-jasa').textContent = fmt(j);
  document.getElementById('show-part').textContent = fmt(p);
  document.getElementById('show-ppn-pct').textContent = ppnPct;
  document.getElementById('show-ppn').textContent = fmt(ppn);
  document.getElementById('show-total').textContent = fmt(total);
}

function autoFill(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('biaya_jasa').value = opt.getAttribute('data-jasa') || 0;
  document.getElementById('biaya_part').value = opt.getAttribute('data-part') || 0;
  hitungTotal();
}
</script>

<?php require 'footer.php'; ?>