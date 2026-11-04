<?php
declare(strict_types=1);

// ===== DB pengguna (tabel users, DB terpisah, server sama) =====
define('DB_USERS_HOST', 'localhost');
define('DB_USERS_NAME', 'ljucoid_dms');          // ← sesuaikan
define('DB_USERS_USER', 'ljucoid_cms');         // ← sesuaikan
define('DB_USERS_PASS', 'icikiwir2026');

// ===== DB aplikasi content manager =====
define('DB_APP_HOST', 'localhost');
define('DB_APP_NAME', 'ljucoid_cms');     // ← sesuaikan
define('DB_APP_USER', 'ljucoid_cms');           // ← sesuaikan
define('DB_APP_PASS', 'icikiwir2026');

define('BASE_URL', 'https://lju.co.id/admin');      // folder aplikasi
define('APP_NAME',  'Website Content Manager');
define('UPLOAD_DIR', __DIR__ . '/../images/berita');
define('UPLOAD_BASE', __DIR__ . '/../images');
define('NEWS_DETAIL_URL', 'https://lju.co.id/news_detail.php?id='); // link detail pada snippet

function pdo(string $host, string $name, string $user, string $pass): PDO {
    return new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}
function pdo_users(): PDO { static $p; return $p ??= pdo(DB_USERS_HOST, DB_USERS_NAME, DB_USERS_USER, DB_USERS_PASS); }
function pdo_app():   PDO { static $p; return $p ??= pdo(DB_APP_HOST,   DB_APP_NAME,   DB_APP_USER,   DB_APP_PASS); }
