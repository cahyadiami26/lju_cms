<?php // delete.php
require_once __DIR__ . '/../guard.php';
$user = require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$st = pdo_app()->prepare("SELECT * FROM berita WHERE id = ?");
$st->execute([$id]);
$row = $st->fetch();

if ($row && (is_admin($user) || (int)$row['created_by'] === (int)$user['id'])) {
    pdo_app()->prepare("DELETE FROM berita WHERE id = ?")->execute([$id]);
    delete_upload($row['front_image']);
    delete_upload($row['main_image']);
    flash('Berita berhasil dihapus.');
} else {
    flash('Tidak berhak menghapus berita ini.', 'danger');
}
header('Location: index.php');
