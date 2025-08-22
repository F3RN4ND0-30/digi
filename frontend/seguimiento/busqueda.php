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
    <link rel="stylesheet" href="../../backend/css/seguimiento/busqueda.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body>
    <div class="layout-escritorio">

        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="container">
                <h2>🔎 Buscar Documentos Enviados</h2>

                <input type="text" id="inputBusqueda" placeholder="Ingrese número o asunto del documento...">

                <table id="tablaResultados" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Número Documento</th>
                            <th>Asunto</th>
                            <th>Área Destino</th>
                            <th>Fecha Movimiento</th>
                            <th>Estado Recepción</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal de Seguimiento -->
    <div id="modalSeguimiento" class="modal-seguimiento" style="display: none;">
        <div class="modal-content-seguimiento">
            <div class="modal-header-seguimiento">
                <h3 id="tituloModal">Trazabilidad del Documento</h3>
                <button class="close-modal" onclick="cerrarModalSeguimiento()">&times;</button>
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
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTables Responsive JS -->
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>


    <script>
        $(document).ready(function() {
            // Botón de menú móvil
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            // Dropdown
            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                // Cerrar otros
                $('.nav-dropdown').not($(this).parent()).removeClass('active');
                $(this).parent().toggleClass('active');
            });

            // Cerrar dropdown si se hace clic afuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });
        });

        $(document).ready(function() {
            var tabla = $('#tablaResultados').DataTable({
                responsive: true,
                ajax: {
                    url: '../../backend/php/ajax/buscar_documentos_ajax.php',
                    dataSrc: '',
                    data: function(d) {
                        d.busqueda = $('#inputBusqueda').val();
                    }
                },
                columns: [{
                        data: 'NumeroDocumento'
                    },
                    {
                        data: 'Asunto'
                    },
                    {
                        data: 'NombreAreaDestino',
                        render: function(data, type, row) {
                            return data || `[ID ${row.AreaDestino}]`; // fallback por si no hay nombre
                        }
                    },
                    {
                        data: 'FechaMovimiento'
                    },
                    {
                        data: 'Recibido',
                        render: function(data) {
                            return data == 1 ? '✅ Recibido' : '⏳ Pendiente';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<button onclick="verSeguimiento(${row.IdDocumentos})" class="btn-seguimiento">🔎 Ver seguimiento</button>`;
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

            $('#inputBusqueda').on('keyup', function() {
                tabla.ajax.reload();
            });
        });

        // Funciones del Modal de Seguimiento
        function verSeguimiento(idDocumento) {
            document.getElementById('modalSeguimiento').style.display = 'flex';
            document.getElementById('tituloModal').textContent = `Trazabilidad del Documento N° ${idDocumento}`;
            document.getElementById('contenidoSeguimiento').innerHTML = '<div class="loading">Cargando datos...</div>';

            fetch(`../../backend/php/archivos/seguimiento_modal.php?id_documentos=${idDocumento}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('contenidoSeguimiento').innerHTML =
                            `<div class="sin-movimientos">Error: ${data.error}</div>`;
                        return;
                    }

                    if (data.movimientos.length === 0) {
                        document.getElementById('contenidoSeguimiento').innerHTML =
                            '<div class="sin-movimientos">No se encontraron movimientos para este documento.</div>';
                        return;
                    }

                    let html = `
                        <table class="tabla-seguimiento">
                            <thead>
                                <tr>
                                    <th>Área Origen</th>
                                    <th>Área Destino</th>
                                    <th>Fecha de Movimiento</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.movimientos.forEach(mov => {
                        const estadoClass = mov.Recibido == 1 ? 'estado-recibido' : 'estado-pendiente';
                        const estadoTexto = mov.Recibido == 1 ? '✅ Recibido' : '⏳ Pendiente';

                        html += `
                            <tr>
                                <td>${mov.OrigenNombre || mov.AreaOrigen}</td>
                                <td>${mov.DestinoNombre || mov.AreaDestino}</td>
                                <td class="fecha-cell">${mov.FechaMovimiento}</td>
                                <td class="${estadoClass}">${estadoTexto}</td>
                                <td class="observacion-cell">${mov.Observacion || '-'}</td>
                            </tr>
                        `;
                    });

                    html += '</tbody></table>';
                    document.getElementById('contenidoSeguimiento').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('contenidoSeguimiento').innerHTML =
                        '<div class="sin-movimientos">Error al cargar los datos.</div>';
                });
        }

        function cerrarModalSeguimiento() {
            document.getElementById('modalSeguimiento').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('modalSeguimiento');
            if (event.target === modal) {
                cerrarModalSeguimiento();
            }
        });

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalSeguimiento();
            }
        });
    </script>
</body>

</html>