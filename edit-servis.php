<?php
require 'koneksi.php';
require 'header-minimal.php';

$id = $_GET['id'] ?? 0;

// Ambil data servis
$stmt = $pdo->prepare("
    SELECT s.*, p.nama AS nama_pelanggan, p.telepon, p.alamat, p.tipe, p.kelas,
           mot.plat, mot.merk, mot.tipe AS tipe_motor, mot.tahun, mot.no_rangka, mot.no_mesin
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$servis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servis) die("Servis tidak ditemukan.");
if (!in_array($servis['status'], ['menunggu', 'proses'])) {
    header('Location: antrian.php');
    exit;
}

// Ambil sparepart yang sudah terpakai
$part_terpakai = $pdo->prepare("
    SELECT sp.*, sc.kode_part, sc.nama_part, sc.harga_jual
    FROM servis_part sp 
    JOIN suku_cadang sc ON sp.part_id = sc.id 
    WHERE sp.servis_id = ?
");
$part_terpakai->execute([$id]);
$part_terpakai = $part_terpakai->fetchAll(PDO::FETCH_ASSOC);

// Ambil sparepart custom yang sudah diinput
$custom_terpakai = $pdo->prepare("SELECT * FROM sparepart_custom WHERE servis_id = ?");
$custom_terpakai->execute([$id]);
$custom_terpakai = $custom_terpakai->fetchAll(PDO::FETCH_ASSOC);

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    try {
        $pdo->beginTransaction();

        // Update pelanggan
        $pdo->prepare("UPDATE pelanggan SET telepon=?, alamat=?, tipe=?, kelas=? WHERE id=?")
            ->execute([$_POST['telepon'], $_POST['alamat'] ?? '', $_POST['tipe_pelanggan'], $_POST['kelas'] ?? null, $servis['pelanggan_id']]);

        // Update motor
        $pdo->prepare("UPDATE motor SET plat=?, merk=?, tipe=?, tahun=?, no_rangka=?, no_mesin=? WHERE id=?")
            ->execute([
                strtoupper($_POST['plat']),
                $_POST['merk'],
                $_POST['tipe_motor'],
                $_POST['tahun'] ?: null,
                $_POST['no_rangka'] ?: null,
                $_POST['no_mesin'] ?: null,
                $servis['motor_id']
            ]);

        // Logika jasa
        $sumber_jasa = $_POST['sumber_jasa'] ?? 'template';
        $jasa_id = !empty($_POST['jasa_id']) ? $_POST['jasa_id'] : null;
        $harga_jasa = 0;

        if ($sumber_jasa === 'custom') {
            $harga_jasa = intval($_POST['harga_jasa_custom'] ?? 0);
            $jasa_id = null;
        } elseif (!empty($jasa_id)) {
            $j = $pdo->prepare("SELECT harga FROM jasa_servis WHERE id=?");
            $j->execute([$jasa_id]);
            $harga_jasa = $j->fetchColumn();
        }

        $km_sekarang = !empty($_POST['km_sekarang']) ? intval($_POST['km_sekarang']) : null;
        $km_selanjutnya = $km_sekarang ? $km_sekarang + 3000 : null;

        // Update servis
        $pdo->prepare("UPDATE servis SET 
            km_sekarang=?, km_servis_selanjutnya=?, keluhan=?, layanan=?,
            jasa_id=?, kategori_servis=?, deskripsi_jasa=?, harga_jasa_custom=?, biaya_jasa=?,
            mekanik_id=?
            WHERE id=?")
            ->execute([
                $km_sekarang, $km_selanjutnya,
                $_POST['keluhan'], $_POST['layanan'],
                $jasa_id, $_POST['kategori_servis'],
                $_POST['deskripsi_jasa'] ?? null,
                $sumber_jasa === 'custom' ? $harga_jasa : 0,
                $harga_jasa,
                $_POST['mekanik_id'],
                $id
            ]);

        // Kembalikan stok sparepart lama
        foreach ($part_terpakai as $pt) {
            $pdo->prepare("UPDATE suku_cadang SET stok = stok + ? WHERE id = ?")->execute([$pt['jumlah'], $pt['part_id']]);
        }

        // Hapus sparepart lama
        $pdo->prepare("DELETE FROM servis_part WHERE servis_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM sparepart_custom WHERE servis_id = ?")->execute([$id]);

        // Insert ulang sparepart master
        if (!empty($_POST['part_id']) && is_array($_POST['part_id'])) {
            $stmt_part = $pdo->prepare("INSERT INTO servis_part (servis_id, part_id, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?)");
            $stmt_stok = $pdo->prepare("UPDATE suku_cadang SET stok = stok - ? WHERE id = ?");
            $stmt_harga = $pdo->prepare("SELECT harga_jual FROM suku_cadang WHERE id=?");

            foreach ($_POST['part_id'] as $i => $part_id) {
                if (empty($part_id)) continue;
                $jumlah = max(1, intval($_POST['part_jumlah'][$i] ?? 1));
                $stmt_harga->execute([$part_id]);
                $harga = $stmt_harga->fetchColumn();
                if ($harga) {
                    $stmt_part->execute([$id, $part_id, $jumlah, $harga, $harga * $jumlah]);
                    $stmt_stok->execute([$jumlah, $part_id]);
                }
            }
        }

        // Insert ulang sparepart custom
        if (!empty($_POST['custom_nama']) && is_array($_POST['custom_nama'])) {
            $stmt_custom = $pdo->prepare("INSERT INTO sparepart_custom (servis_id, nama_part, jumlah, harga_beli, harga_jual, subtotal) VALUES (?,?,?,?,?,?)");
            foreach ($_POST['custom_nama'] as $i => $nama) {
                if (empty(trim($nama))) continue;
                $jumlah = max(1, intval($_POST['custom_jumlah'][$i] ?? 1));
                $harga_beli = intval($_POST['custom_harga_beli'][$i] ?? 0);
                $harga_jual = intval($_POST['custom_harga_jual'][$i] ?? 0);
                $stmt_custom->execute([$id, trim($nama), $jumlah, $harga_beli, $harga_jual, $harga_jual * $jumlah]);
            }
        }

        $pdo->commit();
        header('Location: antrian.php?pesan=update');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal: " . $e->getMessage();
    }
}

// Data dropdown
$mekanik = $pdo->query("SELECT id, nama FROM mekanik ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$jasa_list = $pdo->query("SELECT * FROM jasa_servis ORDER BY kategori, nama_jasa")->fetchAll(PDO::FETCH_ASSOC);
$part_list = $pdo->query("SELECT * FROM suku_cadang ORDER BY kategori, nama_part")->fetchAll(PDO::FETCH_ASSOC);

$jasa_grouped = [];
foreach ($jasa_list as $j) {
    $jasa_grouped[$j['kategori']][] = $j;
}

$part_grouped = [];
foreach ($part_list as $p) {
    $part_grouped[$p['kategori']][] = [
        'id' => $p['id'],
        'kode_part' => $p['kode_part'],
        'nama_part' => $p['nama_part'],
        'harga_jual' => $p['harga_jual']
    ];
}
?>

<div class="page-header">
    <h1>✏️ Edit Servis #<?= $id ?></h1>
    <span style="font-size:12px;color:#888">Status: <?= ucfirst($servis['status']) ?></span>
</div>

<?php if (isset($error)): ?>
<div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:16px"><?= $error ?></div>
<?php endif; ?>

<div class="kartu">
    <form method="POST">
        <input type="hidden" name="sumber_jasa" id="input-sumber-jasa" value="<?= $servis['jasa_id'] ? 'template' : ($servis['harga_jasa_custom'] > 0 ? 'custom' : 'template') ?>">

        <!-- Data Pelanggan -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin-bottom:12px;text-transform:uppercase;letter-spacing:1px">👤 Data Pelanggan</h3>
        <div class="form-row">
            <div class="form-group"><label>Nama</label><input value="<?= htmlspecialchars($servis['nama_pelanggan']) ?>" disabled style="background:#f9fafb"></div>
            <div class="form-group"><label>No. Telepon</label><input name="telepon" value="<?= htmlspecialchars($servis['telepon']) ?>"></div>
        </div>
        <div class="form-group"><label>Alamat</label><textarea name="alamat" rows="2"><?= htmlspecialchars($servis['alamat']) ?></textarea></div>
        <div class="form-row">
            <div class="form-group">
                <label>Tipe Pelanggan</label>
                <select name="tipe_pelanggan" id="sel-tipe" onchange="toggleKelas()">
                    <option value="umum" <?= $servis['tipe']=='umum'?'selected':'' ?>>Umum</option>
                    <option value="siswa" <?= $servis['tipe']=='siswa'?'selected':'' ?>>Siswa</option>
                </select>
            </div>
            <div class="form-group" id="grup-kelas" style="<?= $servis['tipe']=='siswa'?'':'display:none' ?>">
                <label>Kelas</label>
                <input name="kelas" value="<?= htmlspecialchars($servis['kelas']) ?>" placeholder="XI TSM 1">
            </div>
        </div>

        <!-- Data Motor -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin:24px 0 12px;text-transform:uppercase;letter-spacing:1px">🏍️ Data Kendaraan</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Merk Motor</label>
                <select name="merk">
                    <?php foreach(['Honda','Yamaha','Suzuki','Kawasaki','TVS','Lainnya'] as $m): ?>
                    <option <?= ($servis['merk']??'')==$m?'selected':'' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Tipe / Model</label><input name="tipe_motor" value="<?= htmlspecialchars($servis['tipe_motor'] ?? '') ?>" placeholder="Vario 125, Mio M3..."></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Tahun</label><input name="tahun" type="number" value="<?= $servis['tahun'] ?? '' ?>" min="1990" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>"></div>
            <div class="form-group"><label>Plat Nomor</label><input name="plat" value="<?= htmlspecialchars($servis['plat'] ?? '') ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kilometer Saat Ini (KM)</label>
                <input name="km_sekarang" type="number" value="<?= $servis['km_sekarang'] ?>" min="0">
                <small style="color:#888;font-size:11px">Servis selanjutnya +3.000 KM = <?= $servis['km_servis_selanjutnya'] ? number_format($servis['km_servis_selanjutnya'],0,',','.') : '?' ?> KM</small>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>No. Rangka</label><input name="no_rangka" value="<?= htmlspecialchars($servis['no_rangka'] ?? '') ?>" placeholder="MH1JF..."></div>
            <div class="form-group"><label>No. Mesin</label><input name="no_mesin" value="<?= htmlspecialchars($servis['no_mesin'] ?? '') ?>" placeholder="JF50E..."></div>
        </div>

        <!-- Data Servis -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin:24px 0 12px;text-transform:uppercase;letter-spacing:1px">🔧 Data Servis</h3>
        <div class="form-group">
            <label>Keluhan</label>
            <textarea name="keluhan" rows="2"><?= htmlspecialchars($servis['keluhan']) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kategori Servis</label>
                <select name="kategori_servis" id="kategori-servis" onchange="toggleJasa()" required>
                    <option value="">-- Pilih --</option>
                    <option value="ringan" <?= $servis['kategori_servis']=='ringan'?'selected':'' ?>>🔵 Ringan</option>
                    <option value="sedang" <?= $servis['kategori_servis']=='sedang'?'selected':'' ?>>🟡 Sedang</option>
                    <option value="berat" <?= $servis['kategori_servis']=='berat'?'selected':'' ?>>🔴 Berat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sumber Jasa</label>
                <select id="sumber-jasa" onchange="toggleSumberJasa()">
                    <option value="template" <?= $servis['jasa_id']?'selected':'' ?>>📋 Dari Master Data</option>
                    <option value="custom" <?= $servis['harga_jasa_custom']>0?'selected':'' ?>>✏️ Custom</option>
                </select>
            </div>
        </div>

        <div id="jasa-template" style="<?= $servis['harga_jasa_custom']>0?'display:none':'' ?>">
            <div class="form-row">
                <div class="form-group"><label>Jenis Layanan</label><input name="layanan" value="<?= htmlspecialchars($servis['layanan']) ?>"></div>
                <div class="form-group">
                    <label>Paket Jasa (Master)</label>
                    <select name="jasa_id" id="sel-jasa" onchange="updateHarga()">
                        <option value="">-- Pilih --</option>
                        <optgroup label="🔵 Ringan" id="opt-ringan"></optgroup>
                        <optgroup label="🟡 Sedang" id="opt-sedang"></optgroup>
                        <optgroup label="🔴 Berat" id="opt-berat"></optgroup>
                    </select>
                    <small id="harga-jasa-txt" style="color:#2563eb;display:block;margin-top:4px"></small>
                </div>
            </div>
        </div>

        <div id="jasa-custom" style="<?= $servis['harga_jasa_custom']>0?'':'display:none' ?>">
            <div class="form-row">
                <div class="form-group"><label>Deskripsi Jasa</label><input name="deskripsi_jasa" value="<?= htmlspecialchars($servis['deskripsi_jasa'] ?? '') ?>"></div>
                <div class="form-group"><label>Harga Jasa (Rp)</label><input type="number" name="harga_jasa_custom" value="<?= $servis['harga_jasa_custom'] ?>" min="0"></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Mekanik</label>
                <select name="mekanik_id" required>
                    <?php foreach($mekanik as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $servis['mekanik_id']==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Sparepart Master -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin:24px 0 4px;text-transform:uppercase;letter-spacing:1px">📦 Suku Cadang (Master)</h3>
        <div id="part-list">
            <?php if (!empty($part_terpakai)): ?>
                <?php foreach ($part_terpakai as $pt): ?>
                <div class="part-row" style="display:flex;gap:8px;align-items:flex-end;margin-bottom:8px">
                    <div class="form-group" style="flex:1;margin:0">
                        <select name="part_id[]">
                            <option value="">-- Cari suku cadang... --</option>
                            <?php foreach($part_grouped as $kat => $parts): ?>
                            <optgroup label="<?= $kat ?>">
                                <?php foreach($parts as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $pt['part_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= $p['kode_part'] ?> — <?= htmlspecialchars($p['nama_part']) ?> (Rp <?= number_format($p['harga_jual'],0,',','.') ?>)
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="width:70px;margin:0">
                        <input type="number" name="part_jumlah[]" value="<?= $pt['jumlah'] ?>" min="1">
                    </div>
                    <button type="button" onclick="hapusPart(this)" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;font-size:14px">✕</button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="part-row" style="display:flex;gap:8px;align-items:flex-end;margin-bottom:8px">
                <div class="form-group" style="flex:1;margin:0">
                    <select name="part_id[]">
                        <option value="">-- Cari suku cadang... --</option>
                        <?php foreach($part_grouped as $kat => $parts): ?>
                        <optgroup label="<?= $kat ?>">
                            <?php foreach($parts as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['kode_part'] ?> — <?= htmlspecialchars($p['nama_part']) ?> (Rp <?= number_format($p['harga_jual'],0,',','.') ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="width:70px;margin:0"><input type="number" name="part_jumlah[]" value="1" min="1"></div>
                <button type="button" onclick="hapusPart(this)" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;font-size:14px">✕</button>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="tambahPart()" class="btn btn-sm" style="margin-top:8px">+ Tambah Part Master</button>

        <!-- Sparepart Custom -->
        <h3 style="font-size:14px;font-weight:600;color:#f59e0b;margin:24px 0 4px;text-transform:uppercase;letter-spacing:1px">🛒 Suku Cadang Custom</h3>
        <div id="custom-part-list">
            <?php if (!empty($custom_terpakai)): ?>
                <?php foreach ($custom_terpakai as $ct): ?>
                <div class="custom-part-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 40px;gap:8px;align-items:end;margin-bottom:8px">
                    <div class="form-group" style="margin:0"><input type="text" name="custom_nama[]" value="<?= htmlspecialchars($ct['nama_part']) ?>"></div>
                    <div class="form-group" style="margin:0"><input type="number" name="custom_jumlah[]" value="<?= $ct['jumlah'] ?>" min="1"></div>
                    <div class="form-group" style="margin:0"><input type="number" name="custom_harga_beli[]" value="<?= $ct['harga_beli'] ?>" min="0"></div>
                    <div class="form-group" style="margin:0"><input type="number" name="custom_harga_jual[]" value="<?= $ct['harga_jual'] ?>" min="0"></div>
                    <button type="button" onclick="hapusCustomPart(this)" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;font-size:14px;align-self:center">✕</button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="custom-part-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 40px;gap:8px;align-items:end;margin-bottom:8px">
                <div class="form-group" style="margin:0"><input type="text" name="custom_nama[]" placeholder="Nama sparepart..."></div>
                <div class="form-group" style="margin:0"><input type="number" name="custom_jumlah[]" value="1" min="1"></div>
                <div class="form-group" style="margin:0"><input type="number" name="custom_harga_beli[]" value="0" min="0"></div>
                <div class="form-group" style="margin:0"><input type="number" name="custom_harga_jual[]" value="0" min="0"></div>
                <button type="button" onclick="hapusCustomPart(this)" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;font-size:14px;align-self:center">✕</button>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="tambahCustomPart()" class="btn btn-sm" style="margin-top:8px;background:#fef3c7;color:#92400e;border-color:#fcd34d">+ Tambah Part Custom</button>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:24px;padding-top:16px;border-top:2px solid #f3f4f6">
            <a href="antrian.php" class="btn">Batal</a>
            <button type="submit" name="update" class="btn btn-primary">💾 Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
const jasaData = <?= json_encode($jasa_list) ?>;
const partGrouped = <?= json_encode($part_grouped) ?>;

function isiOptgroup(k) {
    const o = document.getElementById('opt-'+k);
    if (!o) return;
    o.innerHTML = '';
    jasaData.filter(j => j.kategori === k).forEach(j => {
        const op = document.createElement('option');
        op.value = j.id;
        op.setAttribute('data-harga', j.harga);
        op.textContent = j.nama_jasa + ' — Rp ' + Number(j.harga).toLocaleString('id-ID');
        o.appendChild(op);
    });
}

function toggleJasa() {
    const k = document.getElementById('kategori-servis').value;
    if (!k) return;
    ['ringan','sedang','berat'].forEach(x => {
        document.getElementById('opt-'+x).style.display = x === k ? '' : 'none';
    });
    isiOptgroup(k);
}

function toggleSumberJasa() {
    const s = document.getElementById('sumber-jasa').value;
    document.getElementById('input-sumber-jasa').value = s;
    document.getElementById('jasa-template').style.display = s === 'template' ? 'block' : 'none';
    document.getElementById('jasa-custom').style.display = s === 'custom' ? 'block' : 'none';
}

function updateHarga() {
    const sel = document.getElementById('sel-jasa');
    if (!sel) return;
    const o = sel.options[sel.selectedIndex];
    const txt = document.getElementById('harga-jasa-txt');
    if (o && o.getAttribute('data-harga')) {
        txt.textContent = 'Biaya jasa: Rp ' + Number(o.getAttribute('data-harga')).toLocaleString('id-ID');
    } else {
        txt.textContent = '';
    }
}

function toggleKelas() {
    document.getElementById('grup-kelas').style.display = 
        document.getElementById('sel-tipe').value === 'siswa' ? 'block' : 'none';
}

function tambahPart() {
    const container = document.getElementById('part-list');
    const template = container.querySelector('.part-row');
    const row = template.cloneNode(true);
    // Reset nilai
    row.querySelector('select').value = '';
    row.querySelector('input[type="number"]').value = 1;
    container.appendChild(row);
}

function hapusPart(btn) {
    const rows = document.querySelectorAll('.part-row');
    if (rows.length > 1) btn.closest('.part-row').remove();
}

function tambahCustomPart() {
    const container = document.getElementById('custom-part-list');
    const template = container.querySelector('.custom-part-row');
    const row = template.cloneNode(true);
    row.querySelectorAll('input').forEach(inp => {
        inp.value = inp.type === 'number' ? (inp.name.includes('jumlah') ? '1' : '0') : '';
    });
    container.appendChild(row);
}

function hapusCustomPart(btn) {
    if (document.querySelectorAll('.custom-part-row').length > 1) btn.closest('.custom-part-row').remove();
}

document.addEventListener('DOMContentLoaded', function() {
    isiOptgroup('ringan');
    isiOptgroup('sedang');
    isiOptgroup('berat');
    toggleJasa();
    
    <?php if($servis['jasa_id']): ?>
    setTimeout(function() {
        const sel = document.getElementById('sel-jasa');
        if (sel) {
            sel.value = '<?= $servis['jasa_id'] ?>';
            updateHarga();
        }
    }, 200);
    <?php endif; ?>
});
</script>

<?php require 'footer-minimal.php'; ?>