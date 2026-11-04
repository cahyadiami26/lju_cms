<?php
require_once __DIR__ . '/../guard.php';
require_once __DIR__ . '/../layout.php';
$user = require_user();

$q      = trim($_GET['q'] ?? '');
$status = in_array($_GET['status'] ?? '', ['draft', 'published'], true) ? $_GET['status'] : '';

$sql    = "SELECT * FROM berita WHERE 1=1";
$params = [];
if ($q !== '')      { $sql .= " AND (short_title LIKE ? OR full_title LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status !== '') { $sql .= " AND status = ?"; $params[] = $status; }
$sql .= " ORDER BY no_urut ASC, id DESC";

$st = pdo_app()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

// Nama penulis dari DB users (DB terpisah)
$authors = [];
$ids = array_values(array_unique(array_column($rows, 'created_by')));
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st2 = pdo_users()->prepare("SELECT id, name FROM users WHERE id IN ($in)");
    $st2->execute($ids);
    $authors = $st2->fetchAll(PDO::FETCH_KEY_PAIR);
}

page_header('Kelola Berita', $user, 'berita');
?>
<div class="toolbar">
    <form method="get">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul…">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
        <button class="btn btn-outline">Filter</button>
    </form>
    <a class="btn btn-primary" href="form.php">+ Tulis Berita</a>
</div>

<div class="card" style="padding:6px 10px">
<table class="table">
    <tr><th></th><th>Judul</th><th>Tag</th><th>No.</th><th>Status</th><th>Penulis</th><th style="width:220px">Aksi</th></tr>
    <?php if (!$rows): ?>
    <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:28px">Tidak ada data.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r):
        $canManage = is_admin($user) || is_infokom($user) || (int)$r['created_by'] === (int)$user['id']; ?>
    <tr>
        <td><img class="thumb" src="<?= e($r['front_image'] ? BASE_URL . '/' . $r['front_image'] : '') ?>" alt="" onerror="this.style.visibility='hidden'"></td>
        <td><a href="form.php?id=<?= (int)$r['id'] ?>"><strong><?= e($r['short_title']) ?></strong></a></td>
        <td><span class="badge"><?= e($r['tag']) ?></span></td>
        <td><?= (int)$r['no_urut'] ?></td>
        <td><span class="badge st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td style="color:var(--muted);font-size:12.5px"><?= e($authors[$r['created_by']] ?? ('User #' . $r['created_by'])) ?></td>
        <td>
            <a class="btn btn-outline btn-sm" href="form.php?id=<?= (int)$r['id'] ?>">Edit</a>
            <form method="post" action="status.php" style="display:inline" onsubmit="return true">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-outline btn-sm"><?= $r['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
            </form>
            <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Hapus berita ini secara permanen?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php page_footer();
