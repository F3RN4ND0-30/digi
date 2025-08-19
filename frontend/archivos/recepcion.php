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

$sql = "SELECT 
            m.IdMovimientoDocumento,
            d.NumeroDocumento,
            d.Asunto,
            e.Estado,
            a_origen.Nombre AS AreaOrigen,
            m.Observacion,
            m.FechaMovimiento
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a_origen ON m.AreaOrigen = a_origen.IdAreas
        WHERE m.AreaDestino = ? AND m.Recibido = 0
        ORDER BY m.FechaMovimiento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
$documentos_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recepción de Documentos - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css" />
    
    <!-- CSS específico -->
    <link rel="stylesheet" href="../../backend/css/archivos/recepcion.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- jQuery primero -->
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
                <!-- En tu navbar o barra lateral -->
                <div id="notificaciones" style="position: relative; cursor: pointer;">
                    🔔 <span id="contador" style="color: red; font-weight: bold;"></span>
                </div>

                <!-- Contenedor para la lista -->
                <div id="listaNotificaciones" style="display: none; position: absolute; background: #fff; color:black; border: 1px solid #ccc; max-height: 300px; overflow-y: auto; padding: 10px; width: 300px; z-index: 100;">
                    <strong>Notificaciones:</strong>
                    <ul id="contenedorNotificaciones" style="list-style: none; padding-left: 0;"></ul>
                </div>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2><i class="fas fa-inbox"></i> Documentos para Recepción</h2>
                </div>
                
                <div class="tarjeta-body">
                    <?php if (empty($documentos_pendientes)) : ?>
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="fas fa-info-circle me-3"></i>
                            <div>
                                <strong>Sin documentos pendientes</strong><br>
                                No hay documentos pendientes de recepción en tu área.
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table id="tablaRecepcion" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Asunto</th>
                                        <th>Estado</th>
                                        <th>Área de Origen</th>
                                        <th>Fecha de Envío</th>
                                        <th>Observación</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaRecepcionBody">
                                    <?php foreach ($documentos_pendientes as $doc): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($doc['NumeroDocumento']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($doc['Asunto']) ?>">
                                                    <?= htmlspecialchars($doc['Asunto']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= htmlspecialchars($doc['Estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <i class="fas fa-building me-1"></i>
                                                <?= htmlspecialchars($doc['AreaOrigen']) ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('d/m/Y H:i', strtotime($doc['FechaMovimiento'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($doc['Observacion']) ?>">
                                                    <?= htmlspecialchars($doc['Observacion']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" action="../../backend/php/archivos/recepcion_procesar.php" class="d-inline">
                                                    <input type="hidden" name="id_movimiento" value="<?= $doc['IdMovimientoDocumento'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Confirmar recepción?')">
                                                        <i class="fas fa-check"></i> Recibir
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaRecepcion').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                responsive: true,
                pageLength: 25,
                order: [[4, 'desc']],
                columnDefs: [
                    { targets: [6], orderable: false }
                ]
            });
        });

        function actualizarRecepcion() {
            $.ajax({
                url: '../../backend/php/ajax/cargar_recepcion_ajax.php',
                method: 'GET',
                success: function(data) {
                    $('#tablaRecepcionBody').html(data);
                    $('#tablaRecepcion').DataTable().destroy();
                    $('#tablaRecepcion').DataTable({
                        language: {
                            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                        },
                        responsive: true,
                        pageLength: 25,
                        order: [[4, 'desc']],
                        columnDefs: [
                            { targets: [6], orderable: false }
                        ]
                    });
                },
                error: function() {
                    console.error("Error al actualizar la tabla de recepción.");
                }
            });
        }

        setInterval(actualizarRecepcion, 30000);
    </script>
</body>

</html>