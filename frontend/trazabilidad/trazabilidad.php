<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

// Detectar si es móvil para cargar navbar y css correspondientes
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/Mobile|Android|iP(hone|od|ad)/i', $_SERVER['HTTP_USER_AGENT']);
}

// Definir qué archivo de navbar y CSS se va a usar
$navbarFile = $isMobile ? 'navbar_mobil.php' : 'navbar.php';
$navbarCss  = $isMobile ? 'navbar_mobil.css' : 'navbar.css';

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buscar Documentos Enviados</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- CSS Principal del Escritorio -->
    <link rel="stylesheet" href="../../backend/css/trazabilidad/trazabilidad.css" />
    <link rel="stylesheet" href="../../backend/css/seguimiento/busqueda.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
    <style>
        .tabs-enviados {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab-btn {
            border: none;
            background: #f1f5f9;
            padding: 0.7rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            color: #475569;
            border-radius: 6px 6px 0 0;
            /* cuadradito arriba */
            border: 1px solid transparent;
            border-bottom: none;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            background: #e2e8f0;
        }

        .tab-btn.active {
            background: #6c5ce7;
            color: #fff;
            border-color: #6c5ce7;
            box-shadow: 0 2px 8px rgba(108, 92, 231, 0.35);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Badge para el número/código */
        .badge-numero-enviado {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: #fff;
            box-shadow: 0 2px 6px rgba(108, 92, 231, 0.35);
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <?php
    // Leer el estado del modo aviso global
    $estadoFile = __DIR__ . "/../../frontend/modo_aviso.json";
    $modoActivo = true; // Por defecto activo (sistema normal)

    if (file_exists($estadoFile)) {
        $jsonData = json_decode(file_get_contents($estadoFile), true);
        $modoActivo = $jsonData["modo_pago_activo"];
    }

    // Mostrar banner si NO han pagado
    if (!$modoActivo): ?>
        <div style="
        background: #d50000;
        color: white;
        padding: 15px;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        font-family: Inter, sans-serif;
        z-index: 9999;
        position: relative;
        border-bottom: 4px solid #7f0000;
    ">
            ⚠️ ¡NO HAN PAGADO! ESTA PÁGINA HA SIDO INTERVENIDA POR SISTEMAS ⚠️
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const nav = document.querySelector("nav");
                if (nav) {
                    nav.style.pointerEvents = "none";
                    nav.style.opacity = "0.4";
                }
            });
        </script>

    <?php endif; ?>
    <div class="layout-escritorio">

        <!-- Navbar -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">

            <div class="container">
                <h2>📎 Documentos Anexados en mi Área</h2>

                <input type="text" id="inputBusqueda"
                    placeholder="Buscar por número o asunto...">

                <table id="tablaResultados"
                    class="table table-striped"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>N° Documento</th>
                            <th>Asunto</th>
                            <th>Documento Origen</th>
                            <th>Fecha Anexo</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- Modal de Seguimiento -->
    <div id="modalSeguimiento" class="modal-seguimiento" style="display:none;">
        <div class="modal-content-seguimiento">
            <div class="modal-header-seguimiento">
                <h3 id="tituloModal">Seguimiento del Documento Anexado</h3>
                <button class="close-modal"
                    onclick="cerrarModalSeguimiento()">&times;</button>
            </div>
            <div class="modal-body-seguimiento">
                <div id="contenidoSeguimiento">
                    <div class="loading">Cargando...</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- jQuery (obligatorio para DataTables y scripts con $) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Responsive JS -->
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>


    <script>
        $(document).ready(function() {

            // ------------------------------------------------------------
            // 🔹 NAVBAR Y DROPDOWN
            // ------------------------------------------------------------
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                $('.nav-dropdown').not($(this).parent()).removeClass('active');
                $(this).parent().toggleClass('active');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });

            // ------------------------------------------------------------
            // 🔹 DATATABLE – DOCUMENTOS ANEXADOS
            // ------------------------------------------------------------
            let tabla = $('#tablaResultados').DataTable({
                responsive: true,
                ajax: {
                    url: '../../backend/php/ajax/listar_documentos_anexados_area.php',
                    dataSrc: '',
                    data: function(d) {
                        d.busqueda = $('#inputBusqueda').val();
                    }
                },
                columns: [{
                        data: 'NumeroDocumento',
                        title: 'N° Documento'
                    },
                    {
                        data: 'Asunto',
                        title: 'Asunto'
                    },
                    {
                        data: 'DocumentoOrigen',
                        title: 'Documento Origen'
                    },
                    {
                        data: 'FechaRelacion',
                        title: 'Fecha Anexo'
                    },
                    {
                        data: 'Recibido',
                        title: 'Estado',
                        render: function(data) {
                            return data == 1 ?
                                '✅ Recibido' :
                                '⏳ Pendiente';
                        }
                    },
                    {
                        data: null,
                        title: 'Acción',
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                        <button class="btn-seguimiento"
                            onclick="verSeguimientoAnexado(${row.IdDocumentos})">
                            🔎 Seguimiento
                        </button>
                    `;
                        }
                    }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                order: [
                    [3, 'desc']
                ]
            });

            // ------------------------------------------------------------
            // 🔍 BUSCADOR
            // ------------------------------------------------------------
            $('#inputBusqueda').on('keyup', function() {
                tabla.ajax.reload();
            });

        });


        // ------------------------------------------------------------
        // 🔹 SEGUIMIENTO DOCUMENTO ANEXADO
        // ------------------------------------------------------------
        function verSeguimientoAnexado(idDocumento) {

            const modal = document.getElementById('modalSeguimiento');
            modal.style.display = 'flex';

            document.getElementById('tituloModal').textContent =
                'Seguimiento del Documento Anexado';

            document.getElementById('contenidoSeguimiento').innerHTML =
                '<div class="loading">Cargando seguimiento...</div>';

            fetch('../../backend/php/archivos/seguimiento_documentos_anexados.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        IdDocumentos: idDocumento
                    })
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('contenidoSeguimiento').innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('contenidoSeguimiento').innerHTML =
                        '<div class="sin-movimientos">Error al cargar el seguimiento.</div>';
                });
        }


        // ------------------------------------------------------------
        // 🔹 CERRAR MODAL
        // ------------------------------------------------------------
        function cerrarModalSeguimiento() {
            document.getElementById('modalSeguimiento').style.display = 'none';
        }

        document.addEventListener('click', function(e) {
            const modal = document.getElementById('modalSeguimiento');
            if (e.target === modal) cerrarModalSeguimiento();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModalSeguimiento();
        });
    </script>


    <script>
        $(document).ready(function() {
            // 1. Mostrar/Ocultar el menú móvil completo
            window.toggleMobileMenu = function() {
                $('#mobileMenu').slideToggle(200); // Usa slide para transición suave
            };

            // 2. Controlar los dropdowns internos del menú móvil
            $('#mobileMenu .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                const parentDropdown = $(this).closest('.nav-dropdown');
                const dropdownMenu = parentDropdown.find('.dropdown-menu');

                const isOpen = parentDropdown.hasClass('active');

                // Cerrar todos los demás
                $('#mobileMenu .nav-dropdown').not(parentDropdown).removeClass('active')
                    .find('.dropdown-menu').css('max-height', '0');

                // Toggle el actual
                if (isOpen) {
                    parentDropdown.removeClass('active');
                    dropdownMenu.css('max-height', '0');
                } else {
                    parentDropdown.addClass('active');
                    dropdownMenu.css('max-height', dropdownMenu[0].scrollHeight + 'px');
                }
            });

            // 3. (Opcional) Cerrar dropdowns si se hace clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#mobileMenu .nav-dropdown').length &&
                    !$(e.target).closest('.fas.fa-bars').length) {
                    $('#mobileMenu .nav-dropdown').removeClass('active')
                        .find('.dropdown-menu').css('max-height', '0');
                }
            });
        });
    </script>
</body>

</html>