<?php
require_once __DIR__ . '/guard.php';
require_user();          // hanya Infokom yang bisa upload
verify_csrf();           // via header X-CSRF-Token dari editor

header('Content-Type: application/json');
try {
    $path = save_image($_FILES['file'] ?? [], 'tinymce');
    if (!$path) throw new RuntimeException('Tidak ada file yang diunggah.');
    echo json_encode(['location' => BASE_URL . '/' . $path]); // format TinyMCE
} catch (Throwable $ex) {
    http_response_code(400);
    echo json_encode(['error' => $ex->getMessage()]);
}
