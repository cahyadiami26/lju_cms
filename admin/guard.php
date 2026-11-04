<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_name('LJU_APPS');
ini_set('session.cookie_domain', '.lju.co.id');
session_set_cookie_params([
    'lifetime' => 86400,
    'path'     => '/',
    'domain'   => '.lju.co.id',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    // Re-validasi ke DB users — jangan percaya isi session mentah
    $st = pdo_users()->prepare(
        "SELECT id, name, username, email, role, division, department, position, avatar, is_active
         FROM users WHERE id = ? LIMIT 1");
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch();
            $_SESSION['user_id']    = $u['id'];
            $_SESSION['user_nik']   = $u['nik'];
            $_SESSION['username']   = $u['username'];
            $_SESSION['user_name']  = $u['name'];
            $_SESSION['user_email'] = $u['email'];
            $_SESSION['role']       = $u['role'];
            $_SESSION['position']   = $u['position'];
            $_SESSION['division']   = $u['division'];
            $_SESSION['department'] = $u['department'];
            $_SESSION['avatar']     = $u['avatar'];
            $_SESSION['photo']      = $u['photo'];
            $_SESSION['signature']  = $u['signature'];
            $_SESSION['rfid']       = $u['rfid'];
            $_SESSION['webmail_user'] = $u['webmail_user'];
    return ($u && (int)$u['is_active'] === 1) ? $u : null;
}

function require_user(): array {
    $u = current_user();
    if (!$u) { header('Location: https://lju.co.id/dashboard'); exit; }
    if (strcasecmp(trim((string)$u['division']), 'Infokom') !== 0) {
        http_response_code(403);
        exit('<!doctype html><meta charset="utf-8"><body style="font-family:Arial,sans-serif;background:#f4f6fa;display:grid;place-items:center;height:100vh;margin:0"><div style="text-align:center"><h1 style="color:#0d2b4e;margin:0">403</h1><p style="color:#64748b">Aplikasi ini hanya dapat diakses oleh Divisi Infokom.</p></div></body>');
    }
    return $u;
}

function is_admin(array $u): bool { return ($u['role'] ?? '') === 'admin'; }
function is_infokom(array $u): bool { return ($u['division'] ?? '') === 'Infokom'; }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string)$sent)) {
        http_response_code(419);
        exit('CSRF token tidak valid. Muat ulang halaman.');
    }
}

/* ---------- Helper ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function flash(?string $msg = null, string $type = 'success'): void {
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return; }
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

function upload_dir(string $sub): string { return UPLOAD_BASE . '/' . $sub; }

// save_image: tambah param folder, tetap return NAMA FILE saja
function save_image(array $file, string $prefix, string $sub = 'berita'): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal.');
    if ($file['size'] > 3 * 1024 * 1024) throw new RuntimeException('Ukuran maksimal 3 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Format harus JPG, PNG, atau WebP.');
    $dir = upload_dir($sub);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = $prefix . '-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Gagal menyimpan file.');
    }
    return $name;
}

function delete_upload(?string $name, string $sub = 'berita'): void {
    if (!$name) return;
    $real = realpath(upload_dir($sub) . '/' . basename($name));
    $dir  = realpath(upload_dir($sub));
    if ($real && $dir && str_starts_with($real, $dir) && is_file($real)) @unlink($real);
}

function uploads_url(?string $file, string $sub = 'berita'): string {
    return $file ? BASE_URL . '/images/' . $sub . '/' . rawurlencode($file) : '';
}

function slugify(string $t): string {
    $t = strtolower(trim($t));
    $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
    return trim($t, '-') ?: 'berita';
}

function unique_slug(string $base, int $ignoreId = 0): string {
    $slug = $base; $i = 1;
    $st = pdo_app()->prepare("SELECT id FROM berita WHERE slug = ? AND id != ? LIMIT 1");
    while (true) {
        $st->execute([$slug, $ignoreId]);
        if (!$st->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}
