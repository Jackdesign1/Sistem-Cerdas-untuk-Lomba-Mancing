<?php
header('Content-Type: application/json');
require_once __DIR__ . '/env_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gunakan method POST'
    ]);
    exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nama = trim($_POST['nama_peserta'] ?? '');

if ($id <= 0 || $nama === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'ID dan nama_peserta wajib diisi'
    ]);
    exit;
}

try {
    $sql = "UPDATE data_ikan
            SET nama_peserta = :nama, is_confirmed = 1
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama' => $nama,
        ':id'   => $id,
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Data peserta berhasil diperbarui'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
