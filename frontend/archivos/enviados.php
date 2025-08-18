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
    <title>Documentos Enviados</title>

    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />
    <link rel="stylesheet" href="../../backend/css/archivos/enviados.css" />

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
                <a href="#">⚙️ Configuración</a>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

        <main class="contenido-principal">
            <div class="tarjeta">
                <h2>📤 Documentos Enviados</h2>

                <div style="overflow-x: auto;">
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
        </main>
    </div>

    <script>
        $(document).ready(function() {
            const tabla = $('#tablaEnviados').DataTable({
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
                            return data == 1 ? '✅ Sí' : '⏳ No';
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });

            setInterval(() => {
                tabla.ajax.reload(null, false);
            }, 10000);
        });
    </script>
</body>

</html>