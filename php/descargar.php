<?php
session_start();
require_once __DIR__ . '/../backend/conexion.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso denegado');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    die('ID no especificado');
}

$id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

// Consultar solo si pertenece al usuario
$stmt = $conn->prepare("
    SELECT tipo, archivo, nombre_archivo 
    FROM evidencias 
    WHERE id = :id AND user_id = :user_id
");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$evidencia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evidencia || empty($evidencia['archivo'])) {
    http_response_code(404);
    die('Evidencia no encontrada o sin archivo');
}

$mime = match($evidencia['tipo']) {
    'foto' => preg_match('/\.jpe?g$/i', $evidencia['nombre_archivo']) ? 'image/jpeg' : 'image/png',
    'video' => 'video/mp4',
    default => 'application/octet-stream'
};
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . $evidencia['nombre_archivo'] . '"');
} else {
    header('Content-Disposition: inline; filename="' . $evidencia['nombre_archivo'] . '"');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($evidencia['archivo']));

echo $evidencia['archivo'];
exit;