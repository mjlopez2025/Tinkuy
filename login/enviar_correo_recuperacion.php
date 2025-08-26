<?php
include_once("../config.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Establecer cabecera JSON
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar ambos campos
$usuario = trim($_POST['usuario'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validar que ambos campos estén presentes
if (empty($usuario) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Debe completar ambos campos']);
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

// Buscar usuario que coincida con AMBOS campos
try {
    // CAMBIADO: usar 'usuario' en lugar de 'username'
    $stmt = $conn->prepare("SELECT id, usuario, email FROM usuarios WHERE usuario = :usuario AND email = :email");
    $stmt->execute([
        ':usuario' => $usuario,
        ':email' => $email
    ]);
    $usuario_data = $stmt->fetch();

    if (!$usuario_data) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario y correo electrónico no coinciden']);
        exit;
    }

    // Generar token
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Guardar en BD
    $stmt = $conn->prepare("UPDATE usuarios 
                            SET reset_token = :token, reset_expires = :expira 
                            WHERE id = :id");
    $stmt->execute([
        ':token' => $token,
        ':expira' => $expira,
        ':id' => $usuario_data['id']
    ]);

    // Enviar email
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sudocu@undav.edu.ar';
    $mail->Password   = 'cxro vfjw xnni lovd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('Tinkuy@undav.edu.ar', 'Soporte Tinkuy');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Restablecer contraseña - Tinkuy";
    $enlace = "http://172.16.1.58/Tinkuy/login/restablecer_contrasena.php?token=" . $token;
    
    // CAMBIADO: usar 'usuario' en lugar de 'username'
    $mail->Body = "
        <h2>Restablecer contraseña</h2>
        <p>Hola " . htmlspecialchars($usuario_data['usuario']) . ",</p>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
        <p><a href='$enlace' style='background-color: #3085d6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Restablecer contraseña</a></p>
        <p>O copia este enlace en tu navegador:<br>$enlace</p>
        <p><strong>Este enlace expira en 1 hora.</strong></p>
        <br>
        <p>Saludos,<br>Equipo Tinkuy - UNDAV</p>
    ";

    $mail->send();
    
    // ÉXITO - Devolver JSON
    echo json_encode([
        'success' => true, 
        'message' => 'Se ha enviado un enlace para restablecer tu contraseña a tu correo electrónico.'
    ]);

} catch (Exception $e) {
    // ERROR - Devolver JSON con el error
    error_log("Error en recuperación: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar la solicitud. Por favor, intente nuevamente.'
    ]);
}

exit;
?>