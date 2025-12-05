<?php
header('Content-Type: application/json');

require_once __DIR__ . '/env_db.php';   

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method not allowed, gunakan POST'
    ]);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($data === null) {
    $data = $_POST;
}

$berat       = $data['berat_kg']     ?? ($data['berat'] ?? null);
$panjang     = $data['jarak_cm']     ?? ($data['panjang'] ?? null);
$namaPeserta = $data['nama_peserta'] ?? null;
$timeMs      = $data['time']         ?? null;   

if ($berat === null || $namaPeserta === null) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Field berat_kg dan nama_peserta wajib diisi'
    ]);
    exit;
}
$timestampNow = date('Y-m-d H:i:s');
try {
    $sql = "INSERT INTO data_ikan (nama_peserta, `time`, berat, panjang, timestamp)
            VALUES (:nama_peserta, :time, :berat, :panjang, :timestamp)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama_peserta' => $namaPeserta,
        ':time'         => $timeMs,
        ':berat'        => $berat,
        ':panjang'      => $panjang,
        ':timestamp'    => $timestampNow,
    ]);

    $newId = $pdo->lastInsertId();


    $stmt2 = $pdo->prepare("SELECT `timestamp` FROM data_ikan WHERE id = :id");
    $stmt2->execute([':id' => $newId]);
    $rowTs = $stmt2->fetch();
    $timestamp = $rowTs ? $rowTs['timestamp'] : null;

    echo json_encode([
        'status'  => 'success',
        'message' => 'Data ikan berhasil disimpan',
        'data'    => [
            'id'           => $newId,
            'nama_peserta' => $namaPeserta,
            'time'         => $timeMs,     
            'timestamp'    => $timestamp,   
            'berat'        => $berat,
            'panjang'      => $panjang,
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
