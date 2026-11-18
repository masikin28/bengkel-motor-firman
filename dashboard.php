<?php
/**
 * dashboard.php - Riwayat transaksi pembayaran servis
 */
require 'koneksi.php';
wajib_login();

$cari = trim($_GET['cari'] ?? '');

$sql = 'SELECT t.id_transaksi, t.no_nota, t.tanggal, t.nama_pelanggan, t.no_polisi, t.jenis_servis,
               t.biaya_jasa, t.jumlah_sparepart, t.total_bayar, s.nama_sparepart
        FROM transaksi t
        LEFT JOIN sparepart s ON s.id_sparepart = t.id_sparepart';
$param = [];
if ($cari !== '') {
    $sql .= ' WHERE t.nama_pelanggan LIKE ? OR t.no_polisi LIKE ? OR t.no_nota LIKE ?';
    $like = '%' . $cari . '%';
    $param = [$like, $like, $like];
}
$sql .= ' ORDER BY t.tanggal DESC, t.id_transaksi DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($param);
$data = $stmt->fetchAll();

$ring = $pdo->query("SELECT COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS total FROM transaksi")->fetch();
$hari = $pdo->query("SELECT COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS total FROM transaksi WHERE DATE(tanggal) = CURDATE()")->fetch();
$stokMenipis = (int)$pdo->query('SELECT COUNT(*) FROM sparepart WHERE stok <= 5')->fetchColumn();

$sukses = $_GET['sukses'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Bengkel Motor Firman</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap">
    <div class="brand">Bengkel Motor Firman<small>Pembayaran Servis Motor</small></div>
    <div class="user">
      <span><?= e($_SESSION['nama']) ?> <span class="peran"><?= e($_SESSION['role']) ?></span></span>
      <a class="btn btn-garis btn-kecil" href="logout.php">Keluar</a>
    </div>
  </div>
</header>

<main class="wrap">
  <?php if ($sukses): ?>
    <div class="pesan sukses" role="status">Transaksi berhasil disimpan. Nota <?= e($sukses) ?> siap dicetak.</div>
  <?php endif; ?>

  <section class="ringkasan" aria-label="Ringkasan">
    <div class="card"><div class="label">Transaksi hari ini</div><div class="nilai"><?= (int)$hari['jml'] ?></div></div>
    <div class="card"><div class="label">Pendapatan hari ini</div><div class="nilai"><?= rupiah($hari['total']) ?></div></div>
    <div class="card"><div class="label">Seluruh transaksi</div><div class="nilai"><?= (int)$ring['jml'] ?> &middot; <?= rupiah($ring['total']) ?></div></div>
    <div class="card"><div class="label">Sparepart stok &le; 5</div><div class="nilai"><?= $stokMenipis ?> item</div></div>
  </section>

  <section class="card">
    <div class="judul-bar">
      <h1>Riwayat transaksi</h1>
      <a class="btn btn-utama" href="transaksi.php">Tambah transaksi</a>
    </div>

    <form method="get" class="judul-bar" style="margin-bottom:1rem">
      <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Cari nama, nomor polisi, atau nomor nota" aria-label="Cari transaksi" style="max-width:380px">
      <div>
        <button class="btn btn-kecil" type="submit">Cari</button>
        <?php if ($cari !== ''): ?><a class="btn btn-garis btn-kecil" href="dashboard.php">Reset</a><?php endif; ?>
      </div>
    </form>

    <div class="tabel-scroll">
      <table>
        <thead>
          <tr>
            <th>No. Nota</th><th>Tanggal</th><th>Pelanggan</th><th>No. Polisi</th>
            <th>Servis</th><th>Sparepart</th><th class="angka">Total Bayar</th><th>Nota</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$data): ?>
          <tr><td colspan="8" class="kosong">Belum ada transaksi<?= $cari !== '' ? ' yang cocok dengan pencarian' : '' ?>. Klik "Tambah transaksi" untuk mulai.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $r): ?>
          <tr>
            <td><?= e($r['no_nota']) ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($r['tanggal']))) ?></td>
            <td><?= e($r['nama_pelanggan']) ?></td>
            <td><?= e($r['no_polisi']) ?></td>
            <td><?= e($r['jenis_servis']) ?></td>
            <td><?= $r['nama_sparepart'] ? e($r['nama_sparepart']) . ' &times; ' . (int)$r['jumlah_sparepart'] : '-' ?></td>
            <td class="angka"><strong><?= rupiah($r['total_bayar']) ?></strong></td>
            <td><a class="btn btn-kecil" href="nota.php?id=<?= (int)$r['id_transaksi'] ?>" target="_blank" rel="noopener">Cetak nota</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>
