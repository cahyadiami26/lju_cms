<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/layout.php';
$user = require_user();

$stats = pdo_app()->query(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(status='published'),0) AS published,
            COALESCE(SUM(status='draft'),0) AS draft
     FROM berita")->fetch();
$recent = pdo_app()->query(
    "SELECT id, tag, short_title, status, no_urut, updated_at
     FROM berita ORDER BY updated_at DESC LIMIT 6")->fetchAll();

page_header('Dashboard', $user, 'dashboard');
?>
<div class="stats">
    <div class="stat"><span>Total Berita</span><b><?= (int)$stats['total'] ?></b></div>
    <div class="stat"><span>Published</span><b><?= (int)$stats['published'] ?></b></div>
    <div class="stat"><span>Draft</span><b><?= (int)$stats['draft'] ?></b></div>
</div>

<div class="toolbar">
    <h2 style="font-size:16px;color:var(--navy)">Berita Terbaru Diperbarui</h2>
    <a class="btn btn-primary" href="berita/form.php">+ Tulis Berita</a>
</div>

<div class="card" style="padding:6px 10px">
<table class="table">
    <tr><th>Judul</th><th>Tag</th><th>No.</th><th>Status</th><th>Update</th><th></th></tr>
    <?php if (!$recent): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:28px">Belum ada berita. Mulai dari “Tulis Berita”.</td></tr>
    <?php endif; ?>
    <?php foreach ($recent as $r): ?>
    <tr>
        <td><a href="berita/form.php?id=<?= (int)$r['id'] ?>"><strong><?= e($r['short_title']) ?></strong></a></td>
        <td><span class="badge"><?= e($r['tag']) ?></span></td>
        <td><?= (int)$r['no_urut'] ?></td>
        <td><span class="badge st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td style="color:var(--muted);font-size:12.5px"><?= e(date('d M Y H:i', strtotime($r['updated_at']))) ?></td>
        <td><a class="btn btn-outline btn-sm" href="berita/form.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php page_footer();
