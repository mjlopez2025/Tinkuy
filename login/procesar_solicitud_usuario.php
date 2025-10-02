<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CONFIGURAR ZONA HORARIA ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');

// FORZAR CODIFICACIÓN UTF-8
mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$nombreCompleto = trim($input['nombreCompleto'] ?? '');
$documento = trim($input['documento'] ?? '');
$email = trim($input['email'] ?? '');

// Validaciones...
if (empty($nombreCompleto) || empty($documento) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El email no tiene un formato válido']);
    exit;
}

if (!is_numeric($documento)) {
    echo json_encode(['success' => false, 'message' => 'El documento debe contener solo números']);
    exit;
}

try {
    $mail = new PHPMailer(true);
    
    // CONFIGURAR ENCODING EN PHPMAILER
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sudocu@undav.edu.ar';
    $mail->Password   = 'cxro vfjw xnni lovd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // From fijo + Reply-To personalizado
    $mail->setFrom('Tinkuy@undav.edu.ar', 'Sistema Tinkuy - UNDAV');
    $mail->addAddress('mjlopez@undav.edu.ar'); // <- TU EMAIL
    $mail->addAddress('cvidoni@undav.edu.ar');        
    $mail->addAddress('blazarte@undav.edu.ar');
    $mail->addReplyTo($email, $nombreCompleto);

    $mail->isHTML(true);
    
    // ASUNTO CON CODIFICACIÓN CORRECTA
    $mail->Subject = "🔔 Solicitud de Usuario: " . $nombreCompleto . " - Tinkuy";

    // FECHA Y HORA ACTUAL CORRECTA
    $fechaHoraActual = date('d/m/Y H:i:s');

    $mail->Body = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <style>
            /* TUS ESTILOS HERMOSOS AQUÍ */
            body { 
                font-family: 'Arial', sans-serif; 
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #3498db, #2c3e50);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 15px;
            }
            .content {
                padding: 30px;
            }
            .data-card {
                background: #f8f9fa;
                border-left: 4px solid #3498db;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .data-item {
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .data-item strong {
                color: #2c3e50;
                min-width: 150px;
            }
            .data-item i {
                color: #3498db;
                width: 20px;
                text-align: center;
            }
            .footer {
                background: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 14px;
            }
            .urgent {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: center;
                color: #856404;
            }
            .contact-info {
                background: #e8f4fd;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #3498db;
                color: #2c3e50;
            }
            .contact-info a {
                color: #3498db;
                text-decoration: none;
                font-weight: bold;
            }
            .contact-info a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>
                    <span style='font-size: 28px;'>👤</span>
                    Nueva Solicitud de Usuario - Tinkuy
                </h1>
            </div>
            
            <div class='content'>
                <p>Hola <strong>Área de Sistemas</strong>,</p>
                <p>Se ha recibido una nueva solicitud de acceso al sistema Tinkuy:</p>
                
                <div class='data-card'>
                    <div class='data-item'>
                        <i>📝</i>
                        <strong>Nombre completo:</strong>
                        <span>" . htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') . "</span>
                    </div>
                    <div class='data-item'>
                        <i>🆔</i>
                        <strong>Documento:</strong>
                        <span>" . htmlspecialchars($documento, ENT_QUOTES, 'UTF-8') . "</span>
                    </div>
                    <div class='data-item'>
                        <i>📧</i>
                        <strong>Email:</strong>
                        <span>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</span>
                    </div>
                    <div class='data-item'>
                        <i>📅</i>
                        <strong>Fecha y hora:</strong>
                        <span>" . $fechaHoraActual . "</span>
                    </div>
                </div>

                <div class='urgent'>
                    <strong>⚠️ ACCIÓN REQUERIDA</strong><br>
                    Por favor, procede a crear el usuario en el sistema.
                </div>

                <div class='contact-info'>
                    <strong>💡 Para contactar al solicitante:</strong><br>
                    - <strong>Responde este correo directamente</strong> (irá a " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . ")<br>
                    - O escribe a: <a href='mailto:" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</a>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Sistema Tinkuy</strong><br>
                Universidad Nacional de Avellaneda<br>
                Área de Sistemas</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Versión alternativa en texto plano CON UTF-8
    $mail->AltBody = "NUEVA SOLICITUD DE USUARIO - TINKUY\n\n" .
                     "Solicitante: " . $nombreCompleto . "\n" .
                     "Documento: " . $documento . "\n" .
                     "Email: " . $email . "\n" .
                     "Fecha: " . $fechaHoraActual . "\n\n" .
                     "PARA CONTACTAR AL SOLICITANTE:\n" .
                     "Responde este correo directamente (irá a " . $email . ")\n" .
                     "O escribe a: " . $email . "\n\n" .
                     "Sistema Tinkuy - UNDAV - Área de Sistemas";

    // Enviar el correo
    $mail->send();
    
    echo json_encode([
        'success' => true, 
        'message' => '✅ Solicitud enviada correctamente. Te contactaremos pronto.'
    ]);

} catch (Exception $e) {
    error_log("Error en solicitud de usuario: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => '❌ Error al enviar la solicitud. Por favor, intente nuevamente.'
    ]);
}

exit;
?>