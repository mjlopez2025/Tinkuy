<?php
include_once("../config.php");
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

// Validaciones básicas
if (empty($token) || empty($password) || empty($confirmar)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if ($password !== $confirmar) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}

try {
    // Verificar token válido y no expirado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token = :token AND reset_expires > NOW()");
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
        exit;
    }

    // Hashear la nueva contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Actualizar contraseña y limpiar token
    $stmt = $conn->prepare("UPDATE usuarios SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id");
    $stmt->execute([
        ':password' => $hashedPassword,
        ':id' => $usuario['id']
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión con tu nueva contraseña.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar la contraseña: ' . $e->getMessage()
    ]);
}
?>