<?php

$DB_HOST    = 'localhost';
$DB_NAME    = 'db_spk_ikan';
$DB_USER    = 'root';    
$DB_PASS    = '';          
$DB_CHARSET = 'utf8mb4';

try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ]);
    exit;
}
