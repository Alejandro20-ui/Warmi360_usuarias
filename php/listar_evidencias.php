<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

require_once __DIR__ . '/../backend/conexion.php';

try {
    $stmt = $conn->prepare("
        SELECT id, tipo, nombre_archivo, fecha_captura, tamano_bytes
        FROM evidencias 
        WHERE user_id = :user_id 
        ORDER BY fecha_captura DESC
    ");
    $stmt->bindValue(':user_id', (int)$_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $evidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($evidencias)) {
        echo json_encode(['message' => 'No hay evidencias registradas']);
    } else {
        echo json_encode($evidencias);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al cargar evidencias']);
}