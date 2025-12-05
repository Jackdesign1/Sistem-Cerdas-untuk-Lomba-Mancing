<?php
header('Content-Type: application/json');
require_once __DIR__ . '/env_db.php';

try {
    // Ambil 1 data terbaru yang belum dikonfirmasi
    $sql = "SELECT id, nama_peserta, berat, panjang, `time`, `timestamp`
            FROM data_ikan
            WHERE is_confirmed = 0
            ORDER BY id ASC
            LIMIT 1";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode([
            'status' => 'empty'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'ok',
        'data'   => $row
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
