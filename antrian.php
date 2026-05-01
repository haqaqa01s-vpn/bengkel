<?php
require 'koneksi.php';
require 'header.php';

// Proses tambah servis baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    try {
        $pdo->beginTransaction();

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

        // Ambil harga jasa dari master
        $harga_jasa = 0;
        if (!empty($_POST['jasa_id'])) {
            $j = $pdo->prepare("SELECT harga FROM jasa_servis WHERE id=?");
            $j->execute([$_POST['jasa_id']]);
            $harga_jasa = $j->fetchColumn();
        }

        // Hitung km servis selanjutnya
        $km_sekarang = !empty($_POST['km_sekarang']) ? intval($_POST['km_sekarang']) : null;
        $km_selanjutnya = $km_sekarang ? $km_sekarang + 3000 : null;

        $stmt = $pdo->prepare("INSERT INTO servis
            (pelanggan_id, mekanik_id, kendaraan, plat, merk, tahun, km_sekarang, km_servis_selanjutnya, no_rangka, no_mesin, keluhan, layanan, jasa_id, biaya_jasa, tipe_pelanggan)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $pel_id,
            $_POST['mekanik_id'],
            $_POST['tipe_motor'],
            strtoupper($_POST['plat']),
            $_POST['merk'],
            $_POST['tahun'] ?: null,
            $km_sekarang,
            $km_selanjutnya,
            $_POST['no_rangka'] ?: null,
            $_POST['no_mesin'] ?: null,
            $_POST['keluhan'],
            $_POST['layanan'],
            $_POST['jasa_id'] ?: null,
            $harga_jasa,
            $_POST['tipe_pelanggan']
        ]);
        $servis_id = $pdo->lastInsertId();

        // Catat sparepart yang dipakai & kurangi stok
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

        $pdo->commit();
        header('Location: antrian.php?pesan=berhasil');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal: " . $e->getMessage();
    }
}

// Proses ubah status
if (isset($_GET['ubah_status'])) {
    $stmt = $pdo->prepare("UPDATE servis SET status=? WHERE id=?");
    $stmt->execute([$_GET['status'], $_GET['ubah_status']]);
    header('Location: antrian.php');
    exit;
}

// Ambil data antrian
$antrian = $pdo->query("
    SELECT s.*, p.nama AS nama_pelanggan, mk.nama AS nama_mekanik, j.nama_jasa,
           mot.plat, mot.merk, mot.tipe AS kendaraan, mot.tahun
    FROM servis s
    JOIN pelanggan p ON s.pelanggan_id = p.id
    JOIN mekanik mk ON s.mekanik_id = mk.id
    LEFT JOIN jasa_servis j ON s.jasa_id = j.id
    LEFT JOIN motor mot ON s.motor_id = mot.id
    ORDER BY s.dibuat DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk dropdown
$mekanik = $pdo->query("SELECT id, nama FROM mekanik ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
$jasa_list = $pdo->query("SELECT * FROM jasa_servis ORDER BY kategori, nama_jasa")->fetchAll(PDO::FETCH_ASSOC);
$part_list = $pdo->query("SELECT * FROM suku_cadang WHERE stok > 0 ORDER BY kategori, nama_part")->fetchAll(PDO::FETCH_ASSOC);

// Grup jasa per kategori
$jasa_grouped = [];
foreach ($jasa_list as $j) {
    $jasa_grouped[$j['kategori']][] = $j;
}

// Grup part per kategori
$part_grouped = [];
foreach ($part_list as $p) {
    $part_grouped[$p['kategori']][] = $p;
}
?>

<div class="page-header">
    <h1>Antrian Servis</h1>
    <a href="form-servis.php" class="btn btn-primary">
        + Daftar Servis Baru
    </a>
</div>

<?php if(isset($_GET['pesan'])): ?>
<div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    Servis berhasil didaftarkan!
</div>
<?php endif; ?>

<?php if(isset($error)): ?>
<div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:16px">
    <?= $error ?>
</div>
<?php endif; ?>

<div class="kartu">
    <table>
        <thead>
            <tr><th>Pelanggan</th><th>Kendaraan</th><th>Jasa</th><th>Mekanik</th><th>Biaya</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach($antrian as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['nama_pelanggan']) ?></td>
                <td>
                    <span style="font-weight:500"><?= $a['merk'] ?> <?= $a['kendaraan'] ?></span>
                    <div style="font-size:11px;color:#888"><?= $a['plat'] ?> · <?= $a['tahun'] ?></div>
                </td>
                <td style="font-size:12px"><?= htmlspecialchars($a['nama_jasa'] ?? $a['layanan']) ?></td>
                <td><?= htmlspecialchars($a['nama_mekanik']) ?></td>
                <td style="font-weight:500;font-size:12px">
                    <?= $a['biaya_jasa'] > 0 ? 'Rp '.number_format($a['biaya_jasa'],0,',','.') : '-' ?>
                </td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                <td style="display:flex;gap:4px;flex-wrap:wrap">
                    <?php if($a['status'] === 'menunggu'): ?>
                        <a href="edit-servis.php?id=<?= $a['id'] ?>" class="btn btn-sm" style="background:#fef3c7;color:#92400e">✏️ Edit</a>
                        <a href="?ubah_status=<?= $a['id'] ?>&status=proses" class="btn btn-sm btn-primary">▶ Mulai</a>
                    <?php elseif($a['status'] === 'proses'): ?>
                        <a href="edit-servis.php?id=<?= $a['id'] ?>" class="btn btn-sm" style="background:#fef3c7;color:#92400e">✏️ Edit</a>
                        <a href="?ubah_status=<?= $a['id'] ?>&status=selesai" class="btn btn-sm">✓ Selesai</a>
                    <?php else: ?>
                        <span style="font-size:11px;color:#888">✓ Done</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'footer.php'; ?>