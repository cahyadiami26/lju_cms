<?php
require_once __DIR__ . '/../guard.php';
$user = require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('Hanya admin yang dapat mengubah status.', 'danger');
    header('Location: index.php'); exit;
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$st = pdo_app()->prepare("SELECT status FROM cta_jadwal WHERE id = ?");
$st->execute([$id]);
$row = $st->fetch();

if ($row) {
    $new = $row['status'] === 'published' ? 'draft' : 'published';
    pdo_app()->prepare("UPDATE cta_jadwal SET status = ?, published_at = "
        . ($new === 'published' ? 'NOW()' : 'NULL') . " WHERE id = ?")->execute([$new, $id]);
    flash('Status jadwal diubah menjadi ' . $new . '.');
}
header('Location: index.php');
