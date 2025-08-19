// login.js - Sistema AJAX con SweetAlert2 para DIGI

document.addEventListener("DOMContentLoaded", function () {
  const formulario = document.getElementById("formularioLogin");
  const botonLogin = document.getElementById("botonLogin");
  const mostrarPasswordBtn = document.getElementById("mostrarPassword");
  const passwordInput = document.getElementById("password");
  const contadorIntentos = document.getElementById("contador-intentos");
  const textoIntentos = document.getElementById("texto-intentos");

  // ===== CONFIGURAR SWEETALERT2 =====
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener("mouseenter", Swal.stopTimer);
      toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
  });

  // ===== MOSTRAR/OCULTAR CONTRASEÑA =====
  if (mostrarPasswordBtn && passwordInput) {
    mostrarPasswordBtn.addEventListener("click", function () {
      const tipo =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", tipo);

      const icono = this.querySelector("i");
      icono.classList.toggle("fa-eye");
      icono.classList.toggle("fa-eye-slash");
    });
  }

  // ===== ENVÍO DEL FORMULARIO CON AJAX =====
  if (formulario) {
    formulario.addEventListener("submit", function (e) {
      e.preventDefault();

      if (botonLogin.disabled) return;

      // Mostrar estado de carga
      botonLogin.classList.add("cargando");
      botonLogin.disabled = true;

      // Preparar datos del formulario
      const formData = new FormData(formulario);
      formData.append("ajax", "1");

      // Enviar petición AJAX
      fetch("login.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // LOGIN EXITOSO
            Toast.fire({
              icon: "success",
              title: "Login exitoso",
              text: `Bienvenido ${data.usuario || ""}. Redirigiendo...`,
            });

            setTimeout(() => {
              window.location.href = data.redirect;
            }, 1500);
          } else if (data.bloqueado) {
            // USUARIO O IP BLOQUEADA
            mostrarAlertaBloqueo(data);
            bloquearFormulario(data.tipo);
          } else {
            // ERROR NORMAL - CREDENCIALES INCORRECTAS
            if (data.intentos && data.restantes) {
              mostrarContadorIntentos(data.intentos, data.restantes);
            }

            // Mostrar toast de error
            const icono = data.intentos >= 3 ? "warning" : "error";
            const titulo =
              data.intentos >= 3
                ? "Advertencia de Seguridad"
                : "Error de Autenticación";

            Toast.fire({
              icon: icono,
              title: titulo,
              text: data.error,
            });

            // Si hay muchos intentos, mostrar advertencia adicional
            if (data.intentos >= 3) {
              setTimeout(() => {
                Toast.fire({
                  icon: "info",
                  title: "Atención",
                  text: `Quedan ${data.restantes} intentos antes del bloqueo`,
                });
              }, 1000);
            }

            // Restaurar el botón
            botonLogin.classList.remove("cargando");
            botonLogin.disabled = false;
            passwordInput.value = "";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Toast.fire({
            icon: "error",
            title: "Error de Conexión",
            text: "Verifique su conexión a internet",
          });

          // Restaurar el botón
          botonLogin.classList.remove("cargando");
          botonLogin.disabled = false;
        });
    });
  }

  // ===== MOSTRAR CONTADOR DE INTENTOS =====
  function mostrarContadorIntentos(intentos, restantes) {
    if (contadorIntentos && textoIntentos) {
      textoIntentos.textContent = `Intento ${intentos} de 5. Quedan ${restantes} intentos.`;
      contadorIntentos.style.display = "block";

      // Auto-ocultar después de 8 segundos
      setTimeout(() => {
        contadorIntentos.style.display = "none";
      }, 8000);
    }
  }

  // ===== MOSTRAR ALERTA DE BLOQUEO =====
  function mostrarAlertaBloqueo(data) {
    const esBloqueoIP = data.tipo === "ip";
    const titulo = esBloqueoIP
      ? "IP Bloqueada por Seguridad"
      : "Usuario Bloqueado";
    const icono = esBloqueoIP ? "warning" : "error";

    let mensaje = data.error;
    if (esBloqueoIP) {
      mensaje +=
        "\n\nEste bloqueo afecta a toda conexión desde esta dirección IP.";
    }

    Swal.fire({
      icon: icono,
      title: titulo,
      text: mensaje,
      confirmButtonColor: "#dc2626",
      allowOutsideClick: false,
      customClass: {
        popup: "bloqueo-popup",
      },
      footer: esBloqueoIP
        ? "<small>Si considera que esto es un error, contacte al administrador del sistema.</small>"
        : "<small>El acceso se restaurará automáticamente cuando expire el tiempo de bloqueo.</small>",
    });
  }

  // ===== BLOQUEAR FORMULARIO =====
  function bloquearFormulario(tipo = "usuario") {
    const inputs = formulario.querySelectorAll("input");
    inputs.forEach((input) => (input.disabled = true));

    const textoBoton = botonLogin.querySelector(".texto-boton");
    const esBloqueoIP = tipo === "ip";

    if (esBloqueoIP) {
      textoBoton.innerHTML =
        '<i class="fas fa-ban"></i> IP Bloqueada Temporalmente';
    } else {
      textoBoton.innerHTML = '<i class="fas fa-lock"></i> Usuario Bloqueado';
    }

    botonLogin.disabled = true;
    botonLogin.classList.remove("cargando");
    botonLogin.classList.add("bloqueado");

    // Ocultar contador de intentos si está visible
    if (contadorIntentos) {
      contadorIntentos.style.display = "none";
    }
  }

  // ===== VALIDACIÓN EN TIEMPO REAL =====
  const inputs = formulario.querySelectorAll("input[required]");
  inputs.forEach((input) => {
    input.addEventListener("blur", function () {
      if (!this.value.trim()) {
        this.style.borderColor = "#dc2626";
        this.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.1)";
      } else {
        this.style.borderColor = "";
        this.style.boxShadow = "";
      }
    });

    input.addEventListener("input", function () {
      if (this.style.borderColor === "rgb(220, 38, 38)") {
        this.style.borderColor = "";
        this.style.boxShadow = "";
      }
    });
  });

  // ===== PROTECCIONES DE SEGURIDAD =====

  // Anti-clickjacking
  if (top !== self) {
    top.location = self.location;
  }

  // Limpiar campos sensibles al salir
  window.addEventListener("beforeunload", () => {
    if (passwordInput) {
      passwordInput.value = "";
    }
  });

  // Detectar múltiples intentos en la misma pestaña
  let intentosEnTab = 0;
  const maxIntentosTab = 3;

  formulario.addEventListener("submit", () => {
    intentosEnTab++;
    if (intentosEnTab > maxIntentosTab) {
      Toast.fire({
        icon: "warning",
        title: "Múltiples Intentos Detectados",
        text: "Evite realizar múltiples intentos simultáneos",
      });
    }
  });

  // Reset contador después de un tiempo
  setInterval(() => {
    intentosEnTab = Math.max(0, intentosEnTab - 1);
  }, 60000);

  // ===== FUNCIONES PARA MENSAJES DE URL =====

  // Función para mostrar mensaje de éxito (llamada desde PHP)
  window.mostrarMensajeExito = function (mensaje) {
    Toast.fire({
      icon: "success",
      title: "Información",
      text: mensaje,
    });
  };

  // Función para mostrar mensaje de error (llamada desde PHP)
  window.mostrarMensajeError = function (mensaje) {
    Toast.fire({
      icon: "error",
      title: "Atención",
      text: mensaje,
    });
  };

  // ===== INFORMACIÓN DEL SISTEMA =====
  console.log("DIGI - Sistema de Login Seguro Iniciado");
  console.log(
    "Protecciones activas: Anti-clickjacking, Rate limiting, Bloqueo por IP"
  );
  // ===== NAVBAR - TOGGLE Y DROPDOWN =====
  const toggleButton = document.querySelector(".mobile-toggle");
  const nav = document.querySelector(".navbar-nav");
  const dropdowns = document.querySelectorAll(".nav-dropdown");

  // Mostrar/ocultar menú móvil
  if (toggleButton && nav) {
    toggleButton.addEventListener("click", () => {
      if (window.innerWidth <= 1024) {
        nav.classList.toggle("active");
      }
    });
  }

  // Cerrar menú móvil al hacer resize a escritorio
  window.addEventListener("resize", () => {
    if (window.innerWidth > 1024 && nav?.classList.contains("active")) {
      nav.classList.remove("active");
    }
  });

  // Funcionalidad de dropdowns
  dropdowns.forEach((dropdown) => {
    const toggle = dropdown.querySelector(".dropdown-toggle");
    const menu = dropdown.querySelector(".dropdown-menu");

    if (toggle && menu) {
      toggle.addEventListener("click", (e) => {
        e.preventDefault();

        // Cerrar otros dropdowns abiertos
        dropdowns.forEach((d) => {
          if (d !== dropdown) {
            d.classList.remove("active");
          }
        });

        dropdown.classList.toggle("active");
      });
    }
  });

  // Cerrar dropdown si haces clic fuera
  document.addEventListener("click", (e) => {
    dropdowns.forEach((dropdown) => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove("active");
      }
    });
  });

});
