<?php
/**
 * koneksi.php
 * Koneksi database (PDO) + fungsi bantu yang dipakai semua halaman.
 * Sesuaikan DB_USER / DB_PASS bila MySQL Anda memakai password.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_bengkel_firman');
define('DB_USER', 'root');   // default XAMPP
define('DB_PASS', '');       // default XAMPP: kosong

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Koneksi gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Pastikan MySQL aktif dan database db_bengkel_firman sudah diimpor.');
}

/** Escape output HTML (cegah XSS). */
function e($teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

/** Format angka ke Rupiah, contoh: Rp 73.000 */
function rupiah($angka): string
{
    return 'Rp ' . number_format((int)$angka, 0, ',', '.');
}

/** Wajib login; jika belum, arahkan ke index.php. */
function wajib_login(): void
{
    if (empty($_SESSION['id_admin'])) {
        header('Location: index.php');
        exit;
    }
}

/** Token CSRF untuk form POST. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
