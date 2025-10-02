<?php
include_once("../config.php");
// Permitir CORS (evita bloqueos si accedés desde localhost u otro origen)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');


// Función para conectar a la base de datos
function connectDB($config_tinkuy) {
    try {
        $dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";
        $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $conn;
    } catch (PDOException $e) {
        error_log('Error de conexión PDO: ' . $e->getMessage());
        throw new Exception('Error al conectar con la base de datos. Por favor, intente más tarde.');
    }
}

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Verificar si se recibieron datos JSON
    $input = file_get_contents('php://input');
    if (strlen($input) > 0 && json_decode($input) !== null) {
        $_POST = json_decode($input, true);
    }

    // Validar campos requeridos
    $required = ['email', 'new-username', 'new-password', 'confirm-password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $response['errors'][] = ucfirst(str_replace('-', ' ', $field)) . ' es requerido';
        }
    }

    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit();
    }

    // Obtener y limpiar datos
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $usuario = substr(trim($_POST['new-username']), 0, 50);
    $password = $_POST['new-password'];
    $confirm_password = $_POST['confirm-password'];

    // Validaciones
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = 'Email no válido';
    }

    if (strlen($usuario) < 3) {
        $response['errors'][] = 'Usuario debe tener al menos 3 caracteres';
    }

    if (strlen($password) < 8) {
        $response['errors'][] = 'Contraseña debe tener al menos 8 caracteres';
    }

    if ($password !== $confirm_password) {
        $response['errors'][] = 'Las contraseñas no coinciden';
    }

    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit();
    }

    // Conectar a la base de datos
    $conn = connectDB($config_tinkuy);

    // Verificar si usuario o email existen
    $sql_check = "SELECT COUNT(*) FROM usuarios WHERE email = :email OR usuario = :usuario";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':email' => $email, ':usuario' => $usuario]);
    $exists = $stmt_check->fetchColumn();

    if ($exists > 0) {
        // Verificar cuál exactamente existe
        $sql_check = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([':email' => $email]);
        $response['errors'][] = $stmt_check->fetchColumn() > 0 
            ? 'El email ya está registrado' 
            : 'El nombre de usuario ya está en uso';
        echo json_encode($response);
        exit();
    }

    // Hashear contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $sql_insert = "INSERT INTO usuarios (usuario, password, email, permisos) VALUES (:usuario, :password, :email, 'basico')";
    $stmt_insert = $conn->prepare($sql_insert);
    $result = $stmt_insert->execute([
        ':usuario' => $usuario,
        ':password' => $passwordHash,
        ':email' => $email
    ]);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Registro exitoso';
    } else {
        $response['errors'][] = 'Error al registrar el usuario';
    }

} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage());
    $response['errors'][] = 'Error de base de datos. Por favor, intente más tarde.';
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    $response['errors'][] = $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn = null; // Cerrar conexión
    }
}

echo json_encode($response);


// Restablecer contraseña

