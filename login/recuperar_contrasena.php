<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Agregar SweetAlert CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="login.css">
  <title>Recuperar Contraseña | Tinkuy</title>
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
    <div class="login-container recuperar-card">
      <h1>¿Olvidaste tu contraseña?</h1>
      <p class="sub">
        Ingresá tu nombre de usuario y correo electrónico para enviarte un enlace de recuperación.
      </p>

      <form id="recuperarForm" class="form-recuperar" autocomplete="off">
        <!-- NUEVO CAMPO: USUARIO -->
        <div class="input-group">
          <label for="usuario">Nombre de Usuario</label>
          <div class="input-with-icon">
            <input type="text" id="usuario" name="usuario" placeholder="Tu nombre de usuario" required autocomplete="off">
            <i class="fas fa-user icon"></i>
          </div>
        </div>

        <div class="input-group">
          <label for="email">Correo Electrónico</label>
          <div class="input-with-icon">
            <input type="email" id="email" name="email" placeholder="tucorreo@undav.edu.ar" required autocomplete="off">
            <i class="fas fa-envelope icon"></i>
          </div>
        </div>

        <button type="submit" class="btn-login" id="btnEnviar">
          <span class="btn-text">Enviar enlace</span>
        </button>

        <div class="register-link">
          <p>
            <a href="index.html" class="register-anchor">
              <i class="fas fa-arrow-left" style="margin-right:6px;"></i> Volver al inicio de sesión
            </a>
          </p>
        </div>
      </form>
    </div>
  </div>

  <p class="footer-text">TINKUY v.1.0 &copy; 2025 - Desarrollado por el Área de Sistemas de la Universidad Nacional de Avellaneda.</p>

  <!-- Agregar SweetAlert JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('recuperarForm');
    const btn = document.getElementById('btnEnviar');
    const btnText = btn.querySelector('.btn-text');

    if (form) form.reset();

    form.addEventListener('submit', function (e) {
      e.preventDefault(); // Prevenir el envío normal del formulario
      
      btn.disabled = true;
      btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
      
      // Obtener los valores
      const usuario = document.getElementById('usuario').value;
      const email = document.getElementById('email').value;
      
      // Crear FormData para enviar
      const formData = new FormData();
      formData.append('usuario', usuario);
      formData.append('email', email);
      
      // Enviar datos con fetch
      fetch('enviar_correo_recuperacion.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        // Primero intentar parsear como JSON
        return response.text().then(text => {
          try {
            return text ? JSON.parse(text) : {}
          } catch {
            return {success: false, message: text || 'Respuesta no válida del servidor'}
          }
        })
      })
      .then(data => {
        if (data.success) {
          // Éxito: correo enviado
          Swal.fire({
            icon: 'success',
            title: '¡Correo enviado!',
            text: 'Se ha enviado un enlace para restablecer tu contraseña a tu correo electrónico.',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
          });
          form.reset(); // Limpiar el formulario después del éxito
        } else {
          // Error: credenciales no coinciden u otro error
          let errorMessage = data.message || 'Ocurrió un error al enviar el correo.';
          
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Entendido'
          });
        }
      })
      .catch(error => {
        // Error de red o del servidor
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'No se pudo conectar con el servidor. Por favor, intenta nuevamente.',
          confirmButtonColor: '#d33',
          confirmButtonText: 'Entendido'
        });
        console.error('Error:', error);
      })
      .finally(() => {
        // Restablecer el botón
        btn.disabled = false;
        btnText.textContent = 'Enviar enlace';
      });
    });
  });
</script>
</body>
</html>