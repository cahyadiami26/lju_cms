<?php // status.php — publish/unpublish (khusus admin)
require_once __DIR__ . '/../guard.php';
$user = require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('Hanya admin yang dapat mengubah status publikasi.', 'danger');
    header('Location: index.php'); exit;
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$st = pdo_app()->prepare("SELECT status FROM berita WHERE id = ?");
$st->execute([$id]);
$row = $st->fetch();

if ($row) {
    $new = $row['status'] === 'published' ? 'draft' : 'published';
    pdo_app()->prepare("UPDATE berita SET status = ?, published_at = " . ($new === 'published' ? 'NOW()' : 'NULL') . " WHERE id = ?")
        ->execute([$new, $id]);
    flash('Status berita diubah menjadi ' . $new . '.');
}
header('Location: index.php');
