<?php
/**
 * Penghubung Klik Aplikasi (Activity Tracker + Redirect)
 * File: go.php
 *
 * Semua tombol aplikasi di dashboard mengarah ke file ini, contoh:
 *   /dashboard/go.php?app=presensi
 *   /dashboard/go.php?app=dms
 *   /dashboard/go.php?app=pengadaan
 *
 * Alur:
 *   1. Pastikan user sudah login.
 *   2. Validasi key aplikasi + hak akses user.
 *   3. Catat aktivitas "open_<key>" ke tabel login_logs.
 *   4. Redirect user ke aplikasi tujuan.
 *
 * Karena pencatatan terjadi di server, log tetap tercatat walau user membuka
 * tautan di tab baru, menyalin URL, atau memakai klik tengah mouse.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/apps.php';
require_once __DIR__ . '/config/activity_log.php';

$appKey = strtolower(trim((string) ($_GET['app'] ?? $_GET['key'] ?? '')));
$app    = $appKey !== '' ? getAppByKey($appKey) : null;

// Kompatibilitas tautan lama: go.php?target_system=https://dms.lju.co.id
if ($app === null && !empty($_GET['target_system'])) {
    $legacy = getAppByUrl((string) $_GET['target_system']);
    if ($legacy !== null) {
        $app    = $legacy;
        $appKey = (string) ($legacy['key'] ?? '');
    }
}

// ------------------------------------------------------------------
// Belum login -> arahkan ke portal dengan target aplikasi
// ------------------------------------------------------------------
if (!isLoggedIn()) {
    $target = $app['url'] ?? '';
    $qs     = $target !== '' ? ('?target_system=' . urlencode($target)) : '';

    header('Location: index.php' . $qs, true, 302);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

// ------------------------------------------------------------------
// Key tidak dikenal -> kembali ke dashboard
// ------------------------------------------------------------------
if ($app === null) {
    header('Location: index.php', true, 302);
    exit;
}

$user   = getSessionUser();
$access = getAppAccessState($appKey, $user, $app);

// ------------------------------------------------------------------
// Tidak berhak -> catat percobaan akses lalu kembali ke dashboard
// ------------------------------------------------------------------
if (!$access['active']) {
    logUserActivity($userId, LOG_ACCESS_DENIED, [
        'app_key'     => $appKey,
        'app_name'    => $app['name'] ?? $appKey,
        'description' => 'Percobaan membuka ' . ($app['name'] ?? $appKey) . ' ditolak: ' . ($access['reason'] ?? 'tidak berhak'),
        'target_url'  => (string) ($app['url'] ?? ''),
    ]);

    header('Location: index.php?denied=' . urlencode($appKey), true, 302);
    exit;
}

// ------------------------------------------------------------------
// Catat aktivitas klik aplikasi
// ------------------------------------------------------------------
logAppOpen($appKey, $app, $userId, 'klik tombol dashboard');

// ------------------------------------------------------------------
// Redirect ke aplikasi tujuan
// ------------------------------------------------------------------
$destination = (string) ($app['url'] ?? '');

if ($destination === '') {
    header('Location: index.php', true, 302);
    exit;
}

header('Location: ' . $destination, true, 302);
exit;
