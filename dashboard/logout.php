<?php
/**
 * Logout - Menghancurkan session di semua aplikasi
 * File: logout.php
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/activity_log.php';

// Log logout activity (mendukung logout manual atau timeout)
if (isLoggedIn()) {
    // Tentukan jenis aktivitas berdasarkan parameter URL 'reason'
    $reason   = $_GET['reason'] ?? 'logout';
    $activity = ($reason === 'timeout') ? LOG_TIMEOUT : LOG_LOGOUT;

    logUserActivity((int) $_SESSION['user_id'], $activity, [
        'app_key'     => 'dashboard',
        'app_name'    => 'Login Portal',
        'description' => ($activity === LOG_TIMEOUT)
            ? 'Sesi berakhir karena tidak ada aktivitas'
            : 'Logout dari portal dashboard',
        'target_url'  => '/dashboard/',
    ]);
}

// Hancurkan session menggunakan helper
destroySession();

// Redirect ke halaman login dengan membawa status (opsional untuk menampilkan pesan)
$redirectReason = $_GET['reason'] ?? 'manual';
header('Location: /dashboard/?status=logged_out&reason=' . $redirectReason);
exit;
