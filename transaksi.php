<?php
/**
 * transaksi.php - Form input transaksi servis
 * Total dihitung ulang di server dari harga database (bukan dari input browser),
 * lalu stok sparepart dikurangi dalam satu transaksi database.
 */
require 'koneksi.php';
wajib_login();

$error = '';
$old = ['nama' => '', 'nopol' => '', 'servis' => '', 'jasa' => '', 'sparepart' => '', 'jumlah' => '1'];

$spareparts = $pdo->query('SELECT id_sparepart, kode_sparepart, nama_sparepart, harga, stok FROM sparepart ORDER BY nama_sparepart')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'nama'      => trim($_POST['nama_pelanggan'] ?? ''),
        'nopol'     => strtoupper(trim($_POST['no_polisi'] ?? '')),
        'servis'    => trim($_POST['jenis_servis'] ?? ''),
        'jasa'      => trim($_POST['biaya_jasa'] ?? ''),
        'sparepart' => trim($_POST['id_sparepart'] ?? ''),
        'jumlah'    => trim($_POST['jumlah_sparepart'] ?? '1'),
    ];

    $idSp   = ($old['sparepart'] !== '' && ctype_digit($old['sparepart'])) ? (int)$old['sparepart'] : 0;
    $jumlah = ctype_digit($old['jumlah']) ? (int)$old['jumlah'] : -1;
    $jasa   = ctype_digit($old['jasa']) ? (int)$old['jasa'] : -1;

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($old['nama'] === '' || $old['nopol'] === '' || $old['servis'] === '') {
        $error = 'Nama pelanggan, nomor polisi, dan jenis servis wajib diisi.';
    } elseif (mb_strlen($old['nama']) > 100 || mb_strlen($old['nopol']) > 15 || mb_strlen($old['servis']) > 255) {
        $error = 'Salah satu isian terlalu panjang.';
    } elseif ($jasa < 0 || $jasa > 100000000) {
        $error = 'Biaya jasa harus berupa angka bulat 0 atau lebih (tanpa titik/koma).';
    } elseif ($idSp > 0 && ($jumlah < 1 || $jumlah > 1000)) {
        $error = 'Jumlah sparepart harus minimal 1.';
    } else {
        try {
            $pdo->beginTransaction();

            $hargaSp = 0;
            if ($idSp > 0) {
                // Kunci baris agar stok tidak bentrok bila ada dua kasir bersamaan
                $st = $pdo->prepare('SELECT nama_sparepart, harga, stok FROM sparepart WHERE id_sparepart = ? FOR UPDATE');
                $st->execute([$idSp]);
                $sp = $st->fetch();

                if (!$sp) {
                    throw new RuntimeException('Sparepart tidak ditemukan.');
                }
                if ($sp['stok'] < $jumlah) {
                    throw new RuntimeException('Stok ' . $sp['nama_sparepart'] . ' tidak cukup (tersisa ' . $sp['stok'] . ').');
                }
                $hargaSp = (int)$sp['harga'];
                $pdo->prepare('UPDATE sparepart SET stok = stok - ? WHERE id_sparepart = ?')->execute([$jumlah, $idSp]);
            } else {
                $jumlah = 0;
            }

            $total = $jasa + ($hargaSp * $jumlah);

            // Nomor nota: FRM-YYYYMMDD-urut
            $c = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal) = CURDATE()');
            $c->execute();
            $noNota = 'FRM-' . date('Ymd') . '-' . str_pad((string)((int)$c->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);

            $ins = $pdo->prepare('INSERT INTO transaksi
                (no_nota, tanggal, nama_pelanggan, no_polisi, jenis_servis, biaya_jasa, id_sparepart, jumlah_sparepart, harga_sparepart, total_bayar, id_admin)
                VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([$noNota, $old['nama'], $old['nopol'], $old['servis'], $jasa,
                           $idSp > 0 ? $idSp : null, $jumlah, $hargaSp, $total, $_SESSION['id_admin']]);
            $idBaru = (int)$pdo->lastInsertId();

            $pdo->commit();
            header('Location: nota.php?id=' . $idBaru . '&baru=1');
            exit;
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($ex->getMessage());
            $error = 'Transaksi gagal disimpan. Coba lagi; bila berulang, hubungi admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Transaksi - Bengkel Motor Firman</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap">
    <div class="brand">Bengkel Motor Firman<small>Pembayaran Servis Motor</small></div>
    <div class="user">
      <span><?= e($_SESSION['nama']) ?> <span class="peran"><?= e($_SESSION['role']) ?></span></span>
      <a class="btn btn-garis btn-kecil" href="dashboard.php">Dashboard</a>
      <a class="btn btn-garis btn-kecil" href="logout.php">Keluar</a>
    </div>
  </div>
</header>

<main class="wrap">
  <section class="card">
    <div class="judul-bar"><h1>Tambah transaksi servis</h1></div>

    <?php if ($error): ?><div class="pesan error" role="alert"><?= e($error) ?></div><?php endif; ?>

    <form method="post" id="formTrx" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="grid">
        <div>
          <label for="nama_pelanggan">Nama pelanggan</label>
          <input type="text" id="nama_pelanggan" name="nama_pelanggan" maxlength="100" value="<?= e($old['nama']) ?>" required>
        </div>
        <div>
          <label for="no_polisi">Nomor polisi kendaraan</label>
          <input type="text" id="no_polisi" name="no_polisi" maxlength="15" placeholder="B 1234 ABC" style="text-transform:uppercase" value="<?= e($old['nopol']) ?>" required>
        </div>
        <div class="penuh">
          <label for="jenis_servis">Jenis servis / kerusakan</label>
          <textarea id="jenis_servis" name="jenis_servis" rows="2" maxlength="255" placeholder="Contoh: Servis rutin, ganti oli, rem blong" required><?= e($old['servis']) ?></textarea>
        </div>
        <div>
          <label for="biaya_jasa">Biaya jasa servis (Rp)</label>
          <input type="number" id="biaya_jasa" name="biaya_jasa" min="0" step="1" inputmode="numeric" value="<?= e($old['jasa']) ?>" required>
        </div>
        <div>
          <label for="id_sparepart">Suku cadang (opsional)</label>
          <select id="id_sparepart" name="id_sparepart">
            <option value="" data-harga="0" data-stok="0">Tanpa sparepart</option>
            <?php foreach ($spareparts as $s): ?>
              <option value="<?= (int)$s['id_sparepart'] ?>"
                      data-harga="<?= (int)$s['harga'] ?>" data-stok="<?= (int)$s['stok'] ?>"
                      <?= $old['sparepart'] === (string)$s['id_sparepart'] ? 'selected' : '' ?>
                      <?= $s['stok'] < 1 ? 'disabled' : '' ?>>
                <?= e($s['kode_sparepart'] . ' - ' . $s['nama_sparepart']) ?> (<?= rupiah($s['harga']) ?>, stok <?= (int)$s['stok'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="jumlah_sparepart">Jumlah sparepart</label>
          <input type="number" id="jumlah_sparepart" name="jumlah_sparepart" min="1" step="1" value="<?= e($old['jumlah']) ?>">
          <div class="bantu" id="infoStok">Pilih sparepart untuk melihat stok.</div>
        </div>
        <div>
          <label for="harga_satuan">Harga sparepart satuan</label>
          <input type="text" id="harga_satuan" value="Rp 0" readonly>
        </div>
        <div class="penuh">
          <div class="total-box">
            <div>Total bayar<div class="bantu" style="color:#b9c6d1">Biaya jasa + (harga sparepart &times; jumlah)</div></div>
            <div class="angka" id="totalBayar" aria-live="polite">Rp 0</div>
          </div>
        </div>
      </div>

      <div class="aksi">
        <button type="submit" class="btn btn-utama">Simpan dan cetak nota</button>
        <a href="dashboard.php" class="btn btn-garis">Batal</a>
      </div>
    </form>
  </section>
</main>

<script>
(function () {
  var jasa = document.getElementById('biaya_jasa');
  var sel = document.getElementById('id_sparepart');
  var qty = document.getElementById('jumlah_sparepart');
  var harga = document.getElementById('harga_satuan');
  var total = document.getElementById('totalBayar');
  var info = document.getElementById('infoStok');

  function rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

  function hitung() {
    var opt = sel.options[sel.selectedIndex];
    var h = parseInt(opt.getAttribute('data-harga'), 10) || 0;
    var stok = parseInt(opt.getAttribute('data-stok'), 10) || 0;
    var j = parseInt(jasa.value, 10) || 0;
    var q = sel.value === '' ? 0 : (parseInt(qty.value, 10) || 0);

    harga.value = rp(h);
    if (sel.value === '') {
      info.textContent = 'Pilih sparepart untuk melihat stok.';
      qty.disabled = true;
    } else {
      qty.disabled = false;
      qty.max = stok;
      info.textContent = 'Stok tersedia: ' + stok + (q > stok ? ' - jumlah melebihi stok!' : '');
    }
    total.textContent = rp(j + h * q);
  }

  [jasa, sel, qty].forEach(function (el) {
    el.addEventListener('input', hitung);
    el.addEventListener('change', hitung);
  });
  hitung();
})();
</script>
</body>
</html>
