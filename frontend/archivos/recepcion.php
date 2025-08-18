<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

// Obtener ID de área desde sesión
$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    die("❌ No se pudo determinar el área del usuario.");
}

$sql = "SELECT m.IdMovimientoDocumento, d.NumeroDocumento, d.Asunto, e.Estado, u.Nombres, u.ApellidoPat, m.Observacion
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN usuarios u ON d.IdUsuarios = u.IdUsuarios
        WHERE m.AreaDestino = ? AND m.Recibido = 0";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
$documentos_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recepción de Documentos</title>

    <!-- Estilos propios -->
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />
    <link rel="stylesheet" href="../../backend/css/recepcion/recepcion.css" />

    <!-- Fuente moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">


    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- jQuery primero -->
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
                <a href="#">⚙️ Configuración</a>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

        <main class="contenido-principal">
            <div class="tarjeta">
                <h2 style="margin-bottom: 1rem;">📥 Documentos para Recepción</h2>

                <?php if (empty($documentos_pendientes)) : ?>
                    <p>No hay documentos pendientes de recepción en tu área.</p>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table id="tablaRecepcion" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Número/Nombre</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Remitente</th>
                                    <th>Observación</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRecepcionBody">
                                <?php foreach ($documentos_pendientes as $doc) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['NumeroDocumento']) ?></td>
                                        <td><?= htmlspecialchars($doc['Asunto']) ?></td>
                                        <td><?= htmlspecialchars($doc['Estado']) ?></td>
                                        <td><?= htmlspecialchars($doc['Nombres'] . ' ' . $doc['ApellidoPat']) ?></td>
                                        <td><?= htmlspecialchars($doc['Observacion']) ?></td>
                                        <td>
                                            <form method="POST" action="../../backend/php/archivos/recepcion_procesar.php">
                                                <input type="hidden" name="id_movimiento" value="<?= $doc['IdMovimientoDocumento'] ?>">
                                                <button type="submit">✅ Recibir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function actualizarRecepcion() {
            $.ajax({
                url: '../../backend/php/ajax/cargar_recepcion_ajax.php',
                method: 'GET',
                success: function(data) {
                    $('#tablaRecepcionBody').html(data);
                },
                error: function() {
                    console.error("Error al actualizar la tabla de recepción.");
                }
            });
        }

        setInterval(actualizarRecepcion, 10000);
    </script>

    <script>
        $(document).ready(function() {
            $('#tablaRecepcion').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });
        });
    </script>
</body>

</html>