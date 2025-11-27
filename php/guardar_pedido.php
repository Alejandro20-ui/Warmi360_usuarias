<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../backend/conexion.php';

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Error: conexión a BD fallida']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['cantidad'], $data['nombre_completo'], $data['direccion'], $data['metodo_pago'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO pedidos (id_usuario, cantidad, nombre_completo, direccion, metodo_pago, estado, fecha)
        VALUES (:id_usuario, :cantidad, :nombre, :direccion, :metodo, 'pendiente', NOW())
    ");
    $stmt->bindValue(':id_usuario', (int)$_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':cantidad', (int)$data['cantidad'], PDO::PARAM_INT);
    $stmt->bindValue(':nombre', $data['nombre_completo'], PDO::PARAM_STR);
    $stmt->bindValue(':direccion', $data['direccion'], PDO::PARAM_STR);
    $stmt->bindValue(':metodo', $data['metodo_pago'], PDO::PARAM_STR);
    
    $stmt->execute();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar']);
}