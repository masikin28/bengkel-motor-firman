<?php
/**
 * index.php - Halaman login (Admin / Kasir)
 */
require 'koneksi.php';

if (!empty($_SESSION['id_admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT id_admin, nama_lengkap, username, password, role FROM admin WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['id_admin'] = (int)$user['id_admin'];
            $_SESSION['nama']     = $user['nama_lengkap'];
            $_SESSION['role']     = $user['role'];
            unset($_SESSION['csrf']);
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Bengkel Motor Firman</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
  <main class="login-card">
    <h1>Bengkel Motor Firman</h1>
    <p>Masuk untuk mengelola pembayaran servis.</p>

    <?php if ($error): ?>
      <div class="pesan error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div style="margin-bottom:1rem">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div style="margin-bottom:1rem">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-utama">Masuk</button>
    </form>

    <div class="akun-uji">
      Akun uji: <strong>admin</strong> / admin123 (Admin) &nbsp;|&nbsp; <strong>kasir</strong> / kasir123 (Kasir).
      Hapus keterangan ini saat sistem dipakai sungguhan.
    </div>
  </main>
</body>
</html>
