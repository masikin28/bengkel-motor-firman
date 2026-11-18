<?php
/**
 * nota.php - Struk pembayaran untuk printer thermal (lebar 58 mm)
 */
require 'koneksi.php';
wajib_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT t.*, s.nama_sparepart, s.kode_sparepart, a.nama_lengkap AS kasir
                       FROM transaksi t
                       LEFT JOIN sparepart s ON s.id_sparepart = t.id_sparepart
                       LEFT JOIN admin a ON a.id_admin = t.id_admin
                       WHERE t.id_transaksi = ?');
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) {
    http_response_code(404);
    exit('Nota tidak ditemukan.');
}

$subSparepart = (int)$t['harga_sparepart'] * (int)$t['jumlah_sparepart'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nota <?= e($t['no_nota']) ?></title>
<style>
  @page { size: 58mm auto; margin: 0; }
  body { margin: 0; background: #e5e8eb; font-family: "Courier New", monospace; color: #000; }
  .struk { width: 58mm; margin: 12px auto; padding: 3mm; background: #fff; font-size: 11px; line-height: 1.35; }
  .tengah { text-align: center; }
  .nama { font-weight: 700; font-size: 14px; }
  .garis { border-top: 1px dashed #000; margin: 5px 0; }
  .baris { display: flex; justify-content: space-between; gap: 6px; }
  .baris span:last-child { text-align: right; white-space: nowrap; }
  .tebal { font-weight: 700; }
  .total { font-size: 13px; font-weight: 700; }
  .tombol { width: 58mm; margin: 0 auto 16px; display: flex; gap: 6px; font-family: "Segoe UI", Arial, sans-serif; }
  .tombol button, .tombol a { flex: 1; padding: 8px; border: 0; border-radius: 6px; text-align: center;
    background: #1c2a36; color: #fff; font-size: 13px; text-decoration: none; cursor: pointer; }
  .tombol a { background: #f2b705; color: #1c2a36; }
  @media print {
    body { background: #fff; }
    .struk { margin: 0; width: 58mm; }
    .tombol { display: none; }
  }
</style>
</head>
<body>
<div class="struk">
  <div class="tengah">
    <div class="nama">BENGKEL MOTOR FIRMAN</div>
    <div>Servis &amp; Suku Cadang Motor</div>
  </div>
  <div class="garis"></div>
  <div class="baris"><span>No. Nota</span><span><?= e($t['no_nota']) ?></span></div>
  <div class="baris"><span>Tanggal</span><span><?= e(date('d/m/Y H:i', strtotime($t['tanggal']))) ?></span></div>
  <div class="baris"><span>Kasir</span><span><?= e($t['kasir'] ?? '-') ?></span></div>
  <div class="garis"></div>
  <div class="baris"><span>Pelanggan</span><span><?= e($t['nama_pelanggan']) ?></span></div>
  <div class="baris"><span>No. Polisi</span><span><?= e($t['no_polisi']) ?></span></div>
  <div class="garis"></div>

  <div class="tebal">Servis / Kerusakan</div>
  <div><?= e($t['jenis_servis']) ?></div>
  <div class="baris"><span>Biaya jasa</span><span><?= rupiah($t['biaya_jasa']) ?></span></div>

  <?php if ($t['jumlah_sparepart'] > 0): ?>
    <div class="garis"></div>
    <div class="tebal">Suku Cadang</div>
    <div><?= e($t['nama_sparepart'] ?? 'Sparepart') ?></div>
    <div class="baris">
      <span><?= (int)$t['jumlah_sparepart'] ?> x <?= rupiah($t['harga_sparepart']) ?></span>
      <span><?= rupiah($subSparepart) ?></span>
    </div>
  <?php endif; ?>

  <div class="garis"></div>
  <div class="baris total"><span>TOTAL BAYAR</span><span><?= rupiah($t['total_bayar']) ?></span></div>
  <div class="garis"></div>
  <div class="tengah">Terima kasih atas kunjungan Anda.<br>Simpan nota ini sebagai bukti pembayaran.</div>
</div>

<div class="tombol">
  <button type="button" onclick="window.print()">Cetak</button>
  <a href="dashboard.php<?= isset($_GET['baru']) ? '?sukses=' . urlencode($t['no_nota']) : '' ?>">Kembali</a>
</div>
<?php if (isset($_GET['baru'])): ?>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
<?php endif; ?>
</body>
</html>
