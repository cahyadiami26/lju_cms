<?php
require_once __DIR__ . '/../guard.php';
require_once __DIR__ . '/../layout.php';
$user = require_user();

$q      = trim($_GET['q'] ?? '');
$status = in_array($_GET['status'] ?? '', ['draft', 'published'], true) ? $_GET['status'] : '';

$sql = "SELECT * FROM cta_jadwal WHERE 1=1";
$params = [];
if ($q !== '')      { $sql .= " AND (heading_text LIKE ? OR button_text LIKE ?)"; $params = ["%$q%", "%$q%"]; }
if ($status !== '') { $sql .= " AND status = ?"; $params[] = $status; }
$sql .= " ORDER BY status='published' DESC, published_at DESC, id DESC";

$st = pdo_app()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

page_header('CTA Jadwal Kapal', $user, 'jadwal');
?>
<div class="toolbar">
    <form method="get">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari heading…">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
        <button class="btn btn-outline">Filter</button>
    </form>
    <a class="btn btn-primary" href="form.php">+ Jadwal Baru</a>
</div>

<div class="card" style="padding:6px 10px">
<table class="table">
    <tr><th></th><th>Heading</th><th>Button</th><th>Status</th><th>Update</th><th style="width:230px">Aksi</th></tr>
    <?php if (!$rows): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:28px">Belum ada jadwal.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): $canManage = (int)$r['created_by'] === (int)$user['id']; ?>
    <tr>
        <td><img class="thumb" src="<?= e(uploads_url($r['jadwal_image'], 'jadwal')) ?>" alt="" onerror="this.style.visibility='hidden'"></td>
        <td><a href="form.php?id=<?= (int)$r['id'] ?>"><strong><?= e($r['heading_text']) ?></strong></a></td>
        <td style="font-size:12.5px;color:var(--muted)"><?= e($r['button_text']) ?></td>
        <td><span class="badge st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span>
            <?php if ($r['published_at']): ?><div style="font-size:11px;color:var(--muted)"><?= e(date('d M Y', strtotime($r['published_at']))) ?></div><?php endif; ?></td>
        <td style="color:var(--muted);font-size:12.5px"><?= e(date('d M Y H:i', strtotime($r['updated_at']))) ?></td>
        <td>
            
            <a class="btn btn-outline btn-sm" href="form.php?id=<?= (int)$r['id'] ?>">Edit</a>
            
            
            <form method="post" action="status.php" style="display:inline"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-outline btn-sm"><?= $r['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
            </form>
            
            <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Hapus jadwal ini?')"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
            </form>
            <span style="color:var(--muted);font-size:12px">—</span>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php page_footer();
