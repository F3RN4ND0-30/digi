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

    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css">
    <link rel="stylesheet" href="../../backend/css/seguimiento/busqueda.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="../../backend/js/notificaciones.js"></script>
</head>

<body>
    <div class="layout-escritorio">
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

                <div id="listaNotificaciones" style="display: none; position: absolute; background: #fff; color:black; border: 1px solid #ccc; max-height: 300px; overflow-y: auto; padding: 10px; width: 300px; z-index: 100;">
                    <strong>Notificaciones:</strong>
                    <ul id="contenedorNotificaciones" style="list-style: none; padding-left: 0;"></ul>
                </div>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

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