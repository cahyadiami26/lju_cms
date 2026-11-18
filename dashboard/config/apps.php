<?php
/**
 * Konfigurasi Aplikasi & URL Whitelist
 * File: config/apps.php
 */

if (!defined('APP_URL_DMS'))       define('APP_URL_DMS',       'https://dms.lju.co.id');
if (!defined('APP_URL_ARSIP'))     define('APP_URL_ARSIP',     'https://dms.lju.co.id/arsip');
if (!defined('APP_URL_WBS'))       define('APP_URL_WBS',       'https://kepo.lju.co.id/dashboard.php');
if (!defined('APP_URL_PM'))        define('APP_URL_PM',        'https://pm.lju.co.id');
if (!defined('APP_URL_PENGADAAN')) define('APP_URL_PENGADAAN', 'https://pengadaan.lju.co.id');
if (!defined('APP_URL_HRD'))       define('APP_URL_HRD',       'https://hris.lju.co.id');
if (!defined('APP_URL_WEBMAIL'))   define('APP_URL_WEBMAIL',   'open_webmail.php'); // Mengarah ke skrip pemroses API cPanel
if (!defined('APP_URL_CMS'))       define('APP_URL_CMS',       'https://lju.co.id/admin');
if (!defined('APP_URL_ASSESSMENT')) define('APP_URL_ASSESSMENT', 'https://assessment.lju.co.id');
if (!defined('APP_URL_PRESENSI'))  define('APP_URL_PRESENSI',  'https://hris.lju.co.id/modules/attendance/checkin.php');
if (!defined('APP_URL_ACTIVITY_LOG')) define('APP_URL_ACTIVITY_LOG', 'activity_logs.php');

if (!function_exists('getAppsConfig')) {
    function getAppsConfig(): array
    {
        $apps = [
            'presensi' => [
                'name'          => 'Presensi Online',
                'description'   => 'Presensi kehadiran karyawan harian (HRIS)',
                'url'           => APP_URL_PRESENSI,
                'icon_active'   => 'assets/images/apps/hrd-active.png',
                'icon_inactive' => 'assets/images/apps/hrd-inactive.png',
                'color'         => '#2563eb',
                'color_light'   => '#dbeafe',
                'emoji'         => '🕒',
                'active'        => true,
                'order'         => 0,
                // Tampil sebagai tombol cepat di topbar, bukan kartu di grid
                'hidden'        => true,
            ],
            'dms' => [
                'name'          => 'Naskah Dinas',
                'description'   => 'Sistem pengelolaan naskah dinas digital',
                'url'           => APP_URL_DMS,
                'icon_active'   => 'assets/images/apps/dms-active.png',
                'icon_inactive' => 'assets/images/apps/dms-inactive.png',
                'color'         => '#2563eb',
                'color_light'   => '#dbeafe',
                'emoji'         => '📄',
                'active'        => true,
                'order'         => 1,
            ],
            'arsip' => [
                'name'          => 'Pengarsipan',
                'description'   => 'Sistem pengarsipan dokumen digital',
                'url'           => APP_URL_ARSIP,
                'icon_active'   => 'assets/images/apps/arsip-active.png',
                'icon_inactive' => 'assets/images/apps/arsip-inactive.png',
                'color'         => '#059669',
                'color_light'   => '#d1fae5',
                'emoji'         => '📁',
                'active'        => true,
                'order'         => 2,
            ],
            'webmail' => [
                'name'          => 'Webmail Korporat',
                'description'   => 'Akses surat elektronik perusahaan langsung tanpa login ulang',
                'url'           => APP_URL_WEBMAIL,
                'icon_active'   => 'assets/img/icon_webmail.png',
                'icon_inactive' => 'assets/img/icon_webmail.png',
                'color'         => '#0284c7',
                'color_light'   => '#e0f2fe',
                'emoji'         => '✉️',
                'active'        => true,
                'order'         => 3,
            ],
            'wbs' => [
                'name'          => 'Whistleblower',
                'description'   => 'Sistem pelaporan whistleblower (WBS)',
                'url'           => APP_URL_WBS,
                'icon_active'   => 'assets/images/apps/wbs-active.png',
                'icon_inactive' => 'assets/images/apps/wbs-inactive.png',
                'color'         => '#dc2626',
                'color_light'   => '#fee2e2',
                'emoji'         => '📢',
                'active'        => true,
                'order'         => 4,
            ],
            'pm' => [
                'name'          => 'Task Management',
                'description'   => 'Manajemen tugas dan proyek operasional',
                'url'           => APP_URL_PM,
                'icon_active'   => 'assets/images/apps/pm-active.png',
                'icon_inactive' => 'assets/images/apps/pm-inactive.png',
                'color'         => '#7c3aed',
                'color_light'   => '#ede9fe',
                'emoji'         => '📊',
                'active'        => true,
                'order'         => 5,
            ],
            'pengadaan' => [
                'name'          => 'Pengadaan',
                'description'   => 'Sistem Perlengkapan & Pengadaan',
                'url'           => APP_URL_PENGADAAN,
                'icon_active'   => 'assets/images/apps/pengadaan-active.png',
                'icon_inactive' => 'assets/images/apps/pengadaan-inactive.png',
                'color'         => '#ea580c',
                'color_light'   => '#ffedd5',
                'emoji'         => '🛒',
                'active'        => true,
                'order'         => 6,
            ],
            'hrd' => [
                'name'          => 'SDM / HRD',
                'description'   => 'Sistem manajemen sumber daya manusia',
                'url'           => APP_URL_HRD,
                'icon_active'   => 'assets/images/apps/hrd-active.png',
                'icon_inactive' => 'assets/images/apps/hrd-inactive.png',
                'color'         => '#0891b2',
                'color_light'   => '#cffafe',
                'emoji'         => '👥',
                'active'        => true,
                'order'         => 7,
            ],
            'cms' => [
                'name'          => 'WebContent',
                'description'   => 'Sistem input Berita Web',
                'url'           => APP_URL_CMS,
                'icon_active'   => 'assets/images/apps/hrd-active.png',
                'icon_inactive' => 'assets/images/apps/hrd-inactive.png',
                'color'         => '#0891b2',
                'color_light'   => '#cffafe',
                'emoji'         => '🌐',
                'active'        => true,
                'order'         => 8,
            ],
            'assessment' => [
                'name'          => 'Assessment',
                'description'   => 'Assessment Karyawan',
                'url'           => APP_URL_ASSESSMENT,
                'icon_active'   => 'assets/images/apps/hrd-active.png',
                'icon_inactive' => 'assets/images/apps/hrd-inactive.png',
                'color'         => '#0891b2',
                'color_light'   => '#cffafe',
                'emoji'         => '💯',
                'active'        => true,
                'order'         => 10,
            ],
        ];

        // Mengurutkan berdasarkan key 'order' secara otomatis
        uasort($apps, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        return $apps;
    }
}

if (!function_exists('getAppByKey')) {
    /**
     * Ambil definisi satu aplikasi berdasarkan key (mis. 'dms', 'presensi').
     */
    function getAppByKey(string $key): ?array
    {
        $apps = getAppsConfig();
        return $apps[$key] ?? null;
    }
}

if (!function_exists('getAppByUrl')) {
    /**
     * Cari aplikasi berdasarkan URL tujuan (untuk tautan lama /target_system).
     */
    function getAppByUrl(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        foreach (getAppsConfig() as $key => $app) {
            if (($app['url'] ?? '') === $url) {
                return ['key' => $key] + $app;
            }
        }

        // Cocokkan tanpa trailing slash
        $needle = rtrim($url, '/');
        foreach (getAppsConfig() as $key => $app) {
            if (rtrim((string) ($app['url'] ?? ''), '/') === $needle) {
                return ['key' => $key] + $app;
            }
        }

        return null;
    }
}

if (!function_exists('isUserDireksi')) {
    function isUserDireksi(array $user): bool
    {
        $div  = strtolower((string) ($user['division'] ?? ''));
        $dept = strtolower((string) ($user['department'] ?? ''));
        $role = strtolower((string) ($user['role'] ?? ''));

        return $div === 'direksi' || $dept === 'direksi' || $role === 'admin';
    }
}

if (!function_exists('isUserInfokom')) {
    function isUserInfokom(array $user): bool
    {
        return strtolower((string) ($user['division'] ?? '')) === 'infokom';
    }
}

if (!function_exists('getAppAccessState')) {
    /**
     * Tentukan status akses satu aplikasi untuk user.
     *
     * @return array{active: bool, forbidden: bool, badge: string|null, label: string|null, reason: string|null}
     *   badge: 'inactive' | 'direksi' | 'infokom' | null
     */
    function getAppAccessState(string $key, array $user, ?array $app = null): array
    {
        $app = $app ?? (getAppsConfig()[$key] ?? []);

        // Aplikasi nonaktif dari konfigurasi
        if (empty($app['active'])) {
            return [
                'active'    => false,
                'forbidden' => false,
                'badge'     => 'inactive',
                'label'     => 'Segera Hadir',
                'reason'    => 'Aplikasi belum aktif',
            ];
        }

        // WBS hanya untuk Direksi (dan admin)
        if ($key === 'wbs' && !isUserDireksi($user)) {
            return [
                'active'    => false,
                'forbidden' => true,
                'badge'     => 'direksi',
                'label'     => 'Khusus Direksi',
                'reason'    => 'Akses WBS hanya untuk Direksi',
            ];
        }

        // WebContent hanya untuk Infokom
        if ($key === 'cms' && !isUserInfokom($user)) {
            return [
                'active'    => false,
                'forbidden' => false,
                'badge'     => 'infokom',
                'label'     => 'Khusus Infokom',
                'reason'    => 'Akses WebContent hanya untuk Infokom',
            ];
        }

        return [
            'active'    => true,
            'forbidden' => false,
            'badge'     => null,
            'label'     => null,
            'reason'    => null,
        ];
    }
}

if (!function_exists('getAllowedRedirectUrls')) {
    function getAllowedRedirectUrls(): array
    {
        $apps = getAppsConfig();
        return array_values(array_column($apps, 'url'));
    }
}