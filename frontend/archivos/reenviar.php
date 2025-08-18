<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;

$sql = "SELECT m.IdMovimientoDocumento, m.IdDocumentos, d.NumeroDocumento, d.Asunto, u.Nombres, u.ApellidoPat
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN usuarios u ON d.IdUsuarios = u.IdUsuarios
        WHERE m.AreaDestino = ? 
          AND m.Recibido = 1
          AND NOT EXISTS (
              SELECT 1 FROM movimientodocumento m2
              WHERE m2.IdDocumentos = m.IdDocumentos
              AND m2.AreaOrigen = ?
          )
        ORDER BY m.IdMovimientoDocumento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id, $area_id]);
$documentos_recibidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$areas = $pdo->prepare("SELECT IdAreas, Nombre FROM areas WHERE IdAreas != ?");
$areas->execute([$area_id]);
$areas = $areas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reenvío de Documentos</title>
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css">
    <link rel="stylesheet" href="../../backend/css/archivos/reenviados.css" />

    <!-- Fuente moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Selectize CSS -->
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/css/selectize.default.min.css" rel="stylesheet" />

    <!-- Selectize JS -->
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/js/selectize.min.js"></script>
</head>

<body>
    <div class="layout-escritorio">
        <aside class="sidebar">
            <h2>DIGI - MPP</h2>
            <nav>
                <a href="../sisvis/escritorio.php">🏠 Inicio</a>
                <a href="../archivos/recepcion.php">📊 recepción</a>
                <a href="../archivos/enviados.php">📤 Enviados</a>
                <a href="../archivos/reenviar.php">📤 Reenviar</a>
                <a href="#">⚙️ Configuración</a>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

        <main class="contenido-principal">
            <div class="tarjeta">
                <h2>📤 Reenviar Documentos</h2>

                <?php if (empty($documentos_recibidos)): ?>
                    <p>No hay documentos recibidos para reenviar.</p>
                <?php else: ?>
                    <table id="tablaReenvio" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Asunto</th>
                                <th>Remitente</th>
                                <th>Reenviar a</th>
                                <th>Observación</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos_recibidos as $doc): ?>
                                <tr>
                                    <form method="POST" action="../../backend/php/archivos/procesar_reenvio.php">
                                        <td><?= htmlspecialchars($doc['NumeroDocumento']) ?></td>
                                        <td><?= htmlspecialchars($doc['Asunto']) ?></td>
                                        <td><?= htmlspecialchars($doc['Nombres'] . ' ' . $doc['ApellidoPat']) ?></td>
                                        <td>
                                            <select name="nueva_area" required>
                                                <option value="">Seleccione</option>
                                                <?php foreach ($areas as $a): ?>
                                                    <option value="<?= $a['IdAreas'] ?>"><?= htmlspecialchars($a['Nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="observacion" placeholder="Opcional"></td>
                                        <td>
                                            <input type="hidden" name="id_documento" value="<?= $doc['IdDocumentos'] ?>">
                                            <button type="submit">Reenviar</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            $('#tablaReenvio').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });

            $('select').selectize({
                allowEmptyOption: true,
                placeholder: 'Seleccione un área',
                sortField: 'text',
                create: false
            });
        });
    </script>

</body>

</html>