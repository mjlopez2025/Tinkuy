<?php
include_once("../config.php");

if (!isset($_GET['token'])) {
    die("Token no válido.");
}

$token = $_GET['token'];
// CAMBIÉ LA CONSULTA: Ahora también selecciona el nombre de usuario
$stmt = $conn->prepare("SELECT id, reset_expires, usuario FROM usuarios WHERE reset_token = :token");
$stmt->execute([':token' => $token]);
$usuario = $stmt->fetch();

if (!$usuario || strtotime($usuario['reset_expires']) < time()) {
    die("Enlace inválido o expirado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Agregar SweetAlert CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="login.css">
  <title>Restablecer Contraseña | Tinkuy</title>
</head>
<body>
  <div class="container1">
    <div class="logo-container">
      <div class="image-side">
        <img src="../imagenes/logo.png" alt="Logo UNDAV" class="logo-undav">
      </div>
      <div class="tincuy">
        <p style="font-size:10pt;font-weight:normal;">
          Tinkuy: Del quechua, "Encuentro" o "Unión armónica". <br>
          En la tradición andina, representa la convergencia de fuerzas complementarias para crear algo superior.
        </p>
      </div>
    </div>
    <div class="login-container">
      <h1>Restablecer Contraseña</h1>
      <form id="resetForm" autocomplete="off">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <!-- NUEVO CAMPO: Nombre de usuario (solo lectura) -->
        <div class="input-group">
          <label for="username">Nombre de usuario</label>
          <div class="input-with-icon">
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" readonly autocomplete="off">
            <i class="fas fa-user icon"></i>
          </div>
          <small style="color: #666; font-size: 12px;">Este campo no se puede modificar</small>
        </div>

        <div class="input-group">
          <label for="password">Nueva contraseña</label>
          <div class="input-with-icon">
            <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8" autocomplete="new-password">
            <i class="fas fa-lock icon"></i>
            <span class="toggle-password" onclick="togglePassword('password')">
              <i class="fas fa-eye"></i>
            </span>
          </div>
          <div class="password-strength-meter">
            <div class="strength-bar"></div>
            <span class="strength-text">Seguridad: <span id="strength-text">Débil</span></span>
          </div>
        </div>

        <div class="input-group">
          <label for="confirmar">Repetir contraseña</label>
          <div class="input-with-icon">
            <input type="password" id="confirmar" name="confirmar" placeholder="Vuelve a escribir tu contraseña" required autocomplete="new-password">
            <i class="fas fa-lock icon"></i>
            <span class="toggle-password" onclick="togglePassword('confirmar')">
              <i class="fas fa-eye"></i>
            </span>
          </div>
          <span id="password-match-message" class="message"></span>
        </div>

        <button type="submit" class="btn-login" id="submitBtn">
          <span class="btn-text">Actualizar contraseña</span>
        </button>
        
        <div class="register-link">
          <p>¿Recordaste tu contraseña? <a href="index.html" class="register-anchor">Inicia sesión aquí</a></p>
        </div>
      </form>
    </div>
  </div>
  
  <p class="footer-text">TINKUY v.1.0 &copy; 2025 - Desarrollado por el Área de Sistemas de la Universidad Nacional de Avellaneda.</p>

  <!-- Agregar SweetAlert JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirmar');
      const strengthText = document.getElementById('strength-text');
      const strengthBar = document.querySelector('.strength-bar');
      const passwordMatchMessage = document.getElementById('password-match-message');
      const resetForm = document.getElementById('resetForm');
      const submitBtn = document.getElementById('submitBtn');
      const btnText = submitBtn.querySelector('.btn-text');

      // Mostrar/ocultar contraseña
      window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling.querySelector('i');
        if (field.type === 'password') {
          field.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          field.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      };

      // Medidor de fortaleza
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Longitud
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Complejidad
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Actualizar UI
        strengthBar.parentElement.classList.remove('password-weak', 'password-medium', 'password-strong');
        
        if (strength <= 2) {
          strengthBar.parentElement.classList.add('password-weak');
          strengthText.textContent = 'Débil';
        } else if (strength <= 4) {
          strengthBar.parentElement.classList.add('password-medium');
          strengthText.textContent = 'Media';
        } else {
          strengthBar.parentElement.classList.add('password-strong');
          strengthText.textContent = 'Fuerte';
        }
      });

      // Validar coincidencia de contraseñas
      confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
          passwordMatchMessage.textContent = 'Las contraseñas no coinciden';
          passwordMatchMessage.style.color = '#e74c3c';
        } else {
          passwordMatchMessage.textContent = 'Las contraseñas coinciden';
          passwordMatchMessage.style.color = '#2ecc71';
        }
      });

      // Validación del formulario
      resetForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Resetear mensajes de error
        clearErrors();

        // Validación básica del cliente
        const errors = [];
        
        if (passwordInput.value !== confirmPasswordInput.value) {
          errors.push('Las contraseñas no coinciden');
        }

        if (passwordInput.value.length < 8) {
          errors.push('La contraseña debe tener al menos 8 caracteres');
        }

        if (errors.length > 0) {
          showError(errors.join('<br>'));
          return;
        }

        // Mostrar estado de carga
        setLoadingState(true);

        try {
          // Enviar datos al servidor
          const response = await fetch('procesar_restablecimiento.php', {
            method: 'POST',
            body: new FormData(this)
          });

          // Verificar si la respuesta es JSON
          const responseText = await response.text();
          let data;
          
          try {
            data = JSON.parse(responseText);
          } catch (e) {
            throw new Error('El servidor respondió con un formato inválido: ' + responseText.substring(0, 100));
          }

          if (!response.ok) {
            throw new Error(data.message || 'Error en el servidor');
          }

          if (data.success) {
            // Éxito - mostrar mensaje y redirigir
            Swal.fire({
              icon: 'success',
              title: '¡Contraseña actualizada!',
              text: data.message || 'Tu contraseña ha sido restablecida correctamente',
              confirmButtonColor: '#3085d6',
            }).then(() => {
              window.location.href = 'index.html?msg=password_updated';
            });
          } else {
            // Mostrar errores del servidor
            showError(data.message || 'Error al actualizar la contraseña');
          }
        } catch (error) {
          console.error('Error al restablecer contraseña:', error);
          showError(error.message || 'Error al conectar con el servidor. Por favor, intente nuevamente.');
        } finally {
          // Restaurar botón
          setLoadingState(false);
        }
      });

      // Función para mostrar errores
      function showError(message) {
        let errorContainer = document.getElementById('error-container');
        
        if (!errorContainer) {
          errorContainer = document.createElement('div');
          errorContainer.id = 'error-container';
          errorContainer.className = 'error-container';
          resetForm.prepend(errorContainer);
        }
        
        errorContainer.innerHTML = `
          <div class="error-content">
            <i class="fas fa-exclamation-circle"></i>
            <div>${message}</div>
          </div>
        `;
        
        // Desplazarse al error
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      // Función para limpiar errores anteriores
      function clearErrors() {
        const errorContainer = document.getElementById('error-container');
        if (errorContainer) {
          errorContainer.remove();
        }
      }

      // Función para manejar estado de carga
      function setLoadingState(isLoading) {
        if (isLoading) {
          submitBtn.disabled = true;
          btnText.innerHTML = '<i class="fas fa-spinner spinner"></i> Actualizando...';
        } else {
          submitBtn.disabled = false;
          btnText.textContent = 'Actualizar contraseña';
        }
      }
    });
  </script>

  <style>
    /* Estilo adicional para el campo de solo lectura */
    #username {
      background-color: #f8f9fa;
      cursor: not-allowed;
    }
    
    #username:focus {
      border-color: #ddd;
      box-shadow: none;
    }
  </style>
</body>
</html>