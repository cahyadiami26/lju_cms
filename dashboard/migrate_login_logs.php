<?php
/**
 * Migrasi Skema Tabel login_logs
 * File: migrate_login_logs.php
 *
 * Jalankan sekali lewat browser (login sebagai admin / Infokom):
 *   https://lju.co.id/dashboard/migrate_login_logs.php
 *
 * Yang dilakukan:
 *   - Membuat tabel login_logs jika belum ada
 *   - Mengubah kolom activity menjadi VARCHAR(50) (dari ENUM lama)
 *   - Membuat user_id boleh NULL (untuk login gagal tanpa user terdaftar)
 *   - Menambah kolom app_key, app_name, description, target_url
 *   - Menambah index untuk laporan aktivitas
 *
 * Aman dijalankan berulang: setiap langkah dicek dulu sebelum dijalankan.
 * Setelah migrasi selesai, file ini boleh dihapus.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/apps.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getSessionUser();
$isAllowed   = isUserDireksi($currentUser) || isUserInfokom($currentUser) || (($_SESSION['role'] ?? '') === 'admin');

if (!$isAllowed) {
    http_response_code(403);
    exit('Halaman ini hanya dapat diakses administrator.');
}

$steps   = [];
$ran     = false;
$columns = [];
$indexes = [];
$error   = '';

$pdo = getDBConnection();

function tableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :t");
        $stmt->execute(['t' => $table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function getColumns(PDO $pdo, string $table): array
{
    $cols = [];
    try {
        foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll() as $row) {
            $cols[strtolower($row['Field'])] = $row;
        }
    } catch (Throwable $e) {
        // abaikan
    }
    return $cols;
}

function getIndexes(PDO $pdo, string $table): array
{
    $idx = [];
    try {
        foreach ($pdo->query("SHOW INDEX FROM `{$table}`")->fetchAll() as $row) {
            $idx[$row['Key_name']] = true;
        }
    } catch (Throwable $e) {
        // abaikan
    }
    return $idx;
}

function runStep(string $label, callable $fn, array &$steps): void
{
    try {
        $result = $fn();
        $steps[] = [
            'label'  => $label,
            'status' => 'ok',
            'detail' => $result ?: 'OK',
        ];
    } catch (Throwable $e) {
        $steps[] = [
            'label'  => $label,
            'status' => 'error',
            'detail' => $e->getMessage(),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run') {
    $ran = true;

    // 1. Tabel login_logs
    if (!tableExists($pdo, 'login_logs')) {
        runStep('Membuat tabel login_logs', function () use ($pdo) {
            $pdo->exec("
                CREATE TABLE `login_logs` (
                    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` INT(10) UNSIGNED DEFAULT NULL,
                    `activity` VARCHAR(50) NOT NULL DEFAULT 'login_success',
                    `app_key` VARCHAR(50) DEFAULT NULL,
                    `app_name` VARCHAR(100) DEFAULT NULL,
                    `description` VARCHAR(255) DEFAULT NULL,
                    `target_url` VARCHAR(255) DEFAULT NULL,
                    `ip_address` VARCHAR(45) DEFAULT NULL,
                    `user_agent` VARCHAR(1000) DEFAULT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_login_logs_user_created` (`user_id`, `created_at`),
                    KEY `idx_login_logs_activity_created` (`activity`, `created_at`),
                    KEY `idx_login_logs_app_created` (`app_key`, `created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return 'Tabel dibuat';
        }, $steps);
    }

    $columns = getColumns($pdo, 'login_logs');
    $indexes = getIndexes($pdo, 'login_logs');

    // 2. Kolom activity -> VARCHAR(50)
    if (!empty($columns['activity'])) {
        $type = strtolower((string) $columns['activity']['Type']);
        if (strpos($type, 'varchar(50)') !== 0) {
            runStep('Mengubah kolom activity menjadi VARCHAR(50)', function () use ($pdo) {
                $pdo->exec("ALTER TABLE `login_logs` MODIFY COLUMN `activity` VARCHAR(50) NOT NULL DEFAULT 'login_success'");
                return 'Tipe data diperbarui';
            }, $steps);
        } else {
            $steps[] = ['label' => 'Kolom activity', 'status' => 'skip', 'detail' => 'Sudah VARCHAR(50)'];
        }
    }

    // 3. user_id boleh NULL
    if (!empty($columns['user_id'])) {
        $nullable = strtoupper((string) $columns['user_id']['Null']) === 'YES';
        if (!$nullable) {
            runStep('Membuat user_id boleh NULL', function () use ($pdo) {
                $pdo->exec("ALTER TABLE `login_logs` MODIFY COLUMN `user_id` INT(10) UNSIGNED NULL DEFAULT NULL");
                return 'Kolom user_id diperbarui';
            }, $steps);
        } else {
            $steps[] = ['label' => 'Kolom user_id', 'status' => 'skip', 'detail' => 'Sudah boleh NULL'];
        }
    }

    // 4. Kolom tambahan
    $newColumns = [
        'app_key'     => "VARCHAR(50) DEFAULT NULL AFTER `user_id`",
        'app_name'    => "VARCHAR(100) DEFAULT NULL AFTER `app_key`",
        'description' => "VARCHAR(255) DEFAULT NULL AFTER `app_name`",
        'target_url'  => "VARCHAR(255) DEFAULT NULL AFTER `description`",
    ];

    foreach ($newColumns as $col => $definition) {
        if (isset($columns[$col])) {
            $steps[] = ['label' => 'Kolom ' . $col, 'status' => 'skip', 'detail' => 'Sudah ada'];
            continue;
        }

        runStep('Menambah kolom ' . $col, function () use ($pdo, $col, $definition) {
            $pdo->exec("ALTER TABLE `login_logs` ADD COLUMN `{$col}` {$definition}");
            return 'Kolom ditambahkan';
        }, $steps);
    }

    // 5. user_agent diperbesar (opsional)
    if (!empty($columns['user_agent'])) {
        $uaType = strtolower((string) $columns['user_agent']['Type']);
        if (strpos($uaType, 'varchar(255)') === 0) {
            runStep('Memperbesar kolom user_agent', function () use ($pdo) {
                $pdo->exec("ALTER TABLE `login_logs` MODIFY COLUMN `user_agent` VARCHAR(1000) DEFAULT NULL");
                return 'user_agent -> VARCHAR(1000)';
            }, $steps);
        }
    }

    // 6. Index
    $newIndexes = [
        'idx_login_logs_user_created'     => "(`user_id`, `created_at`)",
        'idx_login_logs_activity_created' => "(`activity`, `created_at`)",
        'idx_login_logs_app_created'      => "(`app_key`, `created_at`)",
    ];

    foreach ($newIndexes as $name => $def) {
        if (isset($indexes[$name])) {
            $steps[] = ['label' => 'Index ' . $name, 'status' => 'skip', 'detail' => 'Sudah ada'];
            continue;
        }

        runStep('Menambah index ' . $name, function () use ($pdo, $name, $def) {
            $pdo->exec("ALTER TABLE `login_logs` ADD INDEX `{$name}` {$def}");
            return 'Index ditambahkan';
        }, $steps);
    }

    $columns = getColumns($pdo, 'login_logs');
    $indexes = getIndexes($pdo, 'login_logs');
} else {
    $columns = getColumns($pdo, 'login_logs');
    $indexes = getIndexes($pdo, 'login_logs');
}

$requiredColumns = ['user_id', 'activity', 'app_key', 'app_name', 'description', 'target_url', 'ip_address', 'user_agent', 'created_at'];
$missingColumns  = array_diff($requiredColumns, array_keys($columns));
$schemaReady     = empty($missingColumns);

$totalLogs = 0;
if (!empty($columns)) {
    try {
        $totalLogs = (int) $pdo->query("SELECT COUNT(*) FROM login_logs")->fetchColumn();
    } catch (Throwable $e) {
        $totalLogs = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Log Aktivitas - LJU Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --blue: #1e40af; --teal: #14b8a6; --dark: #0f172a; --gray: #64748b; }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh; color: var(--dark); padding: 32px 16px;
            background: #f2f6fa;
        }
        .wrap { max-width: 820px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; }
        h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.4px; margin-bottom: 6px; }
        .sub { font-size: 13px; color: var(--gray); font-weight: 500; margin-bottom: 20px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 50px; margin-bottom: 18px; }
        .badge.ok { background: #d1fae5; color: #047857; }
        .badge.warn { background: #fef3c7; color: #b45309; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
        th { text-align: left; color: var(--gray); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; font-weight: 600; }
        td.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; font-weight: 500; color: #334155; }
        .step-ok { color: #047857; }
        .step-skip { color: #64748b; }
        .step-error { color: #b91c1c; }
        .btn { display: inline-flex; align-items: center; gap: 8px; background: var(--blue); color: #fff; border: none; padding: 12px 22px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: inherit; text-decoration: none; }
        .btn:hover { background: #1d4ed8; }
        .btn-ghost { background: #e2e8f0; color: var(--dark); }
        .btn-ghost:hover { background: #cbd5e1; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .note { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 12px; color: #475569; line-height: 1.6; margin-top: 18px; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 5px; font-size: 11px; }
        .stat-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        .stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; min-width: 150px; }
        .stat b { display: block; font-size: 20px; font-weight: 800; color: var(--blue); }
        .stat span { font-size: 11px; color: var(--gray); font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Migrasi Log Aktivitas</h1>
        <p class="sub">Menyiapkan tabel <code>login_logs</code> agar dapat mencatat aktivitas tombol &amp; aplikasi yang diklik user.</p>

        <div class="badge <?= $schemaReady ? 'ok' : 'warn' ?>">
            <?= $schemaReady ? 'Skema sudah siap' : 'Skema belum lengkap' ?>
        </div>

        <div class="stat-row">
            <div class="stat"><b><?= number_format($totalLogs, 0, ',', '.') ?></b><span>Total baris log</span></div>
            <div class="stat"><b><?= count($columns) ?></b><span>Kolom terdeteksi</span></div>
            <div class="stat"><b><?= $schemaReady ? 'Siap' : 'Perlu migrasi' ?></b><span>Status pencatatan aplikasi</span></div>
        </div>

        <?php if (!empty($steps)): ?>
            <table>
                <thead>
                    <tr><th>Langkah</th><th>Status</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                <?php foreach ($steps as $step): ?>
                    <tr>
                        <td><?= htmlspecialchars($step['label']) ?></td>
                        <td class="step-<?= $step['status'] ?>">
                            <?= $step['status'] === 'ok' ? 'Berhasil' : ($step['status'] === 'skip' ? 'Dilewati' : 'Gagal') ?>
                        </td>
                        <td class="mono"><?= htmlspecialchars($step['detail']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <table>
            <thead><tr><th>Kolom</th><th>Tipe</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($requiredColumns as $col): ?>
                <tr>
                    <td class="mono"><?= htmlspecialchars($col) ?></td>
                    <td class="mono"><?= htmlspecialchars((string) ($columns[$col]['Type'] ?? '-')) ?></td>
                    <td class="<?= isset($columns[$col]) ? 'step-ok' : 'step-error' ?>">
                        <?= isset($columns[$col]) ? 'Ada' : 'Belum ada' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="actions">
            <?php if (!$schemaReady): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="run">
                    <button type="submit" class="btn">Jalankan Migrasi</button>
                </form>
            <?php endif; ?>
            <a href="activity_logs.php" class="btn btn-ghost">Lihat Log Aktivitas</a>
            <a href="index.php" class="btn btn-ghost">Kembali ke Dashboard</a>
        </div>

        <div class="note">
            <strong>Catatan:</strong> setelah migrasi berhasil, seluruh klik tombol aplikasi (Presensi Online, Naskah Dinas,
            Pengarsipan, Pengadaan, Task Management, SDM/HRD, Webmail, WBS, WebContent, Assessment) serta kunjungan dashboard
            otomatis tercatat di tabel <code>login_logs</code>. Hapus file ini setelah migrasi selesai.
        </div>
    </div>
</div>
</body>
</html>
