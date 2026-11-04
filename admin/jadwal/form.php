<?php
require_once __DIR__ . '/../guard.php';
require_once __DIR__ . '/../layout.php';
$user = require_user();

$id      = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$db      = pdo_app();
$row     = null;
$errors  = [];

if ($editing) {
    $st = $db->prepare("SELECT * FROM cta_jadwal WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { http_response_code(404); exit('Jadwal tidak ditemukan.'); }

}

$data = ['heading_text' => $row['heading_text'] ?? '', 'button_text' => $row['button_text'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['heading_text'] = trim((string)($_POST['heading_text'] ?? ''));
    $data['button_text']  = trim((string)($_POST['button_text'] ?? ''));
    if ($data['heading_text'] === '') $errors[] = 'Heading wajib diisi.';
    if ($data['button_text'] === '')  $errors[] = 'Button text wajib diisi.';
    // Jika mengedit jadwal yang sudah published, image wajib ada (lama baru saja)
    if (!$errors && $editing && $row['status'] === 'published' && !$row['jadwal_image']
        && ($_FILES['jadwal_image']['error'] ?? 1) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Jadwal published membutuhkan gambar.';
    }

    if (!$errors) {
        try {
            $newImg = save_image($_FILES['jadwal_image'] ?? [], 'jadwal', 'jadwal');
            if ($editing) {
                $db->prepare("UPDATE cta_jadwal SET heading_text=?, button_text=?,
                              jadwal_image=COALESCE(?, jadwal_image) WHERE id=?")
                    ->execute([$data['heading_text'], $data['button_text'], $newImg, $id]);
                if ($newImg) delete_upload($row['jadwal_image'], 'jadwal');
                flash('Jadwal diperbarui.');
            } else {
                $db->prepare("INSERT INTO cta_jadwal (heading_text, button_text, jadwal_image,
                              status, created_by) VALUES (?,?,?,'draft',?)")
                    ->execute([$data['heading_text'], $data['button_text'], $newImg, $user['id']]);
                flash('Jadwal baru dibuat (draft). Publish untuk menampilkannya di homepage.');
            }
            header('Location: index.php'); exit;
        } catch (Throwable $ex) { $errors[] = $ex->getMessage(); }
    }
    $row = $row ?: [];
    $row['heading_text'] = $data['heading_text'];
    $row['button_text']  = $data['button_text'];
}

page_header($editing ? 'Edit Jadwal' : 'Jadwal Baru', $user, 'jadwal');
?>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <div class="field">
        <label>Heading Text <span style="color:var(--red)">*</span></label>
        <input type="text" name="heading_text" value="<?= e($row['heading_text'] ?? '') ?>" maxlength="255">
    </div>
    <div class="field">
        <label>Button Text <span style="color:var(--red)">*</span></label>
        <input type="text" name="button_text" value="<?= e($row['button_text'] ?? '') ?>" maxlength="100"
               placeholder="Contoh: Jadwal Oktober 2026">
        <div class="hint">Ini teks tombol di homepage — update tiap ganti bulan.</div>
    </div>
    <div class="field">
        <label>Jadwal Image <span style="font-weight:400;color:var(--muted)">(banner &amp; popup · JPG/PNG/WebP, maks 3MB)</span></label>
        <?php if (!empty($row['jadwal_image'])): ?>
            <img class="img-preview" src="<?= e(uploads_url($row['jadwal_image'], 'jadwal')) ?>" alt="">
        <?php endif; ?>
        <input type="file" name="jadwal_image" accept="image/jpeg,image/png,image/webp" style="margin-top:8px">
    </div>
    <button class="btn btn-primary" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Simpan Jadwal' ?></button>
    <a class="btn btn-outline" href="index.php">Batal</a>
</form>
<?php page_footer();
