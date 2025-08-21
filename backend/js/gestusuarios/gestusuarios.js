/**
 * JavaScript para Gestión de Usuarios - DIGI
 * Versión corregida con declaraciones globales explícitas
 */

// Variables globales - DECLARACIÓN EXPLÍCITA
window.modoEditar = false;
window.modalVisible = false;

// Inicialización cuando la página carga
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM cargado, inicializando eventos...");
  inicializarEventos();
  inicializarSelectize();

  // Verificar que las funciones están disponibles
  console.log("Funciones disponibles:", {
    abrirModal: typeof window.abrirModal,
    editarUsuario: typeof window.editarUsuario,
    cambiarEstado: typeof window.cambiarEstado,
    cambiarTab: typeof window.cambiarTab,
  });
});

// FUNCIÓN ABRIR MODAL - DECLARACIÓN GLOBAL EXPLÍCITA
window.abrirModal = function () {
  console.log("Ejecutando abrirModal()");
  if (window.modalVisible) return;

  window.modoEditar = false;
  limpiarFormulario();

  document.getElementById("tituloModal").textContent = "Crear Usuario";
  document.getElementById("password").required = true;
  document.getElementById("passwordHelp").textContent = "Mínimo 4 caracteres";

  habilitarBusquedaDNI(true);
  mostrarModal();

  // Reinicializar Selectize después de mostrar modal
  setTimeout(() => {
    reinicializarSelectize();
  }, 200);
};

// FUNCIÓN EDITAR USUARIO - DECLARACIÓN GLOBAL EXPLÍCITA
window.editarUsuario = function (id) {
  console.log("Ejecutando editarUsuario() con ID:", id);
  if (window.modalVisible) return;

  window.modoEditar = true;
  document.getElementById("tituloModal").textContent = "Editar Usuario";
  document.getElementById("password").required = false;
  document.getElementById("passwordHelp").textContent =
    "Dejar vacío para mantener la contraseña actual";

  habilitarBusquedaDNI(false);

  const datos = new FormData();
  datos.append("accion", "obtener");
  datos.append("id", id);

  fetch("../../backend/php/gestusuarios/gestusuarios.php", {
    method: "POST",
    body: datos,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        mostrarModal();

        // Reinicializar Selectize y luego llenar datos
        setTimeout(() => {
          reinicializarSelectize();
          setTimeout(() => {
            llenarFormulario(data.usuario);
          }, 150);
        }, 200);
      } else {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: data.message || "Error al obtener datos del usuario",
          showConfirmButton: false,
          timer: 4000,
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        toast: true,
        position: "top-end",
        icon: "error",
        title: "No se pudo conectar con el servidor",
        showConfirmButton: false,
        timer: 4000,
      });
    });
};

// FUNCIÓN CAMBIAR ESTADO - DECLARACIÓN GLOBAL EXPLÍCITA
window.cambiarEstado = function (id, nuevoEstado) {
  console.log("Ejecutando cambiarEstado() con ID:", id, "Estado:", nuevoEstado);
  const accion = nuevoEstado === 1 ? "reactivar" : "desactivar";

  Swal.fire({
    title: "¿Confirmar acción?",
    text: `¿Está seguro de ${accion} este usuario?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#6c5ce7",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, continuar",
    cancelButtonText: "Cancelar",
    backdrop: true,
    allowOutsideClick: true,
  }).then((result) => {
    if (result.isConfirmed) {
      const datos = new FormData();
      datos.append("accion", "cambiar_estado");
      datos.append("id", id);
      datos.append("estado", nuevoEstado);

      fetch("../../backend/php/gestusuarios/gestusuarios.php", {
        method: "POST",
        body: datos,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              toast: true,
              position: "top-end",
              icon: "success",
              title: data.message,
              showConfirmButton: false,
              timer: 1000,
              timerProgressBar: true,
            });
            setTimeout(() => location.reload(), 1000);
          } else {
            Swal.fire({
              toast: true,
              position: "top-end",
              icon: "error",
              title: data.message,
              showConfirmButton: false,
              timer: 3000,
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "error",
            title: "Error de conexión al servidor",
            showConfirmButton: false,
            timer: 3000,
          });
        });
    }
  });
};

// FUNCIÓN CAMBIAR TAB - DECLARACIÓN GLOBAL EXPLÍCITA
window.cambiarTab = function (tab) {
  console.log("Ejecutando cambiarTab() con tab:", tab);
  document.querySelectorAll(".tab-usuario").forEach((btn) => {
    btn.classList.remove("active");
  });
  document.querySelectorAll(".contenido-tab").forEach((contenido) => {
    contenido.classList.remove("active");
  });

  event.target.classList.add("active");
  document.getElementById("contenido-" + tab).classList.add("active");
};

// FUNCIÓN CERRAR MODAL - DECLARACIÓN GLOBAL EXPLÍCITA
window.cerrarModal = function () {
  console.log("Ejecutando cerrarModal()");
  const modal = document.getElementById("modalUsuario");

  if (Swal.isVisible()) {
    Swal.close();
  }

  modal.classList.remove("show");

  setTimeout(() => {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
    document.body.classList.remove("modal-open");
    window.modalVisible = false;
    limpiarFormulario();
  }, 300);
};

// Función para inicializar Selectize en todos los selects
function inicializarSelectize() {
  if (document.getElementById("area")) {
    $("#area").selectize({
      placeholder: "Seleccione un área",
      searchField: ["text"],
      maxItems: 1,
      create: false,
      allowEmptyOption: true,
      preload: true,
      render: {
        option: function (item, escape) {
          return (
            '<div class="option-item">' +
            '<i class="fas fa-building" style="margin-right: 8px; color: #6c5ce7;"></i>' +
            escape(item.text) +
            "</div>"
          );
        },
        item: function (item, escape) {
          return '<div class="selected-item">' + escape(item.text) + "</div>";
        },
      },
      onInitialize: function () {
        this.clear(); // ✅ Limpia cualquier valor seleccionado al iniciar
        this.$control_input.attr("placeholder", "Seleccione un área"); // ✅ Aplica placeholder real en input
      },
      onDropdownOpen: function () {
        this.$dropdown.css("z-index", 99999);
      },
    });
  }

  // Configurar Selectize para rol con estilo mejorado
  if (document.getElementById("rol")) {
    $("#rol").selectize({
      placeholder: "Seleccione un rol",
      searchField: ["text"],
      maxItems: 1,
      create: false,
      allowEmptyOption: false,
      render: {
        option: function (item, escape) {
          // Iconos dinámicos basados en el nombre del rol
          let icono = "fa-user";
          let color = "#3498db";

          const texto = item.text.toLowerCase();
          if (texto.includes("administrador")) {
            icono = "fa-user-shield";
            color = "#e74c3c";
          } else if (texto.includes("supervisor")) {
            icono = "fa-user-tie";
            color = "#f39c12";
          } else if (texto.includes("usuario")) {
            icono = "fa-user";
            color = "#3498db";
          }

          return (
            '<div class="option-item">' +
            '<i class="fas ' +
            icono +
            '" style="margin-right: 8px; color: ' +
            color +
            ';"></i>' +
            escape(item.text) +
            "</div>"
          );
        },
        item: function (item, escape) {
          return '<div class="selected-item">' + escape(item.text) + "</div>";
        },
      },
      onDropdownOpen: function () {
        this.$dropdown.css("z-index", 99999);
      },
    });
  }
}

// Función para reinicializar Selectize después de abrir modal
function reinicializarSelectize() {
  // Destruir instancias existentes
  ["area", "rol"].forEach((id) => {
    const element = document.getElementById(id);
    if (element && element.selectize) {
      element.selectize.destroy();
    }
  });

  // Reinicializar
  setTimeout(() => {
    inicializarSelectize();
  }, 100);
}

// Función para inicializar todos los eventos
function inicializarEventos() {
  const formulario = document.getElementById("formUsuario");
  if (formulario) {
    formulario.addEventListener("submit", procesarFormulario);
  }

  configurarNavegacion();
  configurarEventosModales();
}

// Función para habilitar/deshabilitar búsqueda DNI
function habilitarBusquedaDNI(habilitar) {
  const campoDNI = document.getElementById("dni");
  const botonBuscar = document.getElementById("btnBuscarDNI");
  const camposPersonales = ["nombres", "apellidoPat", "apellidoMat"];

  if (habilitar) {
    campoDNI.removeAttribute("readonly");
    campoDNI.placeholder = "12345678";
    if (botonBuscar) botonBuscar.style.display = "flex";

    camposPersonales.forEach((id) => {
      const elemento = document.getElementById(id);
      if (elemento) {
        elemento.removeAttribute("readonly");
        elemento.style.backgroundColor = "";
      }
    });

    configurarEventosBusquedaDNI();
  } else {
    campoDNI.setAttribute("readonly", true);
    campoDNI.placeholder = "DNI no modificable";
    campoDNI.style.backgroundColor = "#f8f9fa";
    if (botonBuscar) botonBuscar.style.display = "none";

    camposPersonales.forEach((id) => {
      const elemento = document.getElementById(id);
      if (elemento) {
        elemento.removeAttribute("readonly");
        elemento.style.backgroundColor = "";
      }
    });

    removerEventosBusquedaDNI();
  }
}

// Función para configurar eventos de búsqueda DNI
function configurarEventosBusquedaDNI() {
  const campoDNI = document.getElementById("dni");
  if (!campoDNI) return;

  let timeoutId;

  campoDNI.addEventListener("input", function busquedaAutomatica() {
    if (window.modoEditar) return;

    const dni = this.value.trim();
    clearTimeout(timeoutId);

    this.classList.remove("dni-valid", "dni-loading", "error");
    ocultarIndicadoresDNI();

    const errorAnterior = this.parentNode.querySelector(".error-message");
    if (errorAnterior) {
      errorAnterior.remove();
    }

    if (dni.length === 8 && /^\d{8}$/.test(dni)) {
      timeoutId = setTimeout(() => {
        buscarDNI(dni);
      }, 700);
    }
  });

  const botonBuscar = document.getElementById("btnBuscarDNI");
  if (botonBuscar) {
    botonBuscar.addEventListener("click", function () {
      const dni = campoDNI.value.trim();
      if (dni.length === 8 && /^\d{8}$/.test(dni)) {
        buscarDNI(dni);
      } else {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "warning",
          title: "Ingrese un DNI válido de 8 dígitos",
          showConfirmButton: false,
          timer: 3000,
        });
      }
    });
  }
}

// Función para remover eventos de búsqueda
function removerEventosBusquedaDNI() {
  const campoDNI = document.getElementById("dni");
  if (campoDNI) {
    const nuevoCampo = campoDNI.cloneNode(true);
    campoDNI.parentNode.replaceChild(nuevoCampo, campoDNI);
  }
}

// Función para buscar datos del DNI
function buscarDNI(dni) {
  if (window.modoEditar) return;

  const campoDNI = document.getElementById("dni");
  const botonBuscar = document.getElementById("btnBuscarDNI");

  campoDNI.classList.add("dni-loading");
  if (botonBuscar) {
    botonBuscar.classList.add("loading");
    botonBuscar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  }

  fetch("../../backend/php/api-reniec.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ numdni: dni }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        llenarDatosPersona(data);
        campoDNI.classList.remove("dni-loading");
        campoDNI.classList.add("dni-valid");

        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "Datos cargados automáticamente",
          showConfirmButton: false,
          timer: 2500,
          timerProgressBar: true,
        });
      } else {
        // DNI NO ENCONTRADO - silencioso
        campoDNI.classList.remove("dni-loading");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      campoDNI.classList.remove("dni-loading");
    })
    .finally(() => {
      if (botonBuscar) {
        botonBuscar.classList.remove("loading");
        botonBuscar.innerHTML = '<i class="fas fa-search"></i>';
      }
    });
}

// Función para llenar los datos de la persona
function llenarDatosPersona(data) {
  const campos = {
    nombres: data.prenombres ? data.prenombres.toUpperCase() : "",
    apellidoPat: data.apPrimer ? data.apPrimer.toUpperCase() : "",
    apellidoMat: data.apSegundo ? data.apSegundo.toUpperCase() : "",
  };

  Object.keys(campos).forEach((id) => {
    const elemento = document.getElementById(id);
    if (elemento && campos[id]) {
      elemento.value = campos[id];
      elemento.classList.add("valido");

      elemento.style.transform = "scale(1.02)";
      elemento.style.transition = "transform 0.2s ease";
      setTimeout(() => {
        elemento.style.transform = "scale(1)";
      }, 200);
    }
  });
}

// Función para ocultar indicadores de DNI
function ocultarIndicadoresDNI() {
  const campoDNI = document.getElementById("dni");
  if (campoDNI) {
    campoDNI.classList.remove("dni-loading", "dni-valid");
  }
}

// Función para llenar el formulario con datos del usuario
function llenarFormulario(usuario) {
  // Campos normales
  const campos = {
    userId: usuario.IdUsuarios,
    usuario: usuario.Usuario,
    dni: usuario.Dni || "",
    nombres: usuario.Nombres || "",
    apellidoPat: usuario.ApellidoPat || "",
    apellidoMat: usuario.ApellidoMat || "",
  };

  Object.keys(campos).forEach((id) => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.value = campos[id];
    }
  });

  // Selectize fields
  setTimeout(() => {
    if (
      document.getElementById("area") &&
      document.getElementById("area").selectize
    ) {
      document.getElementById("area").selectize.setValue(usuario.IdAreas);
    }
    if (
      document.getElementById("rol") &&
      document.getElementById("rol").selectize
    ) {
      document.getElementById("rol").selectize.setValue(usuario.IdRol);
    }
  }, 100);
}

// Función para mostrar el modal
function mostrarModal() {
  const modal = document.getElementById("modalUsuario");

  modal.style.display = "flex";
  document.body.style.overflow = "hidden";
  document.body.classList.add("modal-open");
  window.modalVisible = true;
  modal.style.zIndex = "999998";

  setTimeout(() => {
    modal.classList.add("show");
  }, 10);

  document.addEventListener("keydown", function escapeHandler(e) {
    if (e.key === "Escape" && !document.querySelector(".swal2-container")) {
      window.cerrarModal();
      document.removeEventListener("keydown", escapeHandler);
    }
  });
}

// Función para limpiar el formulario
function limpiarFormulario() {
  const formulario = document.getElementById("formUsuario");
  if (formulario) {
    formulario.reset();

    formulario.querySelectorAll("input, select").forEach((campo) => {
      campo.classList.remove("error", "valido", "dni-loading", "dni-valid");
      campo.removeAttribute("readonly");
      campo.style.backgroundColor = "";
    });

    formulario.querySelectorAll(".error-message").forEach((mensaje) => {
      mensaje.remove();
    });

    // Limpiar Selectize
    ["area", "rol"].forEach((id) => {
      const element = document.getElementById(id);
      if (element && element.selectize) {
        element.selectize.clear();
      }
    });
  }

  ocultarIndicadoresDNI();
}

// Función para procesar el formulario
function procesarFormulario(e) {
  e.preventDefault();

  if (!validarFormulario()) {
    return;
  }

  const botonGuardar = document.querySelector(".btn-guardar");
  const textoOriginal = botonGuardar.innerHTML;

  botonGuardar.disabled = true;
  botonGuardar.innerHTML =
    '<i class="fas fa-spinner fa-spin"></i> Guardando...';

  const datos = new FormData(e.target);
  datos.append("accion", window.modoEditar ? "editar" : "crear");

  // Asegurar que el estado sea siempre 1 (Activo) para nuevos usuarios
  if (!window.modoEditar) {
    datos.set("estado", "1");
  }

  fetch("../../backend/php/gestusuarios/gestusuarios.php", {
    method: "POST",
    body: datos,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: data.message,
          showConfirmButton: false,
          timer: 1000,
          timerProgressBar: true,
        });

        setTimeout(() => {
          window.cerrarModal();
          location.reload();
        }, 1000);
      } else {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: data.message,
          showConfirmButton: false,
          timer: 3000,
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        toast: true,
        position: "top-end",
        icon: "error",
        title: "No se pudo conectar con el servidor",
        showConfirmButton: false,
        timer: 3000,
      });
    })
    .finally(() => {
      botonGuardar.disabled = false;
      botonGuardar.innerHTML = textoOriginal;
    });
}

// Función para validar el formulario
function validarFormulario() {
  const campos = document.querySelectorAll(
    "#formUsuario input[required], #formUsuario select[required]"
  );
  let valido = true;

  campos.forEach((campo) => {
    // Para selectize, verificar el valor del selectize
    if (campo.selectize) {
      if (!campo.selectize.getValue()) {
        mostrarError(campo, "Este campo es obligatorio");
        valido = false;
      } else {
        mostrarExito(campo);
      }
    } else if (!campo.value.trim()) {
      mostrarError(campo, "Este campo es obligatorio");
      valido = false;
    } else {
      validarCampoEspecifico(campo);
    }
  });

  return valido;
}

// Función para validar campos específicos
function validarCampoEspecifico(campo) {
  const valor = campo.value.trim();
  let mensaje = "";

  switch (campo.name) {
    case "usuario":
      if (valor.length < 3) {
        mensaje = "El usuario debe tener al menos 3 caracteres";
      }
      break;
    case "dni":
      if (window.modoEditar && !/^\d{8}$/.test(valor)) {
        mensaje = "El DNI debe tener exactamente 8 dígitos";
      }
      break;
    case "password":
      if (campo.required && valor.length < 4) {
        mensaje = "La contraseña debe tener al menos 4 caracteres";
      }
      break;
    case "nombres":
    case "apellidoPat":
      if (valor.length < 2) {
        mensaje = "Este campo debe tener al menos 2 caracteres";
      }
      break;
  }

  if (mensaje) {
    mostrarError(campo, mensaje);
    return false;
  } else {
    mostrarExito(campo);
    return true;
  }
}

// Función para mostrar error en un campo
function mostrarError(campo, mensaje) {
  campo.classList.remove("valido");
  campo.classList.add("error");

  const errorAnterior = campo.parentNode.querySelector(".error-message");
  if (errorAnterior) {
    errorAnterior.remove();
  }

  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.textContent = mensaje;
  campo.parentNode.appendChild(errorDiv);

  setTimeout(() => {
    if (errorDiv.parentNode) {
      errorDiv.remove();
    }
  }, 5000);
}

// Función para mostrar éxito en un campo
function mostrarExito(campo) {
  campo.classList.remove("error");
  campo.classList.add("valido");

  const errorAnterior = campo.parentNode.querySelector(".error-message");
  if (errorAnterior) {
    errorAnterior.remove();
  }
}

// Función para configurar eventos de modales
function configurarEventosModales() {
  document.addEventListener("click", function (event) {
    const modal = document.getElementById("modalUsuario");
    if (event.target === modal && !document.querySelector(".swal2-container")) {
      window.cerrarModal();
    }
  });
}

// Función para configurar la navegación
function configurarNavegacion() {
  window.toggleMobileNav = function () {
    const nav = document.querySelector(".navbar-nav");
    if (nav) {
      nav.classList.toggle("active");
    }
  };

  document
    .querySelectorAll(".nav-dropdown .dropdown-toggle")
    .forEach((toggle) => {
      toggle.addEventListener("click", function (e) {
        e.preventDefault();

        document.querySelectorAll(".nav-dropdown").forEach((dropdown) => {
          if (dropdown !== this.parentNode) {
            dropdown.classList.remove("active");
          }
        });

        this.parentNode.classList.toggle("active");
      });
    });

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".nav-dropdown")) {
      document.querySelectorAll(".nav-dropdown").forEach((dropdown) => {
        dropdown.classList.remove("active");
      });
    }
  });

  document
    .querySelectorAll("#formUsuario input, #formUsuario select")
    .forEach((campo) => {
      campo.addEventListener("blur", function () {
        if (this.value.trim() && this.name !== "dni") {
          validarCampoEspecifico(this);
        }
      });

      campo.addEventListener("input", function () {
        if (this.classList.contains("error")) {
          this.classList.remove("error");
          const errorMsg = this.parentNode.querySelector(".error-message");
          if (errorMsg) {
            errorMsg.remove();
          }
        }

        // Convertir a mayúsculas automáticamente los campos de nombres
        if (
          this.name === "nombres" ||
          this.name === "apellidoPat" ||
          this.name === "apellidoMat"
        ) {
          const cursorPos = this.selectionStart;
          this.value = this.value.toUpperCase();
          this.setSelectionRange(cursorPos, cursorPos);
        }
      });
    });
}

// LOG DE VERIFICACIÓN AL FINAL
console.log("Archivo gestusuarios.js cargado completamente");
console.log("Funciones globales definidas:", {
  abrirModal: typeof window.abrirModal,
  editarUsuario: typeof window.editarUsuario,
  cambiarEstado: typeof window.cambiarEstado,
  cambiarTab: typeof window.cambiarTab,
  cerrarModal: typeof window.cerrarModal,
});
