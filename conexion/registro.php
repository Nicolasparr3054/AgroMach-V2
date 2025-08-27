<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireccionar según el referer o a una página por defecto
    $referer = $_SERVER['HTTP_REFERER'] ?? '../vista/registro-trabajador.html';
    header("Location: $referer");
    exit();
}

try {
    $conn = getConnection();
    
    // Obtener y limpiar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $rol = trim($_POST['rol'] ?? ''); // "Trabajador" o "Agricultor"
    
    // Validación de campos obligatorios
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    } elseif (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $nombre)) {
        $errores[] = 'El nombre solo puede contener letras y espacios';
    }
    
    if (empty($apellido)) {
        $errores[] = 'El apellido es obligatorio';
    } elseif (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $apellido)) {
        $errores[] = 'El apellido solo puede contener letras y espacios';
    }
    
    if (empty($correo)) {
        $errores[] = 'El correo es obligatorio';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del correo electrónico no es válido';
    }
    
    if (empty($password)) {
        $errores[] = 'La contraseña es obligatoria';
    } elseif (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener mínimo 8 caracteres';
    }
    
    if (empty($confirm_password)) {
        $errores[] = 'Debe confirmar la contraseña';
    } elseif ($password !== $confirm_password) {
        $errores[] = 'Las contraseñas no coinciden';
    }
    
    if (empty($rol)) {
        $errores[] = 'No se pudo determinar el tipo de usuario';
    } elseif (!in_array($rol, ['Trabajador', 'Agricultor'])) {
        $errores[] = 'Tipo de usuario no válido';
    }
    
    // Validar términos y condiciones
    if (!isset($_POST['terminos']) || $_POST['terminos'] !== 'on') {
        $errores[] = 'Debe aceptar los términos y condiciones';
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        throw new Exception(implode('<br>', $errores));
    }
    
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT ID_Usuario FROM Usuario WHERE Correo = ?");
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        // Determinar el enlace de login según el rol
        $login_link = ($rol === 'Agricultor') ? 
            '../vista/login-agricultor.html' : 
            '../vista/login-trabajador.html';
        throw new Exception("El correo electrónico ya está registrado. <a href=\"$login_link\">¿Ya tienes cuenta? Inicia sesión aquí</a>");
    }
    
    // Encriptar contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO Usuario (Nombre, Apellido, Correo, Contraseña, Teléfono, Rol) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $apellido, $correo, $hashedPassword, $telefono, $rol]);
    
    $userId = $conn->lastInsertId();
    
    // Insertar en tabla específica según el rol
    if ($rol === 'Trabajador') {
        $stmt = $conn->prepare("INSERT INTO Trabajador (ID_Trabajador, Experiencia, Habilidades) VALUES (?, ?, ?)");
        $stmt->execute([$userId, 'Sin experiencia registrada', 'Sin habilidades registradas']);
        $redirect_url = '../vista/login-trabajador.html';
        $tipo_usuario = 'trabajador';
    } elseif ($rol === 'Agricultor') {
        $stmt = $conn->prepare("INSERT INTO Agricultor (ID_Agricultor, Nombre_Finca, Descripcion) VALUES (?, ?, ?)");
        $stmt->execute([$userId, 'Sin nombre de finca registrado', 'Sin descripción registrada']);
        $redirect_url = '../vista/login-agricultor.html';
        $tipo_usuario = 'agricultor';
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Establecer sesión de usuario
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_rol'] = $rol;
    $_SESSION['user_nombre'] = $nombre . ' ' . $apellido;
    $_SESSION['user_correo'] = $correo;
    
    // Redirección con mensaje de éxito personalizado
    $mensaje_exito = "¡Registro exitoso $nombre! Tu cuenta como $tipo_usuario fue creada. Ahora puedes iniciar sesión con tu correo y contraseña.";
    header("Location: $redirect_url?message=" . urlencode($mensaje_exito) . "&type=success");
    exit();
    
} catch (Exception $e) {
    // Rollback si hay transacción activa
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Determinar la URL de retorno según el rol o usar el referer
    $return_url = '../vista/registro-trabajador.html'; // Por defecto
    
    if (!empty($rol)) {
        $return_url = ($rol === 'Agricultor') ? 
            '../vista/registro-agricultor.html' : 
            '../vista/registro-trabajador.html';
    } else {
        // Si no hay rol, usar el referer para determinar dónde regresar
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'registro-agricultor.html') !== false) {
            $return_url = '../vista/registro-agricultor.html';
        }
    }
    
    // Redireccionar de vuelta al formulario con el mensaje de error
    $error_message = urlencode($e->getMessage());
    header("Location: $return_url?message=$error_message&type=error");
    exit();
}
?>