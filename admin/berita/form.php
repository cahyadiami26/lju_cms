<?php
require_once __DIR__ . '/../guard.php';
require_once __DIR__ . '/../layout.php';
$user = require_user();

$id      = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$row     = null;
$errors  = [];

if ($editing) {
    $st = pdo_app()->prepare("SELECT * FROM berita WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { http_response_code(404); exit('Berita tidak ditemukan.'); }
    if (!is_admin($user) && (int)$row['created_by'] !== (int)$user['id'] && !is_infokom($user)) {
        http_response_code(403); exit('Anda tidak berhak mengedit berita ini.');
    }
}

$data = [
    'no_urut'      => $row['no_urut']      ?? 0,
    'tag'          => $row['tag']          ?? '',
    'short_title'  => $row['short_title']  ?? '',
    'full_title'   => $row['full_title']   ?? '',
    'news_content' => $row['news_content'] ?? '',
    'front_image'  => $row['front_image']  ?? null,
    'main_image'   => $row['main_image']   ?? null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $k => $_) {
        if (in_array($k, ['front_image', 'main_image'], true)) continue;
        $data[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if ($data['short_title'] === '')              $errors[] = 'Short title wajib diisi.';
    if ($data['full_title'] === '')               $errors[] = 'Full title wajib diisi.';
    if ($data['news_content'] === '')             $errors[] = 'Konten berita wajib diisi.';
    if ($data['tag'] === '')                      $errors[] = 'Tag wajib diisi.';

    if (!$errors) {
        try {
            $newFront = save_image($_FILES['front_image'] ?? [], 'front');
            $newMain  = save_image($_FILES['main_image']  ?? [], 'main');
            $slug     = unique_slug(slugify($data['short_title']), $id);
            $db       = pdo_app();

            if ($editing) {
                $st = $db->prepare("UPDATE berita SET no_urut=?, tag=?, short_title=?, full_title=?, slug=?,
                        news_content=?, front_image=COALESCE(?,front_image), main_image=COALESCE(?,main_image)
                        WHERE id=?");
                $st->execute([$data['no_urut'], $data['tag'], $data['short_title'], $data['full_title'],
                    $slug, $data['news_content'], $newFront, $newMain, $id]);
                if ($newFront) delete_upload($row['front_image']);
                if ($newMain)  delete_upload($row['main_image']);
                flash('Berita berhasil diperbarui.');
            } else {
                $st = $db->prepare("INSERT INTO berita
                        (no_urut, tag, short_title, full_title, slug, news_content, front_image, main_image, created_by)
                        VALUES (?,?,?,?,?,?,?,?,?)");
                $st->execute([$data['no_urut'], $data['tag'], $data['short_title'], $data['full_title'],
                    $slug, $data['news_content'], $newFront, $newMain, $user['id']]);
                flash('Berita berhasil dibuat (status: draft).');
            }
            header('Location: index.php'); exit;
        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
        }
    }
    // Simpan gambar lama agar preview tidak hilang saat validasi gagal
    $data['front_image'] = $data['front_image'] ?? ($row['front_image'] ?? null);
    $data['main_image']  = $data['main_image']  ?? ($row['main_image']  ?? null);
}

page_header($editing ? 'Edit Berita' : 'Tulis Berita', $user, 'berita');
?>
<?php foreach ($errors as $err): ?>
<div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="field">
            <label>No. Urut</label>
            <input type="number" name="no_urut" min="0" value="<?= e((string)$data['no_urut']) ?>">
            <div class="hint">Menentukan urutan tampil di homepage (kecil = duluan).</div>
        </div>
        <div class="field">
            <label>Tag / Kategori</label>
            <input type="text" name="tag" list="taglist" value="<?= e($data['tag']) ?>" placeholder="Energy, Technology, …">
            <datalist id="taglist">
                <option value="Energy"><option value="Technology"><option value="Transportation">
                <option value="Logistics"><option value="Trade"><option value="Perusahaan">
            </datalist>
        </div>
        <div class="field full">
            <label>Short Title <span style="color:var(--red)">*</span></label>
            <input type="text" name="short_title" value="<?= e($data['short_title']) ?>" maxlength="150"
                   placeholder="Judul singkat untuk kartu homepage">
        </div>
        <div class="field full">
            <label>Full Title <span style="color:var(--red)">*</span></label>
            <input type="text" name="full_title" value="<?= e($data['full_title']) ?>" maxlength="255"
                   placeholder="Judul lengkap untuk halaman detail">
        </div>
        <div class="field full">
            <label>Front Image <span style="font-weight:400;color:var(--muted)">(gambar kartu homepage · JPG/PNG/WebP, maks 3MB)</span></label>
            <?php if ($data['front_image']): ?>
                <img class="img-preview" src="<?= e(BASE_URL . '/' . $data['front_image']) ?>" alt="">
            <?php endif; ?>
            <input type="file" name="front_image" accept="image/jpeg,image/png,image/webp" style="margin-top:8px">
        </div>
        <div class="field full">
            <label>Main Image <span style="font-weight:400;color:var(--muted)">(gambar utama halaman detail)</span></label>
            <?php if ($data['main_image']): ?>
                <img class="img-preview" src="<?= e(BASE_URL . '/' . $data['main_image']) ?>" alt="">
            <?php endif; ?>
            <input type="file" name="main_image" accept="image/jpeg,image/png,image/webp" style="margin-top:8px">
        </div>
        <div class="field full">
            <label>Konten Berita <span style="color:var(--red)">*</span></label>
            <textarea id="news_content" name="news_content" rows="14"><?= e($data['news_content']) ?></textarea>
        </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:8px">
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Simpan Berita' ?></button>
        <a class="btn btn-outline" href="index.php">Batal</a>
    </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.9.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#news_content',
    height: 420,
    license_key: 'gpl',
    menubar: false,
    branding: false,
    promotion: false,
    plugins: 'advlist lists link image table code wordcount',
    toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | bullist numlist | link image table | code',
    relative_urls: false,
    remove_script_host: false,
    images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
        const fd = new FormData();
        fd.append('file', blobInfo.blob(), blobInfo.filename());
        fetch('<?= e(BASE_URL) ?>/upload-tinymce.php', {   // ← absolut
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('input[name=csrf_token]').value },
            body: fd
        })
        .then(r => r.json())
        .then(d => d.location ? resolve(d.location) : reject(d.error || 'Upload gagal'))
        .catch(() => reject('Upload gagal'));
    })

});
</script>
<?php page_footer();
