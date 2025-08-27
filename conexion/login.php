<?php
// ================================================================
// ARCHIVO DE PROCESAMIENTO DE LOGIN - AGROMATCH
// Ruta: conexion/login.php
// ================================================================

session_start();
require_once 'conexion.php';

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vista/login-trabajador.html');
    exit();
}

try {
    $conn = getConnection();
    
    // Recoger datos del formulario
    $email = trim($_POST['email']);
    $password = $_POST['contrasena']; // Cambiado de 'password' a 'contrasena' según tu HTML
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        throw new Exception('Por favor completa todos los campos.');
    }
    
    // Buscar usuario por email o por teléfono
    $stmt = $conn->prepare("
        SELECT u.ID_Usuario, u.Nombre, u.Apellido, u.Correo, u.Contraseña, u.Rol, u.Estado
        FROM Usuario u 
        WHERE u.Correo = ? OR u.Teléfono = ?
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Credenciales incorrectas.');
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['Contraseña'])) {
        throw new Exception('Credenciales incorrectas.');
    }
    
    // Verificar que el usuario esté activo
    if ($user['Estado'] !== 'Activo') {
        throw new Exception('Tu cuenta está inactiva. Contacta al administrador.');
    }
    
    // Crear sesión
    $_SESSION['user_id'] = $user['ID_Usuario'];
    $_SESSION['user_role'] = $user['Rol'];
    $_SESSION['user_name'] = $user['Nombre'] . ' ' . $user['Apellido'];
    $_SESSION['user_email'] = $user['Correo'];
    
    // Redireccionar según el rol - CORREGIDAS LAS RUTAS
    switch ($user['Rol']) {
        case 'Trabajador':
            header('Location: ../vista/index-trabajador.html');
            break;
        case 'Agricultor':
            header('Location: ../vista/index-agricultor.html');
            break;
        case 'Administrador':
            header('Location: ../vista/dashboard-admin.html'); // Si tienes este archivo
            break;
        default:
            throw new Exception('Rol de usuario no válido.');
    }
    exit();
    
} catch (Exception $e) {
    // Redireccionar con error
    $error_message = urlencode($e->getMessage());
    header('Location: ../vista/login-trabajador.html?message=' . $error_message . '&type=error');
    exit();
}
?>