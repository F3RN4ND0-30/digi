/**
 * JavaScript para Módulo de Supervisión - DIGI
 * supervision.js - CON OBSERVACIONES OPCIONALES
 */

// Variables globales del módulo
let filtroActivo = "todos";
let datosOriginales = [];

// CONFIGURACIÓN: Cambiar a false para deshabilitar observaciones
const HABILITAR_OBSERVACIONES = true; // Cambiar a false para solo visualización

// Inicialización del módulo
document.addEventListener("DOMContentLoaded", function () {
  console.log("Módulo de Supervisión iniciado");

  inicializarSupervision();
  configurarEventos();
  cargarDatos();

  // Configurar observaciones según la configuración
  configurarObservaciones();
});

/**
 * Configurar observaciones según la configuración
 */
function configurarObservaciones() {
  const botonesObservacion = document.querySelectorAll(".btn-observacion");

  if (!HABILITAR_OBSERVACIONES) {
    // Si las observaciones están deshabilitadas, ocultar los botones
    botonesObservacion.forEach((btn) => {
      btn.style.display = "none";
    });

    // Ajustar el colspan de la tabla si es necesario
    const headers = document.querySelectorAll(".tabla-supervision th");
    headers.forEach((header) => {
      if (header.textContent.includes("OBSERVACIÓN")) {
        header.style.display = "none";
      }
    });

    const cells = document.querySelectorAll(".tabla-supervision td");
    cells.forEach((cell) => {
      if (cell.querySelector(".btn-observacion")) {
        cell.style.display = "none";
      }
    });

    console.log(
      "Modo de solo visualización activado - Observaciones deshabilitadas"
    );
  } else {
    console.log("Modo completo activado - Observaciones habilitadas");
  }
}

/**
 * Inicializar el módulo de supervisión
 */
function inicializarSupervision() {
  // Guardar datos originales de la tabla
  const filas = document.querySelectorAll(".fila-documento");
  datosOriginales = Array.from(filas).map((fila) => ({
    elemento: fila,
    semaforo: fila.dataset.semaforo,
    contenido: fila.textContent.toLowerCase(),
  }));

  // Aplicar animaciones de entrada
  aplicarAnimacionesEntrada();

  console.log(`${datosOriginales.length} documentos cargados`);
}

/**
 * Configurar todos los eventos del módulo
 */
function configurarEventos() {
  // Evento de búsqueda
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

  // Eventos de estadísticas (clicks para filtrar rápido)
  configurarEstadisticasInteractivas();

  // Configurar tooltips
  configurarTooltips();

  console.log("Eventos configurados correctamente");
}

/**
 * Cargar datos adicionales si es necesario
 */
function cargarDatos() {
  // Actualizar contadores en tiempo real
  actualizarContadores();

  // Mostrar mensaje de conexión (sin verificar servidor)
  console.log(
    "Módulo de supervisión listo - Modo sin verificación de servidor"
  );
}

/**
 * Filtrar documentos por tipo de semáforo
 */
function filtrarPor(semaforo) {
  filtroActivo = semaforo;
  console.log(`Filtrando por: ${semaforo}`);

  // Actualizar estado de botones
  document.querySelectorAll(".btn-filtro").forEach((btn) => {
    btn.classList.remove("active");
  });
  document.querySelector(`[data-filtro="${semaforo}"]`).classList.add("active");

  // Aplicar filtro
  aplicarFiltros();

  // Animación suave
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
 * Aplicar filtros combinados (semáforo + búsqueda)
 */
function aplicarFiltros(textoBusqueda = "") {
  let contadorVisible = 0;

  datosOriginales.forEach((item) => {
    const coincideSemaforo =
      filtroActivo === "todos" || item.semaforo === filtroActivo;
    const coincideBusqueda =
      textoBusqueda === "" || item.contenido.includes(textoBusqueda);

    const visible = coincideSemaforo && coincideBusqueda;

    if (visible) {
      item.elemento.style.display = "";
      contadorVisible++;
    } else {
      item.elemento.style.display = "none";
    }
  });

  // Mostrar mensaje si no hay resultados
  mostrarMensajeVacio(contadorVisible === 0);

  // Actualizar contador de resultados
  actualizarContadorResultados(contadorVisible);
}

/**
 * Mostrar/ocultar mensaje de tabla vacía
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
  } else if (!mostrar && mensajeVacio) {
    mensajeVacio.remove();
  }
}

/**
 * Actualizar contador de resultados
 */
function actualizarContadorResultados(cantidad) {
  let contador = document.querySelector(".contador-resultados");

  if (!contador) {
    contador = document.createElement("div");
    contador.className = "contador-resultados";
    contador.style.cssText = `
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #6c5ce7;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 10;
        `;
    const tarjeta = document.querySelector(".tarjeta");
    if (tarjeta) {
      tarjeta.style.position = "relative";
      tarjeta.appendChild(contador);
    }
  }

  contador.textContent = `${cantidad} documento${cantidad !== 1 ? "s" : ""}`;
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
  // Limpiar búsqueda
  const campoBusqueda = document.getElementById("buscarDocumento");
  if (campoBusqueda) {
    campoBusqueda.value = "";
  }

  // Resetear filtro
  filtrarPor("todos");

  console.log("Filtros limpiados");
}

/**
 * Agregar observación a un documento - SOLO SI ESTÁ HABILITADO
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
 * Guardar observación en la base de datos (tabla movimientodocumento)
 */
function guardarObservacion(id, datos) {
  // Mostrar loading
  Swal.fire({
    title: "Guardando observación...",
    html: "Actualizando en la tabla movimientodocumento",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // Aquí deberías hacer la petición real al backend
  // fetch('../../backend/php/guardar-observacion.php', {
  //     method: 'POST',
  //     headers: {'Content-Type': 'application/json'},
  //     body: JSON.stringify({
  //         idDocumento: id,
  //         observacion: datos.observacion
  //     })
  // })

  // Simular petición al servidor
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

    // Actualizar indicador visual en la tabla
    marcarDocumentoConObservacion(id);
  }, 1200);
}

/**
 * Marcar documento con observación en la tabla
 */
function marcarDocumentoConObservacion(id) {
  const fila = document.querySelector(`tr[data-id="${id}"]`);
  if (fila && HABILITAR_OBSERVACIONES) {
    const btnObservacion = fila.querySelector(".btn-observacion");
    if (btnObservacion) {
      btnObservacion.style.background = "#00b894";
      btnObservacion.innerHTML = '<i class="fas fa-check"></i>';
      btnObservacion.title = "Observación agregada correctamente";

      // Añadir animación de éxito
      btnObservacion.style.animation = "pulse 0.6s ease-in-out";
      setTimeout(() => {
        btnObservacion.style.animation = "";
      }, 600);
    }
  }
}

/**
 * Ver observaciones existentes del documento
 */
function verObservaciones(id) {
  console.log(`Viendo observaciones del documento ID: ${id}`);

  // Aquí harías una consulta a la tabla movimientodocumento
  // para obtener las observaciones existentes

  Swal.fire({
    title: "Observaciones del Documento",
    html: `
            <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                <p style="margin-bottom: 1rem; color: #666;">
                    <i class="fas fa-file-alt"></i> Documento ID: ${id}
                </p>
                <div class="observaciones-lista">
                    <!-- Aquí cargarías las observaciones reales desde movimientodocumento -->
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

// Resto de funciones sin cambios...
function exportarSupervision() {
  console.log("Exportando reporte de supervisión");

  Swal.fire({
    title: "Exportar Reporte de Supervisión",
    html: `
            <div style="text-align: left; padding: 1rem;">
                <p style="margin-bottom: 1rem; color: #666;">Seleccione el formato de exportación:</p>
                <div style="margin: 1.5rem 0;">
                    <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;">
                        <input type="radio" name="formato" value="excel" checked style="margin-right: 1rem;"> 
                        <i class="fas fa-file-excel" style="color: #10B981; margin-right: 0.5rem; width: 20px;"></i>
                        Excel (.xlsx) - Recomendado
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;">
                        <input type="radio" name="formato" value="pdf" style="margin-right: 1rem;"> 
                        <i class="fas fa-file-pdf" style="color: #EF4444; margin-right: 0.5rem; width: 20px;"></i>
                        PDF - Para impresión
                    </label>
                    <label style="display: flex; align-items: center; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;">
                        <input type="radio" name="formato" value="csv" style="margin-right: 1rem;"> 
                        <i class="fas fa-file-csv" style="color: #3B82F6; margin-right: 0.5rem; width: 20px;"></i>
                        CSV - Datos básicos
                    </label>
                </div>
                <hr style="margin: 1rem 0; border: none; border-top: 1px solid #e9ecef;">
                <label style="display: flex; align-items: center; margin-top: 1rem; cursor: pointer;">
                    <input type="checkbox" id="incluirFiltros" checked style="margin-right: 0.75rem;">
                    <span>Incluir solo documentos filtrados actualmente</span>
                </label>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-download"></i> Generar Reporte',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    confirmButtonColor: "#6c5ce7",
    cancelButtonColor: "#6c757d",
    width: "450px",
    preConfirm: () => {
      const formato = document.querySelector(
        'input[name="formato"]:checked'
      ).value;
      const incluirFiltros = document.getElementById("incluirFiltros").checked;
      return { formato, incluirFiltros };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      procesarExportacion(result.value);
    }
  });
}

function procesarExportacion(opciones) {
  console.log("Procesando exportación:", opciones);

  let progreso = 0;
  const timer = setInterval(() => {
    progreso += 15;
    Swal.update({
      html: `
                <div style="text-align: center; padding: 1rem;">
                    <i class="fas fa-file-${
                      opciones.formato === "excel"
                        ? "excel"
                        : opciones.formato === "pdf"
                        ? "pdf"
                        : "csv"
                    }" style="font-size: 3rem; color: #6c5ce7; margin-bottom: 1rem;"></i>
                    <p style="margin-bottom: 1rem;">Generando reporte en formato <strong>${opciones.formato.toUpperCase()}</strong>...</p>
                    <div style="background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 1rem 0; height: 25px;">
                        <div style="background: linear-gradient(90deg, #6c5ce7, #74b9ff); height: 100%; width: ${progreso}%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; font-weight: 600;">
                            ${progreso}%
                        </div>
                    </div>
                    <p style="color: #666; font-size: 0.9rem;">Procesando documentos de supervisión...</p>
                </div>
            `,
    });

    if (progreso >= 100) {
      clearInterval(timer);

      setTimeout(() => {
        Swal.fire({
          icon: "success",
          title: "Reporte generado exitosamente",
          html: `El reporte de supervisión en formato <strong>${opciones.formato.toUpperCase()}</strong> se ha generado correctamente.<br><br>
                          <small style="color: #666;">En un sistema real, el archivo se descargaría automáticamente.</small>`,
          timer: 4000,
          showConfirmButton: true,
          confirmButtonText: "Entendido",
          confirmButtonColor: "#6c5ce7",
        });

        simularDescarga(opciones.formato);
      }, 500);
    }
  }, 300);

  Swal.fire({
    title: "Procesando reporte...",
    html: `
            <div style="text-align: center; padding: 1rem;">
                <i class="fas fa-cog fa-spin" style="font-size: 3rem; color: #6c5ce7; margin-bottom: 1rem;"></i>
                <p>Iniciando generación de reporte...</p>
            </div>
        `,
    allowOutsideClick: false,
    showConfirmButton: false,
  });
}

function simularDescarga(formato) {
  const fecha = new Date().toISOString().split("T")[0];
  const hora = new Date().toTimeString().slice(0, 5).replace(":", "");
  const nombreArchivo = `supervision_documentos_${fecha}_${hora}.${formato}`;

  console.log(`Simulando descarga de: ${nombreArchivo}`);
}

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

function configurarTooltips() {
  document.querySelectorAll(".semaforo").forEach((semaforo) => {
    const color = semaforo.classList.contains("verde")
      ? "En tiempo (1-3 días hábiles)"
      : semaforo.classList.contains("amarillo")
      ? "Requiere atención (4-5 días hábiles)"
      : "Urgente (6+ días hábiles)";
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
    const porcentajeVerde = Math.round(
      (contadores.verde / contadores.total) * 100
    );
    const porcentajeAmarillo = Math.round(
      (contadores.amarillo / contadores.total) * 100
    );
    const porcentajeRojo = Math.round(
      (contadores.rojo / contadores.total) * 100
    );

    console.log(
      `Distribución de documentos: Verde ${porcentajeVerde}%, Amarillo ${porcentajeAmarillo}%, Rojo ${porcentajeRojo}%`
    );

    if (porcentajeRojo > 30) {
      console.warn(
        `¡Atención! ${porcentajeRojo}% de documentos están en estado urgente`
      );
    }
  }
}

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

// Funciones globales para uso desde HTML
window.filtrarPor = filtrarPor;
window.buscarEnTabla = buscarEnTabla;
window.limpiarFiltros = limpiarFiltros;
window.agregarObservacion = agregarObservacion;
window.verObservaciones = verObservaciones;
window.exportarSupervision = exportarSupervision;
window.actualizarSupervision = actualizarSupervision;

// Agregar estilos CSS para las animaciones
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
    
    /* Estilos para SweetAlert encima del navbar */
    .swal2-container {
        z-index: 999999 !important;
    }
    
    .swal2-popup {
        z-index: 999999 !important;
    }
    
    .swal2-backdrop-show {
        z-index: 999998 !important;
    }
`;
document.head.appendChild(style);

console.log(
  `Módulo de supervisión cargado - Observaciones: ${
    HABILITAR_OBSERVACIONES ? "HABILITADAS" : "DESHABILITADAS"
  }`
);
