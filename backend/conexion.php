<?php
$host = getenv('MYSQL_HOST') ?: 'maglev.proxy.rlwy.net';
$db = getenv('MYSQL_DATABASE') ?: 'alertamujer';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: 'CZhVEBZHQRoZvxHsUoPlOrWgSTXnacGc';
$port = getenv('MYSQL_PORT') ?: '50204';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass);
    
    // Configuración segura de PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error al conectar con la base de datos");
}