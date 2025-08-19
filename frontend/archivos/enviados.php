<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    die("❌ No se pudo determinar el área del usuario.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Documentos Enviados - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css" />

    <!-- CSS de enviados -->
    <link rel="stylesheet" href="../../backend/css/archivos/enviados.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<<<<<<< HEAD
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
=======
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="../../backend/js/notificaciones.js"></script>
>>>>>>> main
</head>

<body>
    <div class="layout-escritorio">
<<<<<<< HEAD

        <?php include '../navbar/navbar.php'; ?>
=======
        <aside class="sidebar">
            <h2>DIGI - MPP</h2>
            <nav>
                <a href="../sisvis/escritorio.php">🏠 Inicio</a>
                <a href="../archivos/recepcion.php">📥 Recepción</a>
                <a href="../archivos/enviados.php">📤 Enviados</a>
                <a href="../archivos/reenviar.php">📤 Reenviar</a>
                <a href="../seguimiento/busqueda.php">📤 Buscar</a>
                <a href="#">⚙️ Configuración</a>
                <div id="notificaciones" style="position: relative; cursor: pointer;">
                    🔔 <span id="contador" style="color: red; font-weight: bold;"></span>
                </div>

                <div id="listaNotificaciones" style="display: none; position: absolute; background: #fff; color:black; border: 1px solid #ccc; max-height: 300px; overflow-y: auto; padding: 10px; width: 350px; z-index: 100;">
                    <strong>Notificaciones:</strong>
                    <ul id="contenedorNotificaciones" style="list-style: none; padding-left: 0;"></ul>
                </div>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>
>>>>>>> main

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2><i class="fas fa-paper-plane"></i> Documentos Enviados</h2>
                </div>

                <div class="tarjeta-body">
                    <div class="table-responsive">
                        <table id="tablaEnviados" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Número/Nombre</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Área Destino</th>
                                    <th>Observación</th>
                                    <th>Recibido</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTables -->
    <script>
        $(document).ready(function() {
            $('#tablaEnviados').DataTable({
                ajax: {
                    url: '../../backend/php/ajax/cargar_enviados_ajax.php',
                    dataSrc: ''
                },
                columns: [{
                        data: 'IdMovimientoDocumento'
                    },
                    {
                        data: 'NumeroDocumento'
                    },
                    {
                        data: 'Asunto'
                    },
                    {
                        data: 'Estado'
                    },
                    {
                        data: 'FechaMovimiento'
                    },
                    {
                        data: 'AreaDestino'
                    },
                    {
                        data: 'Observacion'
                    },
                    {
                        data: 'Recibido',
                        render: function(data) {
                            return data == 1 ?
                                '<span class="badge bg-success"><i class="fas fa-check"></i> Recibido</span>' :
                                '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pendiente</span>';
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                responsive: true,
                pageLength: 25
            });

            // Auto-refresh
            setInterval(() => {
                $('#tablaEnviados').DataTable().ajax.reload(null, false);
            }, 30000);
        });
    </script>

    <!-- JavaScript del Navbar -->
    <script>
        // Esperar a que todo esté cargado
        $(document).ready(function() {
            // Mobile toggle
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            // Dropdown functionality
            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                // Cerrar otros dropdowns
                $('.nav-dropdown').not($(this).parent()).removeClass('active');

                // Toggle este dropdown
                $(this).parent().toggleClass('active');
            });

            // Cerrar dropdown al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });
        });
    </script>
</body>

</html>