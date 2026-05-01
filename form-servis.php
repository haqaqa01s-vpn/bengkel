<?php
require 'koneksi.php';
require 'header-minimal.php';

// Proses tambah servis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    try {
        $pdo->beginTransaction();

        // === PELANGGAN ===
        $pelanggan_id = $_POST['pelanggan_id'];
        
        if ($pelanggan_id === 'baru') {
            // Tambah pelanggan baru
            $ins = $pdo->prepare("INSERT INTO pelanggan (nama, telepon, alamat, tipe, kelas) VALUES (?,?,?,?,?)");
            $ins->execute([
                $_POST['nama_baru'],
                $_POST['telepon_baru'],
                $_POST['alamat_baru'] ?? '',
                $_POST['tipe_pelanggan'],
                $_POST['kelas'] ?? null
            ]);
            $pelanggan_id = $pdo->lastInsertId();
        } else {
            // Update data pelanggan existing
            $pdo->prepare("UPDATE pelanggan SET telepon=?, alamat=?, tipe=?, kelas=? WHERE id=?")
                ->execute([
                    $_POST['telepon'],
                    $_POST['alamat'] ?? '',
                    $_POST['tipe_pelanggan'],
                    $_POST['kelas'] ?? null,
                    $pelanggan_id
                ]);
        }

        // === MOTOR ===
        $motor_id = $_POST['motor_id'];
        
        if ($motor_id === 'baru') {
            // Tambah motor baru
            $ins = $pdo->prepare("INSERT INTO motor (pelanggan_id, plat, merk, tipe, tahun, no_rangka, no_mesin) VALUES (?,?,?,?,?,?,?)");
            $ins->execute([
                $pelanggan_id,
                strtoupper($_POST['plat_baru']),
                $_POST['merk_baru'],
                $_POST['tipe_baru'],
                $_POST['tahun_baru'] ?: null,
                $_POST['no_rangka_baru'] ?: null,
                $_POST['no_mesin_baru'] ?: null
            ]);
            $motor_id = $pdo->lastInsertId();
        }

        // === JASA ===
        $sumber_jasa = $_POST['sumber_jasa'] ?? 'template';
        $jasa_id = !empty($_POST['jasa_id']) ? $_POST['jasa_id'] : null;
        $deskripsi_jasa = $_POST['deskripsi_jasa'] ?? null;
        $harga_jasa = 0;

        if ($sumber_jasa === 'custom') {
            $harga_jasa = intval($_POST['harga_jasa_custom'] ?? 0);
            $jasa_id = null;
        } elseif (!empty($jasa_id)) {
            $j = $pdo->prepare("SELECT harga FROM jasa_servis WHERE id=?");
            $j->execute([$jasa_id]);
            $harga_jasa = $j->fetchColumn();
        }

        // === KM ===
        $km_sekarang = !empty($_POST['km_sekarang']) ? intval($_POST['km_sekarang']) : null;
        $km_selanjutnya = $km_sekarang ? $km_sekarang + 3000 : null;

        // === INSERT SERVIS ===
        $stmt = $pdo->prepare("INSERT INTO servis
            (pelanggan_id, motor_id, mekanik_id, km_sekarang, km_servis_selanjutnya,
             keluhan, layanan, jasa_id, kategori_servis, deskripsi_jasa, harga_jasa_custom, biaya_jasa, tipe_pelanggan)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $pelanggan_id,
            $motor_id,
            $_POST['mekanik_id'],
            $km_sekarang,
            $km_selanjutnya,
            $_POST['keluhan'],
            $_POST['layanan'],
            $jasa_id,
            $_POST['kategori_servis'],
            $deskripsi_jasa,
            $sumber_jasa === 'custom' ? $harga_jasa : 0,
            $harga_jasa,
            $_POST['tipe_pelanggan']
        ]);
        $servis_id = $pdo->lastInsertId();

        // === SPAREPART MASTER ===
        if (!empty($_POST['part_id']) && is_array($_POST['part_id'])) {
            $stmt_part = $pdo->prepare("INSERT INTO servis_part (servis_id, part_id, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?)");
            $stmt_stok = $pdo->prepare("UPDATE suku_cadang SET stok = stok - ? WHERE id = ? AND stok >= ?");
            $stmt_harga = $pdo->prepare("SELECT harga_jual, stok FROM suku_cadang WHERE id=?");

            foreach ($_POST['part_id'] as $i => $part_id) {
                if (empty($part_id)) continue;
                $jumlah = max(1, intval($_POST['part_jumlah'][$i] ?? 1));
                $stmt_harga->execute([$part_id]);
                $part = $stmt_harga->fetch(PDO::FETCH_ASSOC);
                if ($part && $part['stok'] >= $jumlah) {
                    $subtotal = $part['harga_jual'] * $jumlah;
                    $stmt_part->execute([$servis_id, $part_id, $jumlah, $part['harga_jual'], $subtotal]);
                    $stmt_stok->execute([$jumlah, $part_id, $jumlah]);
                }
            }
        }

        // === SPAREPART CUSTOM ===
        if (!empty($_POST['custom_nama']) && is_array($_POST['custom_nama'])) {
            $stmt_custom = $pdo->prepare("INSERT INTO sparepart_custom (servis_id, nama_part, jumlah, harga_beli, harga_jual, subtotal) VALUES (?,?,?,?,?,?)");
            foreach ($_POST['custom_nama'] as $i => $nama) {
                if (empty(trim($nama))) continue;
                $jumlah = max(1, intval($_POST['custom_jumlah'][$i] ?? 1));
                $harga_beli = intval($_POST['custom_harga_beli'][$i] ?? 0);
                $harga_jual = intval($_POST['custom_harga_jual'][$i] ?? 0);
                $subtotal = $harga_jual * $jumlah;
                $stmt_custom->execute([$servis_id, trim($nama), $jumlah, $harga_beli, $harga_jual, $subtotal]);
            }
        }

        $pdo->commit();
        header('Location: antrian.php?pesan=berhasil');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal: " . $e->getMessage();
    }
}

// Data dropdown
$mekanik = $pdo->query("SELECT id, nama FROM mekanik ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$jasa_list = $pdo->query("SELECT * FROM jasa_servis ORDER BY kategori, nama_jasa")->fetchAll(PDO::FETCH_ASSOC);
$part_list = $pdo->query("SELECT * FROM suku_cadang WHERE stok > 0 ORDER BY kategori, nama_part")->fetchAll(PDO::FETCH_ASSOC);
$pelanggan_list = $pdo->query("SELECT * FROM pelanggan ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);

$jasa_grouped = [];
foreach ($jasa_list as $j) {
    $jasa_grouped[$j['kategori']][] = $j;
}

$part_grouped = [];
foreach ($part_list as $p) {
    $part_grouped[$p['kategori']][] = $p;
}

// Kirim data pelanggan & motor ke JS
$pelanggan_json = json_encode($pelanggan_list);
// $motor_json = json_encode($pdo->query("SELECT * FROM motor ORDER BY plat")->fetchAll(PDO::FETCH_ASSOC));
?>

<div class="page-header">
    <h1>📝 Daftar Servis Baru</h1>
</div>

<?php if (isset($error)): ?>
<div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    <?= $error ?>
</div>
<?php endif; ?>

<div class="kartu">
    <form method="POST">
        <input type="hidden" name="sumber_jasa" id="input-sumber-jasa" value="template">

        <!-- ============ PILIH PELANGGAN ============ -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin-bottom:12px;text-transform:uppercase;letter-spacing:1px">👤 Cari Pelanggan</h3>
        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label>Cari Pelanggan (Nama / No. HP)</label>
                <select name="pelanggan_id" id="select-pelanggan" class="searchable" onchange="pilihPelanggan()" required>
                    <option value="">-- Cari atau tambah baru --</option>
                    <option value="baru">➕ Tambah Pelanggan Baru</option>
                    <?php foreach($pelanggan_list as $pl): ?>
                    <option value="<?= $pl['id'] ?>">
                        <?= htmlspecialchars($pl['nama']) ?> — <?= htmlspecialchars($pl['telepon'] ?: 'No HP') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Data Pelanggan (auto-fill dari existing, atau input baru) -->
        <div id="form-pelanggan-baru" style="display:none">
            <div class="form-row">
                <div class="form-group"><label>Nama Pelanggan</label><input name="nama_baru" placeholder="Nama lengkap"></div>
                <div class="form-group"><label>No. Telepon</label><input name="telepon_baru" placeholder="08xxx"></div>
            </div>
            <div class="form-group"><label>Alamat</label><textarea name="alamat_baru" rows="2" placeholder="Alamat lengkap..."></textarea></div>
        </div>
        
        <div id="form-pelanggan-existing" style="display:none">
            <input type="hidden" name="nama" id="input-nama">
            <div class="form-row">
                <div class="form-group"><label>Nama</label><input id="display-nama" disabled style="background:#f9fafb"></div>
                <div class="form-group"><label>No. Telepon</label><input name="telepon" id="input-telepon" placeholder="08xxx"></div>
            </div>
            <div class="form-group"><label>Alamat</label><textarea name="alamat" id="input-alamat" rows="2" placeholder="Alamat lengkap..."></textarea></div>
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

        <!-- ============ PILIH MOTOR ============ -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin:24px 0 12px;text-transform:uppercase;letter-spacing:1px">🏍️ Pilih Motor</h3>
        <div class="form-row">
            <div class="form-group" style="flex:2">
                <label>Motor Pelanggan</label>
                <select name="motor_id" id="select-motor" class="searchable" onchange="pilihMotor()" required>
                    <option value="">-- Pilih motor atau tambah baru --</option>
                </select>
            </div>
        </div>

        <!-- Form motor baru -->
        <div id="form-motor-baru" style="display:none">
            <div class="form-row">
                <div class="form-group">
                    <label>Plat Nomor</label>
                    <input name="plat_baru" placeholder="BP 1234 AB">
                </div>
                <div class="form-group">
                    <label>Merk Motor</label>
                    <select name="merk_baru">
                        <option>Honda</option><option>Yamaha</option><option>Suzuki</option>
                        <option>Kawasaki</option><option>TVS</option><option>Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Tipe / Model</label><input name="tipe_baru" placeholder="Vario 125, Mio M3..."></div>
                <div class="form-group"><label>Tahun</label><input name="tahun_baru" type="number" min="1990" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>No. Rangka</label><input name="no_rangka_baru" placeholder="MH1JF..."></div>
                <div class="form-group"><label>No. Mesin</label><input name="no_mesin_baru" placeholder="JF50E..."></div>
            </div>
        </div>

        <!-- Info motor terpilih -->
        <div id="info-motor" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:12px;font-size:12px">
            <strong id="info-motor-text"></strong>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Kilometer Saat Ini (KM)</label>
                <input name="km_sekarang" type="number" min="0" placeholder="15000">
                <small style="color:#888;font-size:11px">Servis selanjutnya +3.000 KM</small>
            </div>
        </div>

        <!-- ============ DATA SERVIS ============ -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin:24px 0 12px;text-transform:uppercase;letter-spacing:1px">🔧 Data Servis</h3>
        <div class="form-group">
            <label>Keluhan</label>
            <textarea name="keluhan" rows="2" placeholder="Mesin kasar, susah starter, rem blong..."></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Kategori Servis</label>
                <select name="kategori_servis" id="kategori-servis" onchange="toggleJasa()" required>
                    <option value="">-- Pilih kategori --</option>
                    <option value="ringan">🔵 Ringan</option>
                    <option value="sedang">🟡 Sedang</option>
                    <option value="berat">🔴 Berat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sumber Jasa</label>
                <select id="sumber-jasa" onchange="toggleSumberJasa()">
                    <option value="template">📋 Dari Master Data</option>
                    <option value="custom">✏️ Custom / Dadakan</option>
                </select>
            </div>
        </div>

        <div id="jasa-template">
            <div class="form-row">
                <div class="form-group">
                    <label>Jenis Layanan (Deskripsi)</label>
                    <input name="layanan" placeholder="Tune Up, Ganti Oli, dll" required>
                </div>
                <div class="form-group">
                    <label>Paket Jasa (Master)</label>
                    <select name="jasa_id" id="sel-jasa" onchange="updateHarga()">
                        <option value="">-- Pilih paket jasa --</option>
                        <optgroup label="🔵 Ringan" id="opt-ringan"></optgroup>
                        <optgroup label="🟡 Sedang" id="opt-sedang"></optgroup>
                        <optgroup label="🔴 Berat" id="opt-berat"></optgroup>
                    </select>
                    <small id="harga-jasa-txt" style="color:#2563eb;display:block;margin-top:4px"></small>
                </div>
            </div>
        </div>

        <div id="jasa-custom" style="display:none">
            <div class="form-row">
                <div class="form-group">
                    <label>Deskripsi Jasa</label>
                    <input name="deskripsi_jasa" placeholder="Contoh: Bore Up, Setting ECU...">
                </div>
                <div class="form-group">
                    <label>Harga Jasa (Rp)</label>
                    <input type="number" name="harga_jasa_custom" value="0" min="0">
                </div>
            </div>
        </div>

        <div class="form-row">
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

        <!-- Sparepart Master -->
        <h3 style="font-size:14px;font-weight:600;color:#2563eb;margin:24px 0 4px;text-transform:uppercase;letter-spacing:1px">📦 Suku Cadang (Master)</h3>
        <div id="part-list">
            <div class="part-row" style="display:flex;gap:8px;align-items:flex-end;margin-bottom:8px">
                <div class="form-group" style="flex:1;margin:0">
                    <select name="part_id[]" class="searchable">
                        <option value="">-- Cari suku cadang... --</option>
                        <?php foreach($part_grouped as $kat => $parts): ?>
                        <optgroup label="<?= $kat ?>">
                            <?php foreach($parts as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= $p['kode_part'] ?> — <?= htmlspecialchars($p['nama_part']) ?> (Rp <?= number_format($p['harga_jual'],0,',','.') ?>)
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="width:70px;margin:0">
                    <input type="number" name="part_jumlah[]" value="1" min="1" placeholder="Jml">
                </div>
                <button type="button" onclick="hapusPart(this)" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;font-size:14px">✕</button>
            </div>
        </div>
        <button type="button" onclick="tambahPart()" class="btn btn-sm" style="margin-top:8px">+ Tambah Part Master</button>

        <!-- Sparepart Custom -->
        <h3 style="font-size:14px;font-weight:600;color:#f59e0b;margin:24px 0 4px;text-transform:uppercase;letter-spacing:1px">🛒 Suku Cadang Custom (Beli Khusus)</h3>
        <div id="custom-part-list">
            <div class="custom-part-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 40px;gap:8px;align-items:end;margin-bottom:8px">
                <div class="form-group" style="margin:0"><input type="text" name="custom_nama[]" placeholder="Nama sparepart..."></div>
                <div class="form-group" style="margin:0"><input type="number" name="custom_jumlah[]" value="1" min="1"></div>
                <div class="form-group" style="margin:0"><input type="number" name="custom_harga_beli[]" value="0" min="0"></div>
                <div class="form-group" style="margin:0"><input type="number" name="custom_harga_jual[]" value="0" min="0"></div>
                <button type="button" onclick="hapusCustomPart(this)" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;font-size:14px;align-self:center">✕</button>
            </div>
        </div>
        <button type="button" onclick="tambahCustomPart()" class="btn btn-sm" style="margin-top:8px;background:#fef3c7;color:#92400e;border-color:#fcd34d">+ Tambah Part Custom</button>

        <!-- Submit -->
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:24px;padding-top:16px;border-top:2px solid #f3f4f6">
            <a href="antrian.php" class="btn">Batal</a>
            <button type="submit" name="tambah" class="btn btn-primary">✅ Daftar Servis</button>
        </div>
    </form>
</div>

<script>
// Data dari PHP
const pelangganData = <?= $pelanggan_json ?>;
const motorData = <?= json_encode($pdo->query("SELECT id, pelanggan_id, plat, merk, tipe, tahun, no_rangka, no_mesin FROM motor ORDER BY plat")->fetchAll(PDO::FETCH_ASSOC)) ?>;

// ============ PELANGGAN ============
function pilihPelanggan() {
    const val = document.getElementById('select-pelanggan').value;
    const formBaru = document.getElementById('form-pelanggan-baru');
    const formExisting = document.getElementById('form-pelanggan-existing');
    const selectMotor = document.getElementById('select-motor');
    
    if (val === 'baru') {
        formBaru.style.display = 'block';
        formExisting.style.display = 'none';
        selectMotor.innerHTML = '<option value="baru">➕ Tambah Motor Baru</option>';
        document.getElementById('form-motor-baru').style.display = 'block';
        document.getElementById('info-motor').style.display = 'none';
        selectMotor.value = 'baru';
    } else if (val) {
        formBaru.style.display = 'none';
        formExisting.style.display = 'block';
        
        const pl = pelangganData.find(p => p.id == val);
        if (pl) {
            document.getElementById('input-nama').value = pl.nama;
            document.getElementById('display-nama').value = pl.nama;
            document.getElementById('input-telepon').value = pl.telepon || '';
            document.getElementById('input-alamat').value = pl.alamat || '';
            document.getElementById('sel-tipe').value = pl.tipe || 'umum';
            toggleKelas();
            if (pl.kelas) document.querySelector('input[name="kelas"]').value = pl.kelas;
            
            // Isi motor milik pelanggan ini
            isiMotorPelanggan(val);
        }
    } else {
        formBaru.style.display = 'none';
        formExisting.style.display = 'none';
        selectMotor.innerHTML = '<option value="">-- Pilih motor atau tambah baru --</option>';
    }
}

function isiMotorPelanggan(pelangganId) {
    const selectMotor = document.getElementById('select-motor');
    
    const motors = motorData.filter(m => m.pelanggan_id == pelangganId);
    
    selectMotor.innerHTML = '<option value="">-- Pilih motor --</option>';
    
    if (motors.length > 0) {
        motors.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = (m.plat || 'No Plat') + ' — ' + (m.merk || '') + ' ' + (m.tipe || '') + ' (' + (m.tahun || '-') + ')';
            opt.setAttribute('data-merk', m.merk || '');
            opt.setAttribute('data-tipe', m.tipe || '');
            opt.setAttribute('data-tahun', m.tahun || '');
            opt.setAttribute('data-plat', m.plat || '');
            opt.setAttribute('data-no_rangka', m.no_rangka || '');
            opt.setAttribute('data-no_mesin', m.no_mesin || '');
            selectMotor.appendChild(opt);
        });
    }
    
    // Tambah opsi motor baru
    const optBaru = document.createElement('option');
    optBaru.value = 'baru';
    optBaru.textContent = '➕ Tambah Motor Baru';
    selectMotor.appendChild(optBaru);
    
    // Auto-select jika cuma 1 motor
    if (motors.length === 1) {
        selectMotor.value = motors[0].id;
        pilihMotor();
    }
    
    // Re-init searchable select
    if (typeof SearchableSelect !== 'undefined') {
        // Hancurkan dulu yang lama
        const wrapper = selectMotor.closest('.searchable-select');
        if (wrapper) {
            wrapper.replaceWith(selectMotor);
        }
        // Init ulang
        new SearchableSelect(selectMotor);
    }
}

// ============ MOTOR ============
function pilihMotor() {
    const val = document.getElementById('select-motor').value;
    const formMotorBaru = document.getElementById('form-motor-baru');
    const infoMotor = document.getElementById('info-motor');
    
    if (val === 'baru') {
        formMotorBaru.style.display = 'block';
        infoMotor.style.display = 'none';
        // Kosongkan input motor baru
        document.querySelector('input[name="plat_baru"]').value = '';
        document.querySelector('select[name="merk_baru"]').value = 'Honda';
        document.querySelector('input[name="tipe_baru"]').value = '';
        document.querySelector('input[name="tahun_baru"]').value = '';
        document.querySelector('input[name="no_rangka_baru"]').value = '';
        document.querySelector('input[name="no_mesin_baru"]').value = '';
    } else if (val) {
        formMotorBaru.style.display = 'none';
        
        // Cari data dari select
        const selectMotor = document.getElementById('select-motor');
        const display = selectMotor.querySelector('option:checked');
        
        if (display && display.getAttribute('data-merk')) {
            document.getElementById('info-motor-text').textContent = 
                display.getAttribute('data-merk') + ' ' + 
                display.getAttribute('data-tipe') + 
                ' | Plat: ' + display.getAttribute('data-plat') + 
                ' | Tahun: ' + (display.getAttribute('data-tahun') || '-');
            infoMotor.style.display = 'block';
        } else {
            infoMotor.style.display = 'none';
        }
    } else {
        formMotorBaru.style.display = 'none';
        infoMotor.style.display = 'none';
    }
}

// ============ JASA ============
const jasaData = <?= json_encode($jasa_list) ?>;

function isiOptgroup(kategori) {
    const opt = document.getElementById('opt-' + kategori);
    opt.innerHTML = '';
    jasaData.filter(j => j.kategori === kategori).forEach(j => {
        const option = document.createElement('option');
        option.value = j.id;
        option.setAttribute('data-harga', j.harga);
        option.textContent = j.nama_jasa + ' — Rp ' + Number(j.harga).toLocaleString('id-ID');
        opt.appendChild(option);
    });
}

function toggleJasa() {
    const kategori = document.getElementById('kategori-servis').value;
    if (!kategori) return;
    document.getElementById('opt-ringan').style.display = kategori === 'ringan' ? '' : 'none';
    document.getElementById('opt-sedang').style.display = kategori === 'sedang' ? '' : 'none';
    document.getElementById('opt-berat').style.display = kategori === 'berat' ? '' : 'none';
    isiOptgroup(kategori);
}

function toggleSumberJasa() {
    const sumber = document.getElementById('sumber-jasa').value;
    document.getElementById('input-sumber-jasa').value = sumber;
    document.getElementById('jasa-template').style.display = sumber === 'template' ? 'block' : 'none';
    document.getElementById('jasa-custom').style.display = sumber === 'custom' ? 'block' : 'none';
}

function updateHarga() {
    const sel = document.getElementById('sel-jasa');
    const opt = sel.options[sel.selectedIndex];
    const harga = opt.getAttribute('data-harga');
    document.getElementById('harga-jasa-txt').textContent = harga ? 'Biaya jasa: Rp ' + Number(harga).toLocaleString('id-ID') : '';
}

function toggleKelas() {
    document.getElementById('grup-kelas').style.display = document.getElementById('sel-tipe').value === 'siswa' ? 'block' : 'none';
}

// ============ SPAREPART ============
function tambahPart() {
    const container = document.getElementById('part-list');
    const row = document.createElement('div');
    row.className = 'part-row';
    row.style.cssText = 'display:flex;gap:8px;align-items:flex-end;margin-bottom:8px';
    row.innerHTML = container.querySelector('.part-row').innerHTML;
    container.appendChild(row);
    const newSelect = row.querySelector('select.searchable');
    if (newSelect && typeof SearchableSelect !== 'undefined') new SearchableSelect(newSelect);
}

function hapusPart(btn) {
    if (document.querySelectorAll('.part-row').length > 1) btn.closest('.part-row').remove();
}

function tambahCustomPart() {
    const container = document.getElementById('custom-part-list');
    const row = document.createElement('div');
    row.className = 'custom-part-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 1fr 40px;gap:8px;align-items:end;margin-bottom:8px';
    row.innerHTML = container.querySelector('.custom-part-row').innerHTML;
    row.querySelectorAll('input').forEach(inp => inp.value = inp.type === 'number' ? (inp.name.includes('jumlah') ? '1' : '0') : '');
    container.appendChild(row);
}

function hapusCustomPart(btn) {
    if (document.querySelectorAll('.custom-part-row').length > 1) btn.closest('.custom-part-row').remove();
}

// Init
document.addEventListener('DOMContentLoaded', function() {
    isiOptgroup('ringan');
    isiOptgroup('sedang');
    isiOptgroup('berat');
});
</script>

<?php require 'footer-minimal.php'; ?>