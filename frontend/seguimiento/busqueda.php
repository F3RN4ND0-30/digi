<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Buscar Documentos Enviados</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css" />

    <!-- CSS Principal del Escritorio -->
    <link rel="stylesheet" href="../../backend/css/seguimiento/busqueda.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <script src="../../backend/js/notificaciones.js"></script>
</head>

<body>
    <div class="layout-escritorio">

        <?php include '../navbar/navbar.php'; ?>

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
    <!-- jQuery (obligatorio para DataTables y scripts con $) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
    </script>
    <script>
        $(document).ready(function() {
            var tabla = $('#tablaResultados').DataTable({
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
                        data: 'AreaDestino'
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
                            return `<a href="../../backend/php/archivos/ver_trazabilidad.php?id_documentos=${row.IdDocumentos}" class="btn-seguimiento" target="_blank">🔎 Ver seguimiento</a>`;
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
    </script>
</body>

</html>