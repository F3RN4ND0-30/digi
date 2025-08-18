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

// Obtener documentos enviados por el área actual (ÁreaOrigen)
$sql = "SELECT m.IdMovimientoDocumento, d.NumeroDocumento, d.Asunto, e.Estado, a.Nombre AS area_destino, m.Recibido, m.Observacion
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a ON m.AreaDestino = a.IdAreas
        WHERE m.AreaOrigen = ?
        ORDER BY m.IdMovimientoDocumento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
$documentos_enviados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Documentos Enviados</title>

    <!-- Estilos personalizados -->
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
                <h2 style="margin-bottom: 1rem;">📤 Documentos Enviados</h2>

                <?php if (empty($documentos_enviados)) : ?>
                    <p>No has enviado documentos desde tu área aún.</p>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table id="tablaEnviados" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Área Destino</th>
                                    <th>Observación</th>
                                    <th>Recibido</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEnviadosBody">
                                <?php foreach ($documentos_enviados as $doc) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['NumeroDocumento']) ?></td>
                                        <td><?= htmlspecialchars($doc['Asunto']) ?></td>
                                        <td><?= htmlspecialchars($doc['Estado']) ?></td>
                                        <td><?= htmlspecialchars($doc['area_destino']) ?></td>
                                        <td><?= htmlspecialchars($doc['Observacion']) ?></td>
                                        <td><?= $doc['Recibido'] ? '✅ Sí' : '⏳ No' ?></td>
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
        function actualizarEnviados() {
            $.ajax({
                url: '../../backend/php/ajax/cargar_enviados_ajax.php',
                method: 'GET',
                success: function(data) {
                    $('#tablaEnviadosBody').html(data);
                },
                error: function() {
                    console.error("Error al actualizar la tabla de enviados.");
                }
            });
        }

        setInterval(actualizarEnviados, 10000); // cada 10 segundos
    </script>

    <script>
        $(document).ready(function() {
            $('#tablaEnviados').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });
        });
    </script>
</body>

</html>