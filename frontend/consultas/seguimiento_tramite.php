<?php
session_start();
require '../../backend/db/conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seguimiento de Trámite | DIGI - MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="../../backend/css/seguimiento/seguimiento_tramite.css" />

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
</head>

<body>
    <!-- Botón Volver al Inicio -->
    <a href="../../index.php" class="btn-volver">
        <i class="fas fa-home"></i>
        <span>Volver al Inicio</span>
    </a>

    <!-- Logo Municipal -->
    <div class="muni-logo-header">
        <img src="../../backend/img/munipisco.png" alt="Municipalidad Provincial de Pisco">
    </div>

    <div class="container-principal">
        <!-- Header -->
        <div class="header-seguimiento">
            <div class="header-icon">
                <i class="fas fa-route"></i>
            </div>
            <h1 class="titulo-principal">Seguimiento de Trámite</h1>
            <p class="subtitulo">Consulta el estado actual de tu documento</p>
        </div>

        <!-- Contenedor de Resultados (inicialmente oculto) -->
        <div id="contenedorResultados" class="resultados-container" style="display: none;">

            <!-- Info del Documento -->
            <div class="documento-info-card">
                <div class="info-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>Información del Documento</h3>
                </div>
                <div class="info-body">
                    <div class="info-item">
                        <span class="info-label">N° EXPEDIENTE:</span>
                        <span class="info-value" id="infoExpediente">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ASUNTO:</span>
                        <span class="info-value" id="infoAsunto">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">CONTRIBUYENTE:</span>
                        <span class="info-value" id="infoContribuyente">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">FECHA DE INGRESO:</span>
                        <span class="info-value" id="infoFechaIngreso">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">N° DE FOLIOS:</span>
                        <span class="info-value" id="infoFolios">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ESTADO ACTUAL:</span>
                        <span class="info-value">
                            <span id="infoEstado" class="estado-pill estado-pill--pendiente">-</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeline de Movimientos -->
            <div class="timeline-header">
                <i class="fas fa-clock"></i>
                <h3>Historial de Movimientos</h3>
            </div>

            <div id="timelineMovimientos" class="timeline-container">
                <!-- Se llenará dinámicamente -->
            </div>

            <!-- Botón para expandir/contraer historial cuando es largo -->
            <div id="historialAcciones" class="historial-acciones" style="display:none;">
                <button id="btnToggleHistorial" type="button" class="btn-toggle-historial">
                    Ver historial completo
                </button>
            </div>

            <!-- Botón Nueva Búsqueda -->
            <div class="acciones-container">
                <button onclick="nuevaBusqueda()" class="btn-nueva-busqueda">
                    <i class="fas fa-search"></i>
                    Nueva Búsqueda
                </button>
            </div>
        </div>

        <!-- Mensaje Sin Resultados -->
        <div id="sinResultados" class="sin-resultados" style="display: none;">
            <div class="sin-resultados-icon">
                <i class="fas fa-search-minus"></i>
            </div>
            <h3>No se encontraron resultados</h3>
            <p>Verifica que el DNI/RUC y el número de expediente sean correctos</p>
            <button onclick="nuevaBusqueda()" class="btn-reintentar">
                <i class="fas fa-redo"></i>
                Intentar de nuevo
            </button>
        </div>
    </div>

    <!-- MODAL DE BÚSQUEDA -->
    <div id="modalBusqueda" class="modal-overlay">
        <div class="modal-busqueda">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h2>Buscar Documento</h2>
                <p class="modal-subtitle">Ingresa tus datos para consultar el estado de tu trámite</p>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="dniRuc">
                        <i class="fas fa-id-card"></i>
                        DNI o RUC
                    </label>
                    <input
                        type="text"
                        id="dniRuc"
                        class="input-field"
                        placeholder="Ingresa tu DNI o RUC"
                        maxlength="11">
                </div>

                <div class="form-group">
                    <label for="numeroExpediente">
                        <i class="fas fa-file-alt"></i>
                        Número de Expediente
                    </label>
                    <input
                        type="text"
                        id="numeroExpediente"
                        class="input-field"
                        placeholder="Ej: 2025-001234">
                </div>

                <button id="btnBuscarDocumento" class="btn-buscar">
                    <i class="fas fa-search"></i>
                    <span>Buscar Documento</span>
                </button>

                <div class="modal-footer">
                    <p class="ayuda-text">
                        <i class="fas fa-info-circle"></i>
                        Si no conoces tu número de expediente, comunícate con Mesa de Partes
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Buscando documento...</p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        const MAX_ITEMS_RESUMIDOS = 4; // cuántos movimientos se muestran siempre (el resto se colapsa)
        let datosGlobal = [];

        // ==================== MOSTRAR MODAL AL CARGAR ====================
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("modalBusqueda").style.display = "flex";

            // Focus automático en el primer input
            setTimeout(() => {
                const dniInput = document.getElementById("dniRuc");
                if (dniInput) dniInput.focus();
            }, 500);

            // Enter para buscar
            document.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && document.getElementById("modalBusqueda").style.display === "flex") {
                    document.getElementById("btnBuscarDocumento").click();
                }
            });
        });

        // ==================== FUNCIÓN BUSCAR ====================
        document.getElementById("btnBuscarDocumento").addEventListener("click", function() {
            let dniRuc = document.getElementById("dniRuc").value.trim();
            let expediente = document.getElementById("numeroExpediente").value.trim();

            if (dniRuc === "" || expediente === "") {
                mostrarAlerta("Por favor, completa todos los campos", "warning");
                return;
            }

            // Mostrar loading
            document.getElementById("loadingOverlay").style.display = "flex";
            document.getElementById("modalBusqueda").style.display = "none";

            // Petición AJAX
            $.ajax({
                url: "../../backend/php/ajax/buscar_documento_publico.php",
                method: "GET",
                data: {
                    dni_ruc: dniRuc,
                    expediente: expediente
                },
                dataType: "json",
                success: function(response) {
                    document.getElementById("loadingOverlay").style.display = "none";

                    if (response && response.length > 0) {
                        datosGlobal = response;
                        mostrarResultados(response);
                    } else {
                        mostrarSinResultados();
                    }
                },
                error: function(xhr, status, error) {
                    document.getElementById("loadingOverlay").style.display = "none";
                    mostrarAlerta("Error al buscar el documento. Intenta nuevamente.", "error");
                    console.error("Error:", error);
                }
            });
        });

        // ==================== MOSTRAR RESULTADOS ====================
        function mostrarResultados(datos) {
            document.getElementById("sinResultados").style.display = "none";

            // Ordenar por fecha ASC (primero ingreso, último estado actual)
            datos.sort((a, b) => new Date(a.FechaMovimiento) - new Date(b.FechaMovimiento));

            const primerRegistro = datos[0];
            const ultimoRegistro = datos[datos.length - 1];

            // Ficha del documento
            document.getElementById("infoExpediente").textContent = primerRegistro.NumeroDocumento || "-";
            document.getElementById("infoAsunto").textContent = primerRegistro.Asunto || "-";
            document.getElementById("infoContribuyente").textContent = primerRegistro.NombreContribuyente || "-";
            document.getElementById("infoFechaIngreso").textContent = formatearFecha(primerRegistro.FechaIngreso) || "-";
            // Folios del DOCUMENTO (no del movimiento)
            document.getElementById("infoFolios").textContent = primerRegistro.DocumentoFolios || "-";

            // Estado actual (semáforo) usando el último movimiento
            const estadoActualInfo = obtenerEstadoInfo(ultimoRegistro, true);
            actualizarEstadoActual(estadoActualInfo);

            // Crear timeline
            construirTimeline(datos);

            // Mostrar contenedor con animación
            const contenedor = document.getElementById("contenedorResultados");
            contenedor.style.display = "block";
            setTimeout(() => {
                contenedor.classList.add("show");
            }, 50);
        }

        // Construir timeline con opción de colapsar historial
        function construirTimeline(datos) {
            const timeline = document.getElementById("timelineMovimientos");
            timeline.innerHTML = "";

            const muchosMovimientos = datos.length > MAX_ITEMS_RESUMIDOS;

            datos.forEach((mov, index) => {
                const esUltimo = (index === datos.length - 1);
                const estadoInfo = obtenerEstadoInfo(mov, esUltimo);

                const item = document.createElement("div");
                item.className = `timeline-item ${estadoInfo.clase}`;

                // Si hay muchos movimientos, los más antiguos se marcan como colapsables
                if (muchosMovimientos && index < datos.length - MAX_ITEMS_RESUMIDOS) {
                    item.classList.add("timeline-item--colapsado");
                }

                const foliosMovimiento = mov.MovimientoFolios ? ` (${mov.MovimientoFolios} folios)` : "";

                item.innerHTML = `
                    <div class="timeline-marker">
                        <i class="${estadoInfo.icono}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-header-content">
                            <h4>${mov.OrigenNombre || 'Área no especificada'}</h4>
                            <span class="timeline-badge ${estadoInfo.badgeClass}">
                                ${estadoInfo.texto}
                            </span>
                        </div>
                        <div class="timeline-details">
                            <div class="detail-item">
                                <i class="fas fa-arrow-right"></i>
                                <span><strong>Destino:</strong> ${mov.DestinoNombre || 'No especificado'}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span><strong>Fecha:</strong> ${formatearFecha(mov.FechaMovimiento)}${foliosMovimiento}</span>
                            </div>
                            ${mov.InformeNombre ? `
                            <div class="detail-item">
                                <i class="fas fa-file-invoice"></i>
                                <span><strong>Informe:</strong> ${mov.InformeNombre}</span>
                            </div>
                            ` : ''}
                            ${mov.Observacion ? `
                            <div class="detail-item observacion">
                                <i class="fas fa-comment"></i>
                                <span><strong>Observación:</strong> ${mov.Observacion}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;

                timeline.appendChild(item);
            });

            const historialAcciones = document.getElementById("historialAcciones");
            const btnToggle = document.getElementById("btnToggleHistorial");

            if (datos.length > MAX_ITEMS_RESUMIDOS) {
                historialAcciones.style.display = "flex";
                btnToggle.textContent = "Ver historial completo";
                btnToggle.onclick = toggleHistorial;
            } else {
                historialAcciones.style.display = "none";
            }
        }

        // Alterna entre mostrar todo el historial o resumido
        function toggleHistorial() {
            const btnToggle = document.getElementById("btnToggleHistorial");
            const itemsColapsados = document.querySelectorAll(".timeline-item--colapsado");
            const estaExpandido = btnToggle.dataset.expanded === "1";

            if (estaExpandido) {
                // Volver a colapsar
                itemsColapsados.forEach(item => item.classList.remove("timeline-item--visible"));
                btnToggle.textContent = "Ver historial completo";
                btnToggle.dataset.expanded = "0";
            } else {
                // Mostrar todos
                itemsColapsados.forEach(item => item.classList.add("timeline-item--visible"));
                btnToggle.textContent = "Ver menos movimientos";
                btnToggle.dataset.expanded = "1";
            }
        }

        // ==================== OBTENER INFO DE ESTADO ====================
        function obtenerEstadoInfo(mov, esUltimo) {
            let info = {
                texto: "",
                icono: "",
                clase: "",
                badgeClass: ""
            };

            if (esUltimo) {
                if (mov.IdEstadoDocumento == 8) {
                    info.texto = "Bloqueado";
                    info.icono = "fas fa-ban";
                    info.clase = "bloqueado";
                    info.badgeClass = "badge-bloqueado";
                } else if (mov.Finalizado == 1) {
                    info.texto = "Finalizado";
                    info.icono = "fas fa-check-circle";
                    info.clase = "finalizado";
                    info.badgeClass = "badge-finalizado";
                } else if (mov.Recibido == 1) {
                    info.texto = "Recibido";
                    info.icono = "fas fa-check";
                    info.clase = "recibido";
                    info.badgeClass = "badge-recibido";
                } else {
                    info.texto = "En trámite";
                    info.icono = "fas fa-clock";
                    info.clase = "pendiente";
                    info.badgeClass = "badge-pendiente";
                }
            } else {
                if (mov.Recibido == 1) {
                    info.texto = "Recibido";
                    info.icono = "fas fa-check";
                    info.clase = "recibido";
                    info.badgeClass = "badge-recibido";
                } else {
                    info.texto = "Procesado";
                    info.icono = "fas fa-history";
                    info.clase = "procesado";
                    info.badgeClass = "badge-procesado";
                }
            }

            return info;
        }

        // Actualiza la pastilla de "Estado actual" en la ficha superior
        function actualizarEstadoActual(estadoInfo) {
            const pill = document.getElementById("infoEstado");
            pill.textContent = estadoInfo.texto;
            pill.className = "estado-pill"; // reset
            pill.classList.add(`estado-pill--${estadoInfo.clase || 'pendiente'}`);
        }

        // ==================== MOSTRAR SIN RESULTADOS ====================
        function mostrarSinResultados() {
            document.getElementById("contenedorResultados").style.display = "none";
            document.getElementById("contenedorResultados").classList.remove("show");
            document.getElementById("sinResultados").style.display = "flex";
        }

        // ==================== NUEVA BÚSQUEDA ====================
        function nuevaBusqueda() {
            document.getElementById("contenedorResultados").style.display = "none";
            document.getElementById("contenedorResultados").classList.remove("show");
            document.getElementById("sinResultados").style.display = "none";
            document.getElementById("dniRuc").value = "";
            document.getElementById("numeroExpediente").value = "";
            document.getElementById("modalBusqueda").style.display = "flex";
            setTimeout(() => {
                document.getElementById("dniRuc").focus();
            }, 300);
        }

        // ==================== FORMATEAR FECHA ====================
        function formatearFecha(fechaStr) {
            if (!fechaStr) return "-";
            const fecha = new Date(fechaStr);
            if (isNaN(fecha.getTime())) return fechaStr; // por si viene en otro formato

            const opciones = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return fecha.toLocaleDateString('es-PE', opciones);
        }

        // ==================== MOSTRAR ALERTA SIMPLE ====================
        function mostrarAlerta(mensaje, tipo) {
            alert(mensaje);
        }
    </script>

</body>

</html>