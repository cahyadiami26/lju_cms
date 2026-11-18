<?php
/**
 * Helper Pencatatan Aktivitas User (tabel login_logs)
 * File: config/activity_log.php
 *
 * Semua aktivitas penting user portal dicatat di sini:
 *   - proses login / gagal login / logout / timeout
 *   - klik tombol aplikasi (Presensi, Naskah Dinas, Arsip, Pengadaan, dst.)
 *   - membuka dashboard
 *   - update profil
 *   - percobaan akses aplikasi yang tidak diizinkan
 *
 * Kolom yang dipakai:
 *   user_id, activity, app_key, app_name, description, target_url,
 *   ip_address, user_agent, created_at
 *
 * Jalankan migrate_login_logs.php (atau login_logs_schema.sql) satu kali agar
 * kolom app_key/app_name/description/target_url tersedia. Jika migrasi belum
 * dijalankan, helper ini otomatis memakai format lama (tanpa error fatal),
 * sehingga portal tetap berjalan normal.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/apps.php';

/* Nilai activity yang dipakai di seluruh portal */
if (!defined('LOG_LOGIN_SUCCESS'))        define('LOG_LOGIN_SUCCESS', 'login_success');
if (!defined('LOG_LOGIN_GOOGLE_SUCCESS')) define('LOG_LOGIN_GOOGLE_SUCCESS', 'login_google_success');
if (!defined('LOG_LOGIN_FAILED'))         define('LOG_LOGIN_FAILED', 'login_failed');
if (!defined('LOG_LOGOUT'))               define('LOG_LOGOUT', 'logout');
if (!defined('LOG_TIMEOUT'))              define('LOG_TIMEOUT', 'timeout');
if (!defined('LOG_DASHBOARD'))            define('LOG_DASHBOARD', 'open_dashboard');
if (!defined('LOG_UPDATE_PROFILE'))       define('LOG_UPDATE_PROFILE', 'update_profile');
if (!defined('LOG_ACCESS_DENIED'))        define('LOG_ACCESS_DENIED', 'access_denied');

/**
 * User yang aktivitasnya TIDAK dicatat ke login_logs.
 * Default: user_id = 1 (akun Administrator sistem).
 *
 * Ubah daftar ini bila ada akun lain yang juga ingin dikecualikan,
 * contoh: define('ACTIVITY_LOG_EXCLUDED_USER_IDS', [1, 12, 15]);
 */
define('ACTIVITY_LOG_EXCLUDED_USER_IDS', [
    1,  // Administrator
    35,  // dummy
    36,
    37,
    38
]);

/**
 * Kode aktivitas yang tetap dicatat walaupun user ada di daftar pengecualian.
 * Kosong (default) = seluruh aktivitas user tersebut diabaikan, termasuk
 * login dan logout.
 *
 * Bila login/logout Administrator masih ingin terekam untuk audit, ubah menjadi:
 * define('ACTIVITY_LOG_EXCLUDED_ALLOW', ['login_success', 'login_google_success', 'logout', 'timeout']);
 */
if (!defined('ACTIVITY_LOG_EXCLUDED_ALLOW')) {
    define('ACTIVITY_LOG_EXCLUDED_ALLOW', []);
}

/**
 * Cek apakah aktivitas user ini dikecualikan dari pencatatan.
 */
function isActivityLogExcluded(?int $userId, string $activity = ''): bool
{
    if ($userId === null || $userId <= 0) {
        return false;
    }

    $excluded = (array) ACTIVITY_LOG_EXCLUDED_USER_IDS;
    if (!in_array($userId, array_map('intval', $excluded), true)) {
        return false;
    }

    // Aktivitas yang tetap diizinkan walau user dikecualikan
    $allowed = (array) ACTIVITY_LOG_EXCLUDED_ALLOW;

    return !in_array($activity, $allowed, true);
}

/**
 * Ambil IP address client.
 */
function getLogClientIp(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

/**
 * Ambil user agent client.
 */
function getLogUserAgent(): string
{
    return (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
}

/**
 * Catat satu event aktivitas ke tabel login_logs.
 *
 * @param int|null $userId   users.id (NULL untuk login gagal tanpa user terdaftar)
 * @param string   $activity kode aktivitas, mis. login_success / open_dms
 * @param array    $options  app_key, app_name, description, target_url
 * @return bool true jika log tersimpan
 */
function logUserActivity(?int $userId, string $activity, array $options = []): bool
{
    $activity = trim($activity);
    if ($activity === '') {
        return false;
    }
    $activity = mb_substr($activity, 0, 50);

    // Akun yang dikecualikan (default: Administrator user_id = 1)
    if (isActivityLogExcluded($userId, $activity)) {
        return false;
    }

    $appKey      = isset($options['app_key']) && $options['app_key'] !== '' ? mb_substr((string) $options['app_key'], 0, 50) : null;
    $appName     = isset($options['app_name']) && $options['app_name'] !== '' ? mb_substr((string) $options['app_name'], 0, 100) : null;
    $description = isset($options['description']) && $options['description'] !== '' ? mb_substr((string) $options['description'], 0, 255) : null;
    $targetUrl   = isset($options['target_url']) && $options['target_url'] !== '' ? mb_substr((string) $options['target_url'], 0, 255) : null;

    $ip = getLogClientIp();
    // Batasi 255 karakter agar tetap cocok dengan kolom user_agent versi lama
    $ua = mb_substr(getLogUserAgent(), 0, 255);

    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            INSERT INTO login_logs
                (user_id, activity, app_key, app_name, description, target_url, ip_address, user_agent, created_at)
            VALUES
                (:user_id, :activity, :app_key, :app_name, :description, :target_url, :ip, :ua, NOW())
        ");
        $stmt->execute([
            'user_id'     => ($userId !== null && $userId > 0) ? $userId : null,
            'activity'    => $activity,
            'app_key'     => $appKey,
            'app_name'    => $appName,
            'description' => $description,
            'target_url'  => $targetUrl,
            'ip'          => $ip,
            'ua'          => $ua,
        ]);

        return true;
    } catch (\Throwable $e) {
        // Skema lama (belum dimigrasi): ulangi dengan format kolom lama agar
        // aktivitas user tidak hilang dan halaman tidak error.
        error_log(
            'Activity Log Error: ' . $e->getMessage() .
            ' | Jalankan migrate_login_logs.php untuk mengaktifkan detail aktivitas aplikasi.'
        );
    }

    return logUserActivityLegacy($userId, $activity, $ip, $ua);
}

/**
 * Fallback untuk instalasi yang tabel login_logs-nya belum dimigrasi.
 * Hanya menulis kolom lama (user_id, activity, ip_address, user_agent).
 */
function logUserActivityLegacy(?int $userId, string $activity, string $ip, string $ua): bool
{
    // Kolom activity lama bisa berupa ENUM terbatas.
    $allowed = ['login_success', 'login_failed', 'login_nfc_success', 'login_google_success', 'logout', 'timeout'];
    if (!in_array($activity, $allowed, true)) {
        $activity = strpos($activity, 'open_') === 0 ? 'login_success' : 'login_success';
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, activity, ip_address, user_agent, created_at)
            VALUES (:user_id, :activity, :ip, :ua, NOW())
        ");
        $stmt->execute([
            'user_id'  => ($userId !== null && $userId > 0) ? $userId : null,
            'activity' => $activity,
            'ip'       => $ip,
            'ua'       => mb_substr($ua, 0, 255),
        ]);

        return true;
    } catch (\Throwable $e) {
        error_log('Activity Log Legacy Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Catat aktivitas membuka aplikasi (dipanggil dari go.php / SSO redirect).
 *
 * @param string   $appKey  key aplikasi pada config/apps.php
 * @param array    $app     definisi aplikasi
 * @param int|null $userId  default: user pada session
 * @param string   $source  keterangan tambahan, mis. 'Klik kartu dashboard'
 */
function logAppOpen(string $appKey, array $app, ?int $userId = null, string $source = ''): bool
{
    $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    $appName = (string) ($app['name'] ?? $appKey);
    $label   = 'Membuka ' . $appName;
    if ($source !== '') {
        $label .= ' (' . $source . ')';
    }

    $ok = logUserActivity($userId, 'open_' . $appKey, [
        'app_key'     => $appKey,
        'app_name'    => $appName,
        'description' => $label,
        'target_url'  => (string) ($app['url'] ?? ''),
    ]);

    if ($ok) {
        markActivityThrottle('open_' . $appKey);
    }

    return $ok;
}

/**
 * Catat event tertentu maksimal sekali per $ttlSeconds per session.
 * Berguna untuk halaman yang mudah ter-refresh (mis. dashboard).
 */
function logEventOnce(string $activity, int $ttlSeconds, array $options = [], ?int $userId = null): bool
{
    $now = time();
    $key = $activity;

    if (isset($_SESSION['activity_log_throttle'][$key])) {
        if (($now - (int) $_SESSION['activity_log_throttle'][$key]) < $ttlSeconds) {
            return false;
        }
    }

    $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }

    $ok = logUserActivity($userId, $activity, $options);
    if ($ok) {
        markActivityThrottle($key);
    }

    return $ok;
}

/**
 * Simpan waktu terakhir sebuah event dicatat (untuk throttle).
 */
function markActivityThrottle(string $key): void
{
    $_SESSION['activity_log_throttle'][$key] = time();
}

/**
 * Catat kunjungan dashboard (maksimal sekali per 15 menit per session).
 */
function logDashboardView(?int $userId = null): bool
{
    return logEventOnce(
        LOG_DASHBOARD,
        900,
        [
            'app_key'     => 'dashboard',
            'app_name'    => 'Dashboard Portal',
            'description' => 'Membuka dashboard portal',
            'target_url'  => '/dashboard/',
        ],
        $userId
    );
}

/**
 * Daftar label aktivitas yang dipakai pada laporan.
 */
function getActivityCatalog(): array
{
    $catalog = [
        'login_success'        => ['label' => 'Login berhasil',      'icon' => '🔐', 'color' => '#059669'],
        'login_google_success' => ['label' => 'Login via Google',    'icon' => '🔐', 'color' => '#0284c7'],
        'login_nfc_success'    => ['label' => 'Login via NFC',       'icon' => '🔐', 'color' => '#0891b2'],
        'login_failed'         => ['label' => 'Login gagal',         'icon' => '⛔', 'color' => '#dc2626'],
        'logout'               => ['label' => 'Logout',              'icon' => '🚪', 'color' => '#64748b'],
        'timeout'              => ['label' => 'Sesi berakhir (idle)', 'icon' => '⏰', 'color' => '#d97706'],
        'open_dashboard'       => ['label' => 'Membuka Dashboard',   'icon' => '🏠', 'color' => '#1e40af'],
        'update_profile'       => ['label' => 'Update profil',       'icon' => '✏️', 'color' => '#7c3aed'],
        'access_denied'        => ['label' => 'Akses ditolak',       'icon' => '🔒', 'color' => '#dc2626'],
    ];

    foreach (getAppsConfig() as $key => $app) {
        // Aplikasi "hidden" (mis. Presensi) tetap didaftarkan agar log punya label.
        $catalog['open_' . $key] = [
            'label' => 'Membuka ' . ($app['name'] ?? $key),
            'icon'  => $app['emoji'] ?? '💻',
            'color' => $app['color'] ?? '#1e40af',
        ];
    }

    return $catalog;
}

/**
 * Ambil metadata (label, icon, color) sebuah kode aktivitas.
 */
function getActivityMeta(?string $activity, ?string $appName = null): array
{
    $catalog = getActivityCatalog();
    $activity = (string) $activity;

    if (isset($catalog[$activity])) {
        $meta = $catalog[$activity];
        if ($activity === 'open_dashboard' && $appName) {
            $meta['label'] = 'Membuka ' . $appName;
        }
        return $meta;
    }

    // Activity open_* lama yang aplikasinya sudah tidak ada di config
    if (strpos($activity, 'open_') === 0) {
        return [
            'label' => $appName ? ('Membuka ' . $appName) : 'Membuka aplikasi',
            'icon'  => '💻',
            'color' => '#1e40af',
        ];
    }

    return [
        'label' => $appName ?: ucfirst(str_replace('_', ' ', $activity)),
        'icon'  => '•',
        'color' => '#475569',
    ];
}

/**
 * Bangun klausa WHERE untuk filter laporan aktivitas.
 *
 * @return array{0: string, 1: array<string, mixed>}
 */
function buildActivityLogWhere(array $filters): array
{
    $where  = [];
    $params = [];

    $user = trim((string) ($filters['user'] ?? ''));
    if ($user !== '') {
        $cond = '(u.username LIKE :user_like OR u.name LIKE :user_like2';
        $params['user_like']  = '%' . $user . '%';
        $params['user_like2'] = '%' . $user . '%';
        if (ctype_digit($user)) {
            $cond .= ' OR l.user_id = :user_id';
            $params['user_id'] = (int) $user;
        }
        $where[] = $cond . ')';
    }

    $app = trim((string) ($filters['app'] ?? ''));
    if ($app !== '') {
        $where[] = 'l.app_key = :app_key';
        $params['app_key'] = $app;
    }

    $activity = trim((string) ($filters['activity'] ?? ''));
    if ($activity !== '') {
        $where[] = 'l.activity = :activity';
        $params['activity'] = $activity;
    }

    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'l.created_at >= :date_from';
        $params['date_from'] = $dateFrom . ' 00:00:00';
    }

    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'l.created_at <= :date_to';
        $params['date_to'] = $dateTo . ' 23:59:59';
    }

    return [$where ? ('WHERE ' . implode(' AND ', $where)) : '', $params];
}

/**
 * Ambil daftar log aktivitas (dengan filter & pagination).
 * Mengembalikan ['rows' => [], 'schema_ready' => bool]
 */
function getActivityLogs(array $filters, int $limit = 50, int $offset = 0): array
{
    $limit  = max(1, min(500, $limit));
    $offset = max(0, $offset);

    [$where, $params] = buildActivityLogWhere($filters);

    $sql = "
        SELECT l.id, l.user_id, l.activity, l.app_key, l.app_name, l.description,
               l.target_url, l.ip_address, l.user_agent, l.created_at,
               u.username, u.name AS user_name, u.position, u.division, u.avatar
        FROM login_logs l
        LEFT JOIN users u ON u.id = l.user_id
        {$where}
        ORDER BY l.created_at DESC, l.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ['rows' => $stmt->fetchAll() ?: [], 'schema_ready' => true];
    } catch (\Throwable $e) {
        error_log('Activity Log Query Error: ' . $e->getMessage());

        // Fallback skema lama
        $sql = "
            SELECT l.id, l.user_id, l.activity, NULL AS app_key, NULL AS app_name, NULL AS description,
                   NULL AS target_url, l.ip_address, l.user_agent, l.created_at,
                   u.username, u.name AS user_name, u.position, u.division, u.avatar
            FROM login_logs l
            LEFT JOIN users u ON u.id = l.user_id
            {$where}
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        try {
            $pdo  = getDBConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return ['rows' => $stmt->fetchAll() ?: [], 'schema_ready' => false];
        } catch (\Throwable $e2) {
            error_log('Activity Log Query Fallback Error: ' . $e2->getMessage());
            return ['rows' => [], 'schema_ready' => false];
        }
    }
}

/**
 * Hitung total baris log sesuai filter (untuk pagination).
 */
function countActivityLogs(array $filters): int
{
    [$where, $params] = buildActivityLogWhere($filters);

    $sql = "
        SELECT COUNT(*) AS total
        FROM login_logs l
        LEFT JOIN users u ON u.id = l.user_id
        {$where}
    ";

    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int) $row['total'] : 0;
    } catch (\Throwable $e) {
        error_log('Activity Log Count Error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Statistik ringkas untuk halaman laporan aktivitas.
 */
function getActivityLogStats(): array
{
    $stats = [
        'today_total'   => 0,
        'today_logins'  => 0,
        'today_users'   => 0,
        'today_denied'  => 0,
        'today_app_open' => 0,
        'top_apps'      => [],
    ];

    try {
        $pdo = getDBConnection();

        $row = $pdo->query("
            SELECT
                COUNT(*) AS today_total,
                SUM(CASE WHEN activity IN ('login_success','login_google_success','login_nfc_success') THEN 1 ELSE 0 END) AS today_logins,
                SUM(CASE WHEN activity = 'access_denied' THEN 1 ELSE 0 END) AS today_denied,
                COUNT(DISTINCT user_id) AS today_users
            FROM login_logs
            WHERE DATE(created_at) = CURDATE()
        ")->fetch();

        if ($row) {
            $stats['today_total']  = (int) $row['today_total'];
            $stats['today_logins'] = (int) $row['today_logins'];
            $stats['today_denied'] = (int) $row['today_denied'];
            $stats['today_users']  = (int) $row['today_users'];
        }
    } catch (\Throwable $e) {
        error_log('Activity Log Stats Error: ' . $e->getMessage());
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT app_key, MAX(app_name) AS app_name, COUNT(*) AS total
            FROM login_logs
            WHERE DATE(created_at) = CURDATE()
              AND activity LIKE 'open\\_%'
              AND app_key IS NOT NULL
              AND app_key <> 'dashboard'
            GROUP BY app_key
            ORDER BY total DESC
            LIMIT 5
        ");
        $stats['top_apps'] = $stmt->fetchAll() ?: [];
    } catch (\Throwable $e) {
        // Skema lama belum punya app_key — abaikan.
    }

    return $stats;
}

/**
 * Jumlah login per pengguna pada rentang tanggal tertentu.
 *
 * Yang dihitung: login_success, login_google_success, login_nfc_success.
 * User yang terdaftar di ACTIVITY_LOG_EXCLUDED_USER_IDS (default: Administrator)
 * otomatis tidak diikutkan.
 *
 * @param string $dateFrom YYYY-MM-DD
 * @param string $dateTo   YYYY-MM-DD
 * @param int    $limit    jumlah pengguna teratas yang dikembalikan
 * @return array{rows: array, total_users: int, total_logins: int}
 */
function getLoginCountsByUser(string $dateFrom, string $dateTo, int $limit = 10): array
{
    $result = [
        'rows'         => [],
        'total_users'  => 0,
        'total_logins' => 0,
    ];

    $params = [];
    $where  = [
        "l.activity IN ('login_success', 'login_google_success', 'login_nfc_success')",
        'l.user_id IS NOT NULL',
    ];

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'l.created_at >= :login_from';
        $params['login_from'] = $dateFrom . ' 00:00:00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'l.created_at <= :login_to';
        $params['login_to'] = $dateTo . ' 23:59:59';
    }

    // Kecualikan akun yang tidak boleh muncul (Administrator, dsb.)
    $excluded = array_values(array_filter(array_map('intval', (array) ACTIVITY_LOG_EXCLUDED_USER_IDS)));
    if ($excluded) {
        $placeholders = [];
        foreach ($excluded as $i => $id) {
            $key = 'excl_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $where[] = 'l.user_id NOT IN (' . implode(', ', $placeholders) . ')';
    }

    $limit = max(1, min(50, $limit));
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    try {
        $pdo = getDBConnection();

        // Total keseluruhan (bukan hanya baris yang ditampilkan)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_logins, COUNT(DISTINCT l.user_id) AS total_users
            FROM login_logs l
            {$whereSql}
        ");
        $stmt->execute($params);
        $sum = $stmt->fetch();
        if ($sum) {
            $result['total_logins'] = (int) $sum['total_logins'];
            $result['total_users']  = (int) $sum['total_users'];
        }

        // Peringkat pengguna
        $stmt = $pdo->prepare("
            SELECT l.user_id, u.name, u.username, u.position, u.division,
                   COUNT(*) AS total
            FROM login_logs l
            LEFT JOIN users u ON u.id = l.user_id
            {$whereSql}
            GROUP BY l.user_id, u.name, u.username, u.position, u.division
            ORDER BY total DESC, u.name ASC, l.user_id ASC
            LIMIT {$limit}
        ");
        $stmt->execute($params);
        $result['rows'] = $stmt->fetchAll() ?: [];
    } catch (\Throwable $e) {
        error_log('Login Per User Error: ' . $e->getMessage());
    }

    return $result;
}
