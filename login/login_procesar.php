<?php
include_once("../config.php");


try {
    $dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener JSON del frontend
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['usuario']) || empty($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos de usuario o contraseña']);
    exit;
}

$usuario = trim($input['usuario']);
$password = $input['password'];

// Verificar usuario
$sql = "SELECT password FROM usuarios WHERE usuario = :usuario LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':usuario' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
}
