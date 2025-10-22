/**
 * JavaScript para Módulo de Supervisión - DIGI
 */

// Variables globales del módulo
let filtroActivo = "todos";
let datosOriginales = [];
let datosFiltrados = [];
let paginaActual = 1;
const registrosPorPagina = 10;

// Configuración de observaciones
const HABILITAR_OBSERVACIONES = true;

// Inicialización del módulo
document.addEventListener("DOMContentLoaded", function () {
  console.log("Módulo de Supervisión iniciado");

  inicializarSupervision();
  configurarEventos();
  cargarDatos();
  configurarObservaciones();
  configurarBusquedaAvanzada();
  inicializarPaginacion();
});

/**
 * Inicializar el módulo de supervisión
 */
function inicializarSupervision() {
  const filas = document.querySelectorAll(".fila-documento");

  // Solo incluir filas que realmente existen en el DOM (excluyendo mensajes vacíos)
  datosOriginales = Array.from(filas)
    .filter(
      (fila) =>
        !fila.classList.contains("mensaje-resultados") && fila.dataset.id
    )
    .map((fila) => ({
      elemento: fila,
      semaforo: fila.dataset.semaforo,
      contenido: fila.textContent.toLowerCase(),
    }));

  datosFiltrados = [...datosOriginales];
  aplicarAnimacionesEntrada();
  console.log(
    `${datosOriginales.length} documentos válidos cargados de la tabla`
  );
}

/**
 * Inicializar sistema de paginación
 */
function inicializarPaginacion() {
  const tablaContainer = document.querySelector(".tabla-container");

  // Crear controles de paginación
  const paginacionHTML = `
        <div class="paginacion-container" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8f9fa; border-top: 1px solid #dee2e6;">
            <div class="paginacion-info" style="color: #6c757d; font-size: 0.9rem;">
                Mostrando <span id="registrosDesde">1</span> a <span id="registrosHasta">10</span> de <span id="totalRegistros">${datosOriginales.length}</span> registros
            </div>
            <div class="paginacion-controles" style="display: flex; gap: 0.5rem; align-items: center;">
                <button id="btnAnterior" class="btn-paginacion" style="padding: 0.5rem 1rem; border: 1px solid #dee2e6; background: white; border-radius: 4px; cursor: pointer;" disabled>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <div id="numerosPagina" style="display: flex; gap: 0.25rem;">
                    <!-- Números de página se generan dinámicamente -->
                </div>
                <button id="btnSiguiente" class="btn-paginacion" style="padding: 0.5rem 1rem; border: 1px solid #dee2e6; background: white; border-radius: 4px; cursor: pointer;">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    `;

  tablaContainer.insertAdjacentHTML("afterend", paginacionHTML);

  // Configurar eventos de paginación
  document
    .getElementById("btnAnterior")
    .addEventListener("click", () => cambiarPagina(paginaActual - 1));
  document
    .getElementById("btnSiguiente")
    .addEventListener("click", () => cambiarPagina(paginaActual + 1));

  // Aplicar paginación inicial
  aplicarPaginacion();
}

/**
 * Aplicar paginación a los datos filtrados
 */
function aplicarPaginacion() {
  const totalPaginas = Math.ceil(datosFiltrados.length / registrosPorPagina);
  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;

  // Ocultar todas las filas
  datosOriginales.forEach((item) => {
    item.elemento.style.display = "none";
  });

  // Mostrar solo las filas de la página actual
  const filasPaginaActual = datosFiltrados.slice(inicio, fin);
  filasPaginaActual.forEach((item) => {
    item.elemento.style.display = "";
  });

  // Renumerar filas visibles
  filasPaginaActual.forEach((item, index) => {
    const numeroFila = item.elemento.querySelector(".numero-fila");
    if (numeroFila) {
      numeroFila.textContent = inicio + index + 1;
    }
  });

  // Actualizar controles de paginación
  actualizarControlesPaginacion(totalPaginas);

  // Actualizar información de registros
  const registrosDesde = datosFiltrados.length > 0 ? inicio + 1 : 0;
  const registrosHasta = Math.min(fin, datosFiltrados.length);

  document.getElementById("registrosDesde").textContent = registrosDesde;
  document.getElementById("registrosHasta").textContent = registrosHasta;
  document.getElementById("totalRegistros").textContent = datosFiltrados.length;
}

/**
 * Actualizar controles de paginación
 */
function actualizarControlesPaginacion(totalPaginas) {
  const btnAnterior = document.getElementById("btnAnterior");
  const btnSiguiente = document.getElementById("btnSiguiente");
  const numerosPagina = document.getElementById("numerosPagina");

  // Habilitar/deshabilitar botones
  btnAnterior.disabled = paginaActual <= 1;
  btnSiguiente.disabled = paginaActual >= totalPaginas;

  // Estilo para botones deshabilitados
  btnAnterior.style.opacity = btnAnterior.disabled ? "0.5" : "1";
  btnAnterior.style.cursor = btnAnterior.disabled ? "not-allowed" : "pointer";
  btnSiguiente.style.opacity = btnSiguiente.disabled ? "0.5" : "1";
  btnSiguiente.style.cursor = btnSiguiente.disabled ? "not-allowed" : "pointer";

  // Generar números de página
  numerosPagina.innerHTML = "";
  const maxPaginasVisibles = 5;
  let inicioRango = Math.max(
    1,
    paginaActual - Math.floor(maxPaginasVisibles / 2)
  );
  let finRango = Math.min(totalPaginas, inicioRango + maxPaginasVisibles - 1);

  if (finRango - inicioRango < maxPaginasVisibles - 1) {
    inicioRango = Math.max(1, finRango - maxPaginasVisibles + 1);
  }

  for (let i = inicioRango; i <= finRango; i++) {
    const btnPagina = document.createElement("button");
    btnPagina.textContent = i;
    btnPagina.className = "btn-numero-pagina";
    btnPagina.style.cssText = `
            padding: 0.5rem 0.75rem;
            border: 1px solid #dee2e6;
            background: ${i === paginaActual ? "#6c5ce7" : "white"};
            color: ${i === paginaActual ? "white" : "#495057"};
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        `;

    btnPagina.addEventListener("click", () => cambiarPagina(i));
    numerosPagina.appendChild(btnPagina);
  }
}

/**
 * Cambiar página
 */
function cambiarPagina(nuevaPagina) {
  const totalPaginas = Math.ceil(datosFiltrados.length / registrosPorPagina);

  if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas) {
    paginaActual = nuevaPagina;
    aplicarPaginacion();

    // Scroll suave hacia la tabla
    document.querySelector(".tabla-supervision").scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }
}

/**
 * Configurar eventos del módulo
 */
function configurarEventos() {
  const campoBusqueda = document.getElementById("buscarDocumento");
  if (campoBusqueda) {
    campoBusqueda.addEventListener("input", debounce(buscarEnTabla, 300));
    campoBusqueda.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        this.value = "";
        buscarEnTabla();
      }
    });
  }

  // Eventos de botones de filtro
  document.querySelectorAll(".btn-filtro").forEach((btn) => {
    btn.addEventListener("click", function () {
      const filtro = this.dataset.filtro;
      filtrarPor(filtro);
    });
  });

  configurarEstadisticasInteractivas();
  configurarTooltips();

  console.log("Eventos configurados correctamente");
}

/**
 * Cargar datos adicionales
 */
function cargarDatos() {
  actualizarContadores();
  console.log("Módulo de supervisión listo");
}

/**
 * Configurar observaciones según configuración
 */
function configurarObservaciones() {
  const botonesObservacion = document.querySelectorAll(".btn-observacion");

  if (!HABILITAR_OBSERVACIONES) {
    botonesObservacion.forEach((btn) => {
      btn.style.display = "none";
    });

    const headers = document.querySelectorAll(".tabla-supervision th");
    headers.forEach((header) => {
      if (header.textContent.includes("OBSERVACIÓN")) {
        header.style.display = "none";
      }
    });

    console.log("Modo de solo visualización activado");
  } else {
    console.log("Modo completo activado - Observaciones habilitadas");
  }
}

/**
 * Configurar búsqueda avanzada con contador
 */
function configurarBusquedaAvanzada() {
  const contador = document.getElementById("contadorResultados");

  function actualizarContador() {
    const cantidad = datosFiltrados.length;
    if (contador) {
      contador.textContent = `${cantidad} documento${
        cantidad !== 1 ? "s" : ""
      }`;
    }
  }

  // Actualizar contador inicial
  actualizarContador();
}

/**
 * Filtrar documentos por tipo de semáforo
 */
function filtrarPor(semaforo) {
  filtroActivo = semaforo;
  console.log(`Filtrando por: ${semaforo}`);

  // Actualizar estado de botones
  document.querySelectorAll(".stat-card").forEach((card) => {
    card.classList.remove("active");
  });
  document
    .querySelector(`[data-filtro="${semaforo}"]`)
    ?.classList.add("active");

  aplicarFiltros();
  animarCambioFiltro();
}

/**
 * Buscar en la tabla
 */
function buscarEnTabla() {
  const busqueda = document
    .getElementById("buscarDocumento")
    .value.toLowerCase()
    .trim();
  console.log(`Buscando: "${busqueda}"`);

  aplicarFiltros(busqueda);
}

/**
 * Aplicar filtros combinados con paginación
 */
function aplicarFiltros(textoBusqueda = "") {
  datosFiltrados = datosOriginales.filter((item) => {
    const coincideSemaforo =
      filtroActivo === "todos" || item.semaforo === filtroActivo;
    const coincideBusqueda =
      textoBusqueda === "" || item.contenido.includes(textoBusqueda);

    return coincideSemaforo && coincideBusqueda;
  });

  // Resetear a la primera página
  paginaActual = 1;

  // Aplicar paginación con los nuevos datos filtrados
  aplicarPaginacion();

  // Mostrar mensaje si no hay resultados
  mostrarMensajeVacio(datosFiltrados.length === 0);

  // Actualizar contador de resultados
  const contador = document.getElementById("contadorResultados");
  if (contador) {
    contador.textContent = `${datosFiltrados.length} documento${
      datosFiltrados.length !== 1 ? "s" : ""
    }`;
  }
}

/**
 * Mostrar mensaje cuando no hay resultados
 */
function mostrarMensajeVacio(mostrar) {
  let mensajeVacio = document.querySelector(".mensaje-resultados");
  const colspan = HABILITAR_OBSERVACIONES ? "9" : "8";

  if (mostrar && !mensajeVacio) {
    const tbody = document.querySelector(".tabla-supervision tbody");
    const tr = document.createElement("tr");
    tr.className = "mensaje-resultados";
    tr.innerHTML = `
            <td colspan="${colspan}" class="no-datos">
                <div class="mensaje-vacio">
                    <i class="fas fa-search"></i>
                    <p>No se encontraron documentos que coincidan con los criterios de búsqueda</p>
                    <button onclick="limpiarFiltros()" class="btn-primary">
                        <i class="fas fa-eraser"></i> Limpiar filtros
                    </button>
                </div>
            </td>
        `;
    tbody.appendChild(tr);

    // Ocultar paginación cuando no hay resultados
    document.querySelector(".paginacion-container").style.display = "none";
  } else if (!mostrar && mensajeVacio) {
    mensajeVacio.remove();

    // Mostrar paginación cuando hay resultados
    document.querySelector(".paginacion-container").style.display = "flex";
  }
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
  const campoBusqueda = document.getElementById("buscarDocumento");
  if (campoBusqueda) {
    campoBusqueda.value = "";
  }

  filtrarPor("todos");
  console.log("Filtros limpiados");
}

/**
 * Ver observación completa en modal
 */
function verObservacionCompleta(observacion, fecha) {
  Swal.fire({
    title: "Observación del Documento",
    html: `
            <div style="text-align: left;">
                <p style="margin-bottom: 1rem; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-calendar"></i> Fecha: ${fecha}
                </p>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #6c5ce7;">
                    <p style="margin: 0; line-height: 1.5;">${observacion}</p>
                </div>
            </div>
        `,
    confirmButtonText: "Cerrar",
    confirmButtonColor: "#6c5ce7",
    width: "500px",
  });
}

/**
 * Agregar observación a documento
 */
function agregarObservacion(id) {
  if (!HABILITAR_OBSERVACIONES) {
    Swal.fire({
      icon: "info",
      title: "Función no disponible",
      text: "Las observaciones están deshabilitadas en modo de solo visualización",
      confirmButtonColor: "#6c5ce7",
    });
    return;
  }

  console.log(`Agregando observación al documento ID: ${id}`);

  Swal.fire({
    title: "Agregar Observación",
    html: `
            <div style="text-align: left;">
                <p style="margin-bottom: 1rem; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> 
                    Esta observación se guardará en la tabla movimientodocumento
                </p>
                <label for="observacion" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Observación:</label>
                <textarea id="observacion" class="swal2-textarea" placeholder="Escriba su observación sobre este documento..." style="min-height: 120px; resize: vertical; width: 100%; border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; font-family: inherit;"></textarea>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-save"></i> Guardar Observación',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    confirmButtonColor: "#6c5ce7",
    cancelButtonColor: "#6c757d",
    width: "500px",
    customClass: {
      popup: "swal-observacion-popup",
      confirmButton: "swal-btn-confirm",
      cancelButton: "swal-btn-cancel",
    },
    preConfirm: () => {
      const observacion = document.getElementById("observacion").value.trim();

      if (!observacion) {
        Swal.showValidationMessage("La observación es obligatoria");
        return false;
      }

      if (observacion.length < 10) {
        Swal.showValidationMessage(
          "La observación debe tener al menos 10 caracteres"
        );
        return false;
      }

      return { observacion };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      guardarObservacion(id, result.value);
    }
  });
}

/**
 * Guardar observación en base de datos
 */
function guardarObservacion(id, datos) {
  Swal.fire({
    title: "Guardando observación...",
    html: "Actualizando en la tabla movimientodocumento",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // Simulación de guardado
  setTimeout(() => {
    console.log("Observación guardada en movimientodocumento:", {
      id,
      ...datos,
    });

    Swal.fire({
      icon: "success",
      title: "Observación guardada",
      html: `La observación ha sido registrada en la tabla movimientodocumento para el documento ID: <strong>${id}</strong>`,
      timer: 3000,
      showConfirmButton: false,
      toast: true,
      position: "top-end",
    });

    marcarDocumentoConObservacion(id);
  }, 1200);
}

/**
 * Marcar documento con observación agregada
 */
function marcarDocumentoConObservacion(id) {
  const fila = document.querySelector(`tr[data-id="${id}"]`);
  if (fila && HABILITAR_OBSERVACIONES) {
    const btnObservacion = fila.querySelector(".btn-observacion");
    if (btnObservacion) {
      btnObservacion.style.background = "#00b894";
      btnObservacion.innerHTML = '<i class="fas fa-check"></i>';
      btnObservacion.title = "Observación agregada correctamente";

      btnObservacion.style.animation = "pulse 0.6s ease-in-out";
      setTimeout(() => {
        btnObservacion.style.animation = "";
      }, 600);
    }
  }
}

/**
 * Ver observaciones existentes
 */
function verObservaciones(id) {
  console.log(`Viendo observaciones del documento ID: ${id}`);

  Swal.fire({
    title: "Observaciones del Documento",
    html: `
            <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                <p style="margin-bottom: 1rem; color: #666;">
                    <i class="fas fa-file-alt"></i> Documento ID: ${id}
                </p>
                <div class="observaciones-lista">
                    <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">No hay observaciones registradas</p>
                    </div>
                </div>
            </div>
        `,
    confirmButtonText: "Cerrar",
    confirmButtonColor: "#6c5ce7",
    width: "500px",
  });
}

/**
 * Configurar estadísticas como botones interactivos
 */
function configurarEstadisticasInteractivas() {
  const tarjetasStats = document.querySelectorAll(".stat-card");

  tarjetasStats.forEach((tarjeta, index) => {
    tarjeta.style.cursor = "pointer";
    tarjeta.addEventListener("click", function () {
      const filtros = ["todos", "verde", "amarillo", "rojo"];
      if (filtros[index]) {
        filtrarPor(filtros[index]);

        this.style.transform = "scale(0.95)";
        setTimeout(() => {
          this.style.transform = "";
        }, 150);
      }
    });

    tarjeta.title = "Haz clic para filtrar por esta categoría";

    tarjeta.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-2px)";
    });

    tarjeta.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
    });
  });
}

/**
 * Configurar tooltips informativos
 */
function configurarTooltips() {
  document.querySelectorAll(".semaforo").forEach((semaforo) => {
    const color = semaforo.classList.contains("verde")
      ? "En tiempo (1-3 días)"
      : semaforo.classList.contains("amarillo")
      ? "Requiere atención (4-6 días)"
      : "Urgente (7+ días)";
    semaforo.title = color;
  });

  document.querySelectorAll(".estado-badge").forEach((badge) => {
    const texto = badge.textContent.trim();
    badge.title = `Estado del documento: ${texto}`;
  });

  if (HABILITAR_OBSERVACIONES) {
    document.querySelectorAll(".btn-observacion").forEach((btn) => {
      btn.title = "Agregar observación a este documento";
    });
  }
}

/**
 * Aplicar animaciones de entrada
 */
function aplicarAnimacionesEntrada() {
  document.querySelectorAll(".stat-card").forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";

    setTimeout(() => {
      card.style.transition = "all 0.5s ease";
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 100);
  });

  document.querySelectorAll(".fila-documento").forEach((fila, index) => {
    fila.style.opacity = "0";
    fila.style.transform = "translateX(-20px)";

    setTimeout(() => {
      fila.style.transition = "all 0.3s ease";
      fila.style.opacity = "1";
      fila.style.transform = "translateX(0)";
    }, 500 + index * 50);
  });
}

/**
 * Animación para cambio de filtros
 */
function animarCambioFiltro() {
  const tabla = document.querySelector(".tabla-supervision tbody");
  if (tabla) {
    tabla.style.opacity = "0.7";

    setTimeout(() => {
      tabla.style.transition = "opacity 0.3s ease";
      tabla.style.opacity = "1";
    }, 100);
  }
}

/**
 * Actualizar contadores estadísticos
 */
function actualizarContadores() {
  const contadores = {
    total: datosOriginales.length,
    verde: datosOriginales.filter((item) => item.semaforo === "verde").length,
    amarillo: datosOriginales.filter((item) => item.semaforo === "amarillo")
      .length,
    rojo: datosOriginales.filter((item) => item.semaforo === "rojo").length,
  };

  console.log("Contadores actualizados:", contadores);

  if (contadores.total > 0) {
    const porcentajeRojo = Math.round(
      (contadores.rojo / contadores.total) * 100
    );

    if (porcentajeRojo > 30) {
      console.warn(
        `¡Atención! ${porcentajeRojo}% de documentos están en estado urgente`
      );
    }
  }
}

/**
 * Función debounce para optimizar búsquedas
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Actualizar supervisión completa
 */
function actualizarSupervision() {
  console.log("Actualizando datos de supervisión");

  Swal.fire({
    title: "Actualizando datos...",
    html: "Obteniendo la información más reciente del servidor",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  setTimeout(() => {
    Swal.fire({
      icon: "success",
      title: "Datos actualizados",
      text: "La información se ha actualizado correctamente",
      timer: 2000,
      showConfirmButton: false,
    });

    setTimeout(() => {
      location.reload();
    }, 2000);
  }, 1500);
}

// Funciones globales para uso desde HTML
window.filtrarPor = filtrarPor;
window.buscarEnTabla = buscarEnTabla;
window.limpiarFiltros = limpiarFiltros;
window.agregarObservacion = agregarObservacion;
window.verObservaciones = verObservaciones;
window.actualizarSupervision = actualizarSupervision;
window.verObservacionCompleta = verObservacionCompleta;
window.cambiarPagina = cambiarPagina;
// exportarSupervision está en exportar-supervision.js

// Estilos CSS integrados
const style = document.createElement("style");
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .swal-observacion-popup {
        border-radius: 12px !important;
    }
    
    .swal-btn-confirm {
        border-radius: 8px !important;
        font-weight: 600 !important;
    }
    
    .swal-btn-cancel {
        border-radius: 8px !important;
        font-weight: 600 !important;
    }
    
    .swal2-container {
        z-index: 999999 !important;
    }
    
    .swal2-popup {
        z-index: 999999 !important;
    }
    
    .swal2-backdrop-show {
        z-index: 999998 !important;
    }
    
    /* Estilos para paginación */
    .btn-paginacion:hover {
        background: #f8f9fa !important;
        border-color: #6c5ce7 !important;
    }
    
    .btn-numero-pagina:hover {
        background: #e9ecef !important;
        border-color: #6c5ce7 !important;
    }
    
    .stat-card.active {
        box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3) !important;
        border: 2px solid #6c5ce7 !important;
    }
`;
document.head.appendChild(style);

window.desbloquearDocumento = function (idDoc) {
  Swal.fire({
    title: "¿Desbloquear documento?",
    text: "El estado pasará a SEGUIMIENTO.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí, desbloquear",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#6c5ce7",
    cancelButtonColor: "#6c757d",
  }).then((r) => {
    if (!r.isConfirmed) return;

    const fd = new FormData();
    fd.append("id", idDoc);

    fetch("../../backend/php/desbloquear_documento.php", {
      method: "POST",
      body: fd,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "OK",
            text: data.message,
            timer: 1800,
            showConfirmButton: false,
          }).then(() => location.reload());
        } else {
          Swal.fire("Error", data.message || "No se pudo desbloquear", "error");
        }
      })
      .catch(() =>
        Swal.fire("Error", "Falla de conexión con el servidor", "error")
      );
  });
};

/* console.log(
  `Módulo de supervisión cargado - Observaciones: ${
    HABILITAR_OBSERVACIONES ? "HABILITADAS" : "DESHABILITADAS"
  } - Paginación: 10 registros por página`
);
 */
