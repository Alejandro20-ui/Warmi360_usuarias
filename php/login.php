<?php
session_start();

session_regenerate_id(true);

if (isset($_SESSION['user_id'])) {
    header('Location: principal.php');
    exit;
}

require_once __DIR__ . '/../backend/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // === 1. PROTECCIÓN CSRF (Token opcional pero recomendado) ===
    // Descomenta si implementas tokens CSRF en tu formulario
    /*
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: ../login.html?error=invalid_token');
        exit;
    }
    */

    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if (empty($nombre) || empty($apellidos) || empty($correo)) {
        header('Location: ../login.html?error=empty');
        exit;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../login.html?error=invalid_email');
        exit;
    }

    if (strlen($nombre) > 100 || strlen($apellidos) > 100 || strlen($correo) > 150) {
        header('Location: ../login.html?error=invalid_length');
        exit;
    }

    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $apellidos = htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8');
    $correo = filter_var($correo, FILTER_SANITIZE_EMAIL);

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }

    if (time() - $_SESSION['last_attempt_time'] > 900) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= 5) {
        header('Location: ../login.html?error=blocked');
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT id, nombre, apellidos, correo 
            FROM usuarios 
            WHERE nombre = :nombre 
            AND apellidos = :apellidos 
            AND correo = :correo
            LIMIT 1
        ");
        
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['login_attempts'] = 0;
            
            $log_stmt = $conn->prepare("
                INSERT INTO logs_acceso (usuario_id, ip_address, fecha) 
                VALUES (:user_id, :ip, NOW())
            ");
            $log_stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
            $log_stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
            $log_stmt->execute();

            header('Location: principal.php');
            exit;
            
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            header('Location: ../login.html?error=invalid');
            exit;
        }
        
    } catch (PDOException $e) {
        error_log("Error de login: " . $e->getMessage());
        header('Location: ../login.html?error=system');
        exit;
    }
    
} else {
    header('Location: ../login.html');
    exit;
}