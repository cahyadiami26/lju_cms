<?php
/**
 * Laporan Log Aktivitas User
 * File: activity_logs.php
 *
 * Menampilkan isi tabel login_logs: login, logout, dashboard, dan klik
 * tombol aplikasi (Presensi, Naskah Dinas, Arsip, Pengadaan, dst).
 *
 * Akses: administrator, Infokom, dan Direksi.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/apps.php';
require_once __DIR__ . '/config/activity_log.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getSessionUser();
if (!isUserDireksi($currentUser) && !isUserInfokom($currentUser) && (($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    exit('Halaman ini hanya dapat diakses administrator.');
}

/* ------------------------------------------------------------------
 * Filter & pagination
 * ------------------------------------------------------------------ */
$today = date('Y-m-d');

$filters = [
    'user'      => trim((string) ($_GET['user'] ?? '')),
    'app'       => trim((string) ($_GET['app'] ?? '')),
    'activity'  => trim((string) ($_GET['activity'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? $today)),
    'date_to'   => trim((string) ($_GET['date_to'] ?? $today)),
];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) { $filters['date_from'] = $today; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to']))   { $filters['date_to']   = $today; }

$perPage = 50;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$result     = getActivityLogs($filters, $perPage, $offset);
$rows       = $result['rows'];
$schemaReady = $result['schema_ready'];
$totalRows  = countActivityLogs($filters);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$stats      = getActivityLogStats();
$catalog    = getActivityCatalog();

/* Pilihan aplikasi (kecuali dashboard) */
$appOptions = [];
foreach (getAppsConfig() as $key => $app) {
    $appOptions[$key] = $app['name'] ?? $key;
}

/* ------------------------------------------------------------------
 * Helper tampilan
 * ------------------------------------------------------------------ */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function activityTone(string $activity): string
{
    if (in_array($activity, ['logout', 'timeout'], true))   return 'neutral';
    if (in_array($activity, ['login_failed', 'access_denied'], true)) return 'negative';
    if (in_array($activity, ['login_success', 'login_google_success', 'login_nfc_success'], true)) return 'positive';

    return 'accent';
}

function userHue(int $userId): int
{
    return (int) (($userId * 47) % 360);
}

function relativeTime(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }

    $diff = time() - $ts;

    if ($diff < 60)      return 'baru saja';
    if ($diff < 3600)    return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)   return floor($diff / 3600) . ' jam lalu';
    if ($diff < 172800)  return 'kemarin';
    if ($diff < 604800)  return floor($diff / 86400) . ' hari lalu';

    return date('d/m/Y', $ts);
}

/* Query string untuk pagination tanpa page */
$qsParams = $_GET;
unset($qsParams['page']);
$baseQs = http_build_query($qsParams);
$baseQs = $baseQs !== '' ? ($baseQs . '&') : '';

$maxTop = 0;
foreach ($stats['top_apps'] as $topApp) {
    $maxTop = max($maxTop, (int) $topApp['total']);
}

/* Kalimat temuan di bawah judul */
if ($stats['today_total'] > 0) {
    $finding = 'Hari ini tercatat ' . number_format($stats['today_total'], 0, ',', '.') . ' aktivitas dari '
        . number_format($stats['today_users'], 0, ',', '.') . ' pengguna.';
    if (!empty($stats['top_apps'])) {
        $finding .= ' Aplikasi yang paling sering dibuka: ' . ($stats['top_apps'][0]['app_name'] ?: $stats['top_apps'][0]['app_key'])
            . ' (' . number_format((int) $stats['top_apps'][0]['total'], 0, ',', '.') . ' kali).';
    }
} else {
    $finding = 'Belum ada aktivitas tercatat hari ini.';
}

$presetToday = $today;
$preset7     = date('Y-m-d', strtotime('-6 days'));
$preset30    = date('Y-m-d', strtotime('-29 days'));

/* ------------------------------------------------------------------
 * Login per pengguna (mengikuti periode filter, bukan filter user/app)
 * ------------------------------------------------------------------ */
$loginUsers = getLoginCountsByUser($filters['date_from'], $filters['date_to'], 10);
$excludedLoginUsers = array_values(array_filter(array_map('intval', (array) ACTIVITY_LOG_EXCLUDED_USER_IDS)));

if ($filters['date_from'] === $presetToday && $filters['date_to'] === $presetToday) {
    $periodLabel = 'Hari ini';
} elseif ($filters['date_from'] === $preset7 && $filters['date_to'] === $presetToday) {
    $periodLabel = '7 hari terakhir';
} elseif ($filters['date_from'] === $preset30 && $filters['date_to'] === $presetToday) {
    $periodLabel = '30 hari terakhir';
} elseif ($filters['date_from'] === $filters['date_to']) {
    $periodLabel = date('d/m/Y', strtotime($filters['date_from']));
} else {
    $periodLabel = date('d/m/Y', strtotime($filters['date_from']))
        . ' &ndash; ' . date('d/m/Y', strtotime($filters['date_to']));
}

$maxUserLogin = 0;
foreach ($loginUsers['rows'] as $loginRow) {
    $maxUserLogin = max($maxUserLogin, (int) $loginRow['total']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - LJU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --ink-soft: #475569;
            --muted: #6b7688;
            --line: #e6ecf3;
            --line-soft: #eef2f7;
            --ground: #f2f6fa;
            --surface: #ffffff;
            --accent: #1e40af;
            --accent-soft: #e8eefc;
            --positive: #047857;
            --negative: #b91c1c;
            --warning: #b45309;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--ground);
            color: var(--ink);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }

        .shell { max-width: 1240px; margin: 0 auto; padding: 22px 24px 48px; }

        /* Top bar */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--line);
        }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand-mark {
            width: 30px; height: 30px; border-radius: 8px;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; letter-spacing: -0.02em;
        }
        .brand-name { font-size: 14px; font-weight: 700; letter-spacing: -0.01em; }
        .brand-name span { color: var(--muted); font-weight: 500; }

        .topbar-actions { display: flex; align-items: center; gap: 8px; }

        .link-btn {
            display: inline-flex; align-items: center; gap: 6px;
            height: 32px; padding: 0 12px;
            border-radius: 8px; border: 1px solid var(--line);
            background: var(--surface); color: var(--ink-soft);
            font-size: 12.5px; font-weight: 600; text-decoration: none;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
            font-family: inherit; cursor: pointer;
        }
        .link-btn:hover { background: #f8fafc; border-color: #d3dce8; color: var(--ink); }
        .link-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        /* Title row */
        .title-row { padding: 26px 0 20px; display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
        h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; line-height: 1.2; }
        .subtitle { margin-top: 6px; font-size: 13.5px; line-height: 1.5; color: var(--muted); max-width: 70ch; }

        /* KPI strip */
        .strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 22px;
        }
        .strip-cell { padding: 16px 18px; border-left: 1px solid var(--line-soft); }
        .strip-cell:first-child { border-left: 0; }
        .strip-label { font-size: 12px; font-weight: 500; color: var(--muted); }
        .strip-value {
            margin-top: 4px; font-size: 26px; font-weight: 600; line-height: 1.15;
            letter-spacing: -0.03em; font-variant-numeric: tabular-nums;
        }
        .strip-value.negative { color: var(--negative); }
        .strip-note { margin-top: 3px; font-size: 11.5px; color: var(--muted); }

        /* Body grid */
        .body-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 312px;
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        .panel-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 14px 18px; border-bottom: 1px solid var(--line-soft);
        }
        .panel-title { font-size: 14px; font-weight: 700; letter-spacing: -0.01em; }
        .panel-meta { font-size: 12px; color: var(--muted); font-variant-numeric: tabular-nums; }

        /* Filters */
        .filters {
            padding: 12px 18px 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
        .field label { font-size: 11px; font-weight: 600; color: var(--muted); }
        .field input, .field select {
            height: 34px;
            padding: 0 10px;
            border: 1px solid #d8e1ec;
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font-size: 12.5px;
            font-family: inherit;
            font-weight: 500;
            min-width: 0;
            width: 100%;
        }
        .field input[type="date"] { padding-right: 6px; }
        .field input:focus, .field select:focus { outline: 2px solid var(--accent); outline-offset: 1px; border-color: var(--accent); }

        .toolbar {
            display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;
            padding: 0 18px 13px;
            border-bottom: 1px solid var(--line-soft);
        }
        .toolbar .field input[type="date"] { width: 142px; }
        .toolbar .dates { display: flex; gap: 10px; }
        .filter-actions { display: flex; gap: 8px; align-items: center; margin-left: auto; }

        .presets { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding-bottom: 9px; }
        .preset { font-size: 12px; font-weight: 600; color: var(--accent); text-decoration: none; }
        .preset:hover { text-decoration: underline; }
        .preset.is-active { color: var(--ink); text-decoration: none; }
        .preset-sep { width: 1px; height: 12px; background: var(--line); }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table.logs { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 13px; min-width: 720px; }
        table.logs col.c-time { width: 132px; }
        table.logs col.c-person { width: 178px; }
        table.logs col.c-activity { width: 196px; }
        table.logs col.c-ip { width: 104px; }
        table.logs thead th {
            text-align: left;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--muted);
            padding: 10px 14px;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }
        table.logs tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--line-soft);
            vertical-align: middle;
            color: var(--ink);
        }
        table.logs tbody tr:last-child td { border-bottom: 0; }
        table.logs tbody tr { transition: background .12s ease; }
        table.logs tbody tr:hover { background: #f8fbfe; }

        .col-time { white-space: nowrap; }
        .time-main { font-variant-numeric: tabular-nums; font-weight: 600; }
        .time-sub { display: block; font-size: 11.5px; color: var(--muted); font-variant-numeric: tabular-nums; }

        .person { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .person-initial {
            width: 28px; height: 28px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
            background: var(--chip-bg); color: var(--chip-ink);
        }
        .person-name { font-size: 13px; font-weight: 600; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 132px; }
        .person-sub { font-size: 11.5px; color: var(--muted); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 132px; }

        .activity { display: flex; align-items: flex-start; gap: 8px; }
        .activity-label { display: block; font-weight: 600; white-space: nowrap; }
        .activity-app { display: block; margin-top: 1px; font-size: 11.5px; color: var(--muted); white-space: nowrap; }
        .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-top: 6px; }
        .dot.accent { background: var(--accent); }
        .dot.positive { background: var(--positive); }
        .dot.negative { background: var(--negative); }
        .dot.neutral { background: #94a3b8; }

        .dash { color: #b6c2d1; }

        .desc-cell { color: var(--ink-soft); font-size: 12.5px; max-width: 340px; }
        .desc-cell span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .ip-cell { font-variant-numeric: tabular-nums; color: var(--muted); font-size: 12px; white-space: nowrap; }

        .num { text-align: right; font-variant-numeric: tabular-nums; }

        /* Empty state */
        .empty {
            padding: 54px 24px;
            text-align: center;
        }
        .empty-title { font-size: 14px; font-weight: 700; margin-bottom: 5px; }
        .empty-text { font-size: 12.5px; color: var(--muted); max-width: 46ch; margin: 0 auto; line-height: 1.5; }

        /* Pagination */
        .pagination {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 12px 18px; border-top: 1px solid var(--line-soft);
            font-size: 12px; color: var(--muted);
        }
        .pagination-actions { display: flex; gap: 8px; }
        .pagination-actions .link-btn[aria-disabled="true"] { opacity: .45; pointer-events: none; }

        /* Side panels */
        .side { display: flex; flex-direction: column; gap: 18px; }

        .top-apps { padding: 6px 18px 14px; }
        .top-app { padding: 10px 0; border-bottom: 1px solid var(--line-soft); }
        .top-app:last-child { border-bottom: 0; }
        .top-app-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
        .top-app-name { font-size: 12.5px; font-weight: 600; }
        .top-app-count { font-size: 12.5px; font-weight: 700; font-variant-numeric: tabular-nums; flex-shrink: 0; }
        .top-app-track { margin-top: 7px; height: 4px; border-radius: 2px; background: #eef2f7; overflow: hidden; }
        .top-app-fill { height: 100%; background: var(--accent); border-radius: 2px; }

        /* Login per pengguna */
        .user-logins .top-app-name {
            flex: 1 1 auto; min-width: 0;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .top-app-sub {
            margin-top: 1px; font-size: 11.5px; color: var(--muted);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .panel-empty { font-size: 12px; color: var(--muted); line-height: 1.55; padding: 10px 0 12px; }
        .panel-foot {
            display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
            padding: 12px 18px; border-top: 1px solid var(--line-soft);
            font-size: 12px; color: var(--muted);
        }
        .panel-foot b { color: var(--ink-soft); font-weight: 600; font-variant-numeric: tabular-nums; }
        .panel-note { padding: 0 18px 14px; margin-top: -4px; font-size: 11.5px; color: var(--muted); }

        /* Peringatan skema belum dimigrasi */
        .notice-migrate {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 18px 0; padding: 12px 16px;
            border: 1px solid #fde68a; border-radius: 10px;
            background: #fffbeb; color: var(--warning);
            font-size: 12.5px; font-weight: 500;
        }
        .notice-migrate a { color: var(--warning); font-weight: 700; }

        .denied-line {
            display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
            padding: 14px 18px; border-top: 1px solid var(--line-soft);
            font-size: 12.5px;
        }
        .denied-line b { font-variant-numeric: tabular-nums; }
        .denied-line.is-alert b { color: var(--negative); }

        @media (max-width: 1080px) {
            .body-grid { grid-template-columns: minmax(0, 1fr); }
            .side { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 900px) {
            /* Tablet: kolom IP dilepas agar keterangan tetap terbaca */
            .ip-cell, .ip-head { display: none; }
            .time-rel { display: none; }
            table.logs { min-width: 0; }
            table.logs col.c-time { width: 100px; }
            table.logs col.c-person { width: 186px; }
            table.logs col.c-activity { width: 196px; }
            table.logs col.c-ip { width: 0; }
            .desc-cell { max-width: none; }
        }

        @media (max-width: 760px) {
            .shell { padding: 16px 16px 40px; }
            .title-row { padding: 20px 0 16px; }
            h1 { font-size: 21px; }
            .strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .strip-cell:nth-child(3) { border-left: 0; }
            .strip-cell:nth-child(n+3) { border-top: 1px solid var(--line-soft); }
            .strip-value { font-size: 22px; }
            .filters { grid-template-columns: minmax(0, 1fr); }
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar .dates { display: grid; grid-template-columns: 1fr 1fr; }
            .toolbar .field input[type="date"] { width: 100%; }
            .filter-actions { margin-left: 0; }
            .filter-actions .link-btn { flex: 1; justify-content: center; }
            .side { grid-template-columns: minmax(0, 1fr); }
            .topbar { flex-wrap: wrap; }
        }

        /* Ponsel: tabel berubah menjadi daftar baris bertumpuk, tanpa scroll horizontal */
        @media (max-width: 640px) {
            table.logs { display: block; min-width: 0; font-size: 13px; }
            table.logs colgroup { display: none; }
            table.logs thead { display: none; }
            table.logs tbody { display: block; }

            table.logs tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 2px 12px;
                padding: 12px 14px;
                border-bottom: 1px solid var(--line-soft);
            }
            table.logs tbody tr:last-child { border-bottom: 0; }
            table.logs tbody td {
                display: block;
                padding: 0;
                border-bottom: 0;
                min-width: 0;
            }

            td.col-time { grid-column: 1; grid-row: 1; align-self: center; }
            .time-sub { display: inline; margin-left: 6px; }
            td:nth-child(2) { grid-column: 2; grid-row: 1; justify-self: end; }
            td:nth-child(3) { grid-column: 1 / -1; margin-top: 9px; }
            td.desc-cell { grid-column: 1 / -1; margin-top: 4px; max-width: none; }
            td.ip-cell {
                display: block;
                grid-column: 1 / -1;
                margin-top: 5px;
                font-size: 11px;
            }
            td.ip-cell::before { content: 'IP '; color: var(--muted); }

            .person-name, .person-sub { max-width: 150px; text-align: right; }

            .desc-cell span {
                white-space: normal;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }
        }
    </style>
</head>
<body>
<div class="shell">

    <div class="topbar">
        <a href="index.php" class="brand">
            <span class="brand-mark">LJU</span>
            <span class="brand-name">LJU Portal <span>&middot; Log Aktivitas</span></span>
        </a>
        <div class="topbar-actions">
            <a href="index.php" class="link-btn">Kembali ke Dashboard</a>
            <a href="activity_logs.php" class="link-btn">Muat ulang</a>
        </div>
    </div>

    <div class="title-row">
        <div>
            <h1>Log Aktivitas</h1>
            <p class="subtitle"><?= h($finding) ?></p>
        </div>
        <span class="panel-meta">
            <?= h(date('d/m/Y', strtotime($filters['date_from']))) ?>
            <?= $filters['date_from'] !== $filters['date_to'] ? '&ndash; ' . h(date('d/m/Y', strtotime($filters['date_to']))) : '' ?>
            &middot; <?= number_format($totalRows, 0, ',', '.') ?> baris
        </span>
    </div>

    <div class="strip">
        <div class="strip-cell">
            <div class="strip-label">Aktivitas hari ini</div>
            <div class="strip-value"><?= number_format($stats['today_total'], 0, ',', '.') ?></div>
            <div class="strip-note">Semua event tercatat</div>
        </div>
        <div class="strip-cell">
            <div class="strip-label">Login hari ini</div>
            <div class="strip-value"><?= number_format($stats['today_logins'], 0, ',', '.') ?></div>
            <div class="strip-note">Password &amp; Google</div>
        </div>
        <div class="strip-cell">
            <div class="strip-label">Pengguna aktif</div>
            <div class="strip-value"><?= number_format($stats['today_users'], 0, ',', '.') ?></div>
            <div class="strip-note">Punya aktivitas hari ini</div>
        </div>
        <div class="strip-cell">
            <div class="strip-label">Akses ditolak</div>
            <div class="strip-value <?= $stats['today_denied'] > 0 ? 'negative' : '' ?>"><?= number_format($stats['today_denied'], 0, ',', '.') ?></div>
            <div class="strip-note">Percobaan tanpa hak akses</div>
        </div>
    </div>

    <?php if (!$schemaReady): ?>
        <div class="notice-migrate">
            <span>Kolom detail aktivitas belum tersedia. <a href="migrate_login_logs.php">Jalankan migrasi</a> agar log aplikasi tersimpan lengkap.</span>
        </div>
    <?php endif; ?>

    <div class="body-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">Riwayat aktivitas</span>
                <span class="panel-meta">Halaman <?= $page ?> dari <?= $totalPages ?></span>
            </div>

            <form method="GET" action="activity_logs.php">
                <div class="filters">
                    <div class="field search">
                        <label for="f-user">Pengguna</label>
                        <input type="text" id="f-user" name="user" value="<?= h($filters['user']) ?>" placeholder="Nama atau username">
                    </div>

                    <div class="field">
                        <label for="f-app">Aplikasi</label>
                        <select id="f-app" name="app">
                            <option value="">Semua aplikasi</option>
                            <?php foreach ($appOptions as $key => $name): ?>
                                <option value="<?= h($key) ?>" <?= $filters['app'] === $key ? 'selected' : '' ?>><?= h($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="f-activity">Aktivitas</label>
                        <select id="f-activity" name="activity">
                            <option value="">Semua aktivitas</option>
                            <?php foreach ($catalog as $code => $meta): ?>
                                <option value="<?= h($code) ?>" <?= $filters['activity'] === $code ? 'selected' : '' ?>><?= h($meta['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="toolbar">
                    <div class="presets">
                        <a class="preset <?= $filters['date_from'] === $presetToday && $filters['date_to'] === $presetToday ? 'is-active' : '' ?>"
                           href="activity_logs.php?date_from=<?= $presetToday ?>&amp;date_to=<?= $presetToday ?>">Hari ini</a>
                        <span class="preset-sep"></span>
                        <a class="preset <?= $filters['date_from'] === $preset7 && $filters['date_to'] === $presetToday ? 'is-active' : '' ?>"
                           href="activity_logs.php?date_from=<?= $preset7 ?>&amp;date_to=<?= $presetToday ?>">7 hari</a>
                        <span class="preset-sep"></span>
                        <a class="preset <?= $filters['date_from'] === $preset30 && $filters['date_to'] === $presetToday ? 'is-active' : '' ?>"
                           href="activity_logs.php?date_from=<?= $preset30 ?>&amp;date_to=<?= $presetToday ?>">30 hari</a>
                    </div>

                    <div class="dates">
                        <div class="field">
                            <label for="f-from">Dari tanggal</label>
                            <input type="date" id="f-from" name="date_from" value="<?= h($filters['date_from']) ?>">
                        </div>
                        <div class="field">
                            <label for="f-to">Sampai</label>
                            <input type="date" id="f-to" name="date_to" value="<?= h($filters['date_to']) ?>">
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="link-btn">Terapkan</button>
                        <a href="activity_logs.php" class="link-btn">Reset</a>
                    </div>
                </div>
            </form>

            <?php if (!empty($rows)): ?>
                <div class="table-wrap" style="padding-top: 6px;">
                    <table class="logs">
                        <colgroup>
                            <col class="c-time">
                            <col class="c-person">
                            <col class="c-activity">
                            <col>
                            <col class="c-ip">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aktivitas</th>
                                <th>Keterangan</th>
                                <th class="ip-head">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $rowActivity = (string) ($row['activity'] ?? '');
                                $tone        = activityTone($rowActivity);
                                $meta        = getActivityMeta($rowActivity, $row['app_name'] ?? null);
                                $rowUserId   = (int) ($row['user_id'] ?? 0);
                                $displayName = $row['user_name'] ?: ($row['username'] ?: 'Tidak terdaftar');
                                $initial     = mb_strtoupper(mb_substr($displayName, 0, 1));
                                $hue         = $rowUserId > 0 ? userHue($rowUserId) : null;
                                $chipStyle   = $hue !== null
                                    ? '--chip-bg: hsl(' . $hue . ' 78% 95%); --chip-ink: hsl(' . $hue . ' 55% 34%);'
                                    : '--chip-bg: #eef2f7; --chip-ink: #64748b;';
                                // Untuk klik aplikasi, label sudah memuat nama aplikasi
                                $showAppLine = !empty($row['app_name']) && strpos($rowActivity, 'open_') !== 0;
                            ?>
                            <tr>
                                <td class="col-time">
                                    <span class="time-main"><?= h(date('H:i', strtotime($row['created_at']))) ?></span>
                                    <span class="time-sub"><?= h(date('d/m/Y', strtotime($row['created_at']))) ?><span class="time-rel"> &middot; <?= h(relativeTime($row['created_at'])) ?></span></span>
                                </td>
                                <td>
                                    <div class="person">
                                        <span class="person-initial" style="<?= $chipStyle ?>">
                                            <?= h($initial !== '' ? $initial : '?') ?>
                                        </span>
                                        <div style="min-width:0;">
                                            <div class="person-name"><?= h($displayName) ?></div>
                                            <div class="person-sub"><?= h($row['position'] ?: ($row['username'] ?: 'Tidak ada akun')) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="activity">
                                        <span class="dot <?= $tone ?>"></span>
                                        <div style="min-width:0;">
                                            <span class="activity-label"><?= h($meta['label']) ?></span>
                                            <?php if ($showAppLine): ?>
                                                <span class="activity-app"><?= h($row['app_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="desc-cell">
                                    <?php if (!empty($row['description'])): ?>
                                        <span title="<?= h($row['description']) ?>"><?= h($row['description']) ?></span>
                                    <?php else: ?>
                                        <span class="dash">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ip-cell"><?= !empty($row['ip_address']) ? h($row['ip_address']) : '&mdash;' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <span><?= number_format($totalRows, 0, ',', '.') ?> baris &middot; menampilkan <?= count($rows) ?></span>
                    <div class="pagination-actions">
                        <a class="link-btn" href="activity_logs.php?<?= h($baseQs) ?>page=<?= max(1, $page - 1) ?>"
                           <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>Sebelumnya</a>
                        <a class="link-btn" href="activity_logs.php?<?= h($baseQs) ?>page=<?= min($totalPages, $page + 1) ?>"
                           <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>Berikutnya</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty">
                    <div class="empty-title">Belum ada aktivitas pada rentang ini</div>
                    <p class="empty-text">
                        Log akan terisi otomatis ketika pengguna login, membuka dashboard, atau menekan tombol aplikasi
                        di portal. Coba perluas rentang tanggal atau hapus filter.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <aside class="side">
            <div class="panel">
                <div class="panel-head">
                    <span class="panel-title">Aplikasi teratas hari ini</span>
                </div>

                <?php if (!empty($stats['top_apps'])): ?>
                    <div class="top-apps">
                        <?php foreach ($stats['top_apps'] as $topApp): ?>
                            <?php $width = $maxTop > 0 ? max(3, round(((int) $topApp['total'] / $maxTop) * 100)) : 0; ?>
                            <div class="top-app">
                                <div class="top-app-row">
                                    <span class="top-app-name"><?= h($topApp['app_name'] ?: $topApp['app_key']) ?></span>
                                    <span class="top-app-count"><?= number_format((int) $topApp['total'], 0, ',', '.') ?></span>
                                </div>
                                <div class="top-app-track">
                                    <div class="top-app-fill" style="width: <?= $width ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="top-apps">
                        <p style="font-size: 12px; color: var(--muted); line-height: 1.55; padding: 10px 0 12px;">
                            Belum ada aplikasi yang dibuka hari ini.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="denied-line <?= $stats['today_denied'] > 0 ? 'is-alert' : '' ?>">
                    <span>Akses ditolak hari ini</span>
                    <b><?= number_format($stats['today_denied'], 0, ',', '.') ?></b>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <span class="panel-title">Login per pengguna</span>
                    <span class="panel-meta"><?= $periodLabel ?></span>
                </div>

                <?php if (!empty($loginUsers['rows'])): ?>
                    <div class="top-apps user-logins">
                        <?php foreach ($loginUsers['rows'] as $loginRow): ?>
                            <?php
                                $loginName = $loginRow['name'] ?: ($loginRow['username'] ?: 'Pengguna #' . (int) $loginRow['user_id']);
                                $loginSub  = trim((string) ($loginRow['position'] ?? ''));
                                if ($loginSub === '') { $loginSub = trim((string) ($loginRow['division'] ?? '')); }
                                $loginWidth = $maxUserLogin > 0 ? max(3, round(((int) $loginRow['total'] / $maxUserLogin) * 100)) : 0;
                            ?>
                            <div class="top-app">
                                <div class="top-app-row">
                                    <span class="top-app-name" title="<?= h($loginName) ?>"><?= h($loginName) ?></span>
                                    <span class="top-app-count"><?= number_format((int) $loginRow['total'], 0, ',', '.') ?></span>
                                </div>
                                <?php if ($loginSub !== ''): ?>
                                    <div class="top-app-sub"><?= h($loginSub) ?></div>
                                <?php endif; ?>
                                <div class="top-app-track">
                                    <div class="top-app-fill" style="width: <?= $loginWidth ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="top-apps">
                        <p class="panel-empty">
                            Belum ada login pada periode ini.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="panel-foot">
                    <span>Total login</span>
                    <b><?= number_format($loginUsers['total_logins'], 0, ',', '.') ?> login &middot; <?= number_format($loginUsers['total_users'], 0, ',', '.') ?> pengguna</b>
                </div>
                <?php if (!empty($excludedLoginUsers)): ?>
                    <div class="panel-note">
                        Tidak termasuk user_id <?= h(implode(', ', $excludedLoginUsers)) ?>.
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
</body>
</html>
