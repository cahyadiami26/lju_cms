<?php
session_name('LJU_APPS');
ini_set('session.cookie_domain', '.lju.co.id');

session_set_cookie_params([
    'lifetime' => 86400,
    'path'     => '/',
    'domain'   => '.lju.co.id',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly'  => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function page_header(string $title, array $user, string $active = ''): void {
    $initial = mb_strtoupper(mb_substr((string)$user['name'], 0, 1));
    $nav = [
        'dashboard' => ['dashboard.php',      'Dashboard'],
        'berita'    => ['berita/index.php',   'Berita'],
        'snippet'   => ['snippet.php',        'Snippet Homepage'],
        'jadwal' => ['jadwal/index.php', 'Jadwal Kapal'],
    ];
    ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/app.css">

</head>
<body class="app">
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">LJU</div>
        <div><strong>Website Content</strong><span>Manager · PT Lampung Jasa Utama</span></div>
    </div>
    <nav>
        <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= e(BASE_URL . '/' . $href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <br>
        
        <a href="https://lju.co.id/dashboard" class="sidebar-link text-danger" >
                        <span class="sidebar-icon"><i class="fas fa-sign-out"></i></span>
                        <span class="sidebar-text"><= LJU Dashboard</span>
        </a>
                    
    </nav>
    <div class="sidebar-foot">Divisi Infokom</div>
</aside>
<main class="main">
    <header class="topbar">
        <h1><?= e($title) ?></h1>
        <div class="userchip">
            <span class="avatar"><?= e($initial) ?></span>
            <div><strong><?= e($user['name']) ?></strong><span><?= e($user['position'] ?: 'Staff') ?> · Infokom</span></div>
        </div>
    </header>
    <div class="content">
    <?php flash();
}
function page_footer(): void { ?>
    </div>
</main>
</body>
</html>
<?php } ?>
