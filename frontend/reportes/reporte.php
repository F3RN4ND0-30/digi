<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
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

// Obtener las áreas para el filtro
$stmtAreas = $pdo->prepare("SELECT IdAreas, Nombre FROM areas ORDER BY Nombre");
$stmtAreas->execute();
$areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

// Obtener el área seleccionada (filtro)
$areaFiltro = $_GET['area'] ?? '';

// Solo ejecutar la consulta si hay un área seleccionada
$documentos = [];

if ($areaFiltro !== '') {
    $sql = "SELECT d.NumeroDocumento, DATE(d.FechaIngreso) as Fecha, TIME(d.FechaIngreso) as Hora, d.NombreContribuyente, d.Asunto,
            a.Nombre AS AreaOrigen, ad.Nombre AS AreaDestino, d.NumeroFolios
            FROM documentos d
            LEFT JOIN areas a ON d.IdAreas = a.IdAreas
            LEFT JOIN areas ad ON (
                SELECT md.AreaDestino
                FROM movimientodocumento md
                WHERE md.IdDocumentos = d.IdDocumentos
                ORDER BY md.IdMovimientoDocumento DESC LIMIT 1
            ) = ad.IdAreas
            WHERE d.IdAreas = ?
            ORDER BY d.FechaIngreso DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$areaFiltro]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reportes de Documentos - DIGI MPP</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <link rel="stylesheet" href="../../backend/css/archivos/reportes.css" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
    <style>
        .selectize-dropdown {
            z-index: 9999 !important;
            position: absolute !important;
        }

        .tarjeta-header form {
            position: relative;
            z-index: 9999;
        }

        .tarjeta-body,
        .table-responsive {
            position: relative;
            z-index: 1;
            overflow: visible !important;
        }
    </style>
</head>

<body class="p-4">
    <div class="layout-escritorio">
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h1>Reportes de Documentos</h1>
                        <?php if ($areaFiltro !== ''): ?>
                            <div>
                                <a href="exportar_excel.php?area=<?= urlencode($areaFiltro) ?>" class="btn btn-success me-2" target="_blank">
                                    <i class="fa-solid fa-file-excel"></i> Excel
                                </a>
                                <a href="exportar_pdf.php?area=<?= urlencode($areaFiltro) ?>" class="btn btn-danger" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="GET" class="mb-3">
                        <label for="area" class="form-label">Filtrar por Área:</label>
                        <select name="area" id="area">
                            <option value="" selected disabled hidden>-- SELECCIONE UN AREA --</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= htmlspecialchars($area['IdAreas']) ?>" <?= ($areaFiltro == $area['IdAreas']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($area['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="tarjeta-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tablaReporte">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Razón Social</th>
                                    <th>Asunto</th>
                                    <th>Área</th>
                                    <th>Para</th>
                                    <th>Folios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentos as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['NumeroDocumento']) ?></td>
                                        <td><?= htmlspecialchars($doc['Fecha']) ?></td>
                                        <td><?= htmlspecialchars($doc['Hora']) ?></td>
                                        <td><?= htmlspecialchars($doc['NombreContribuyente']) ?></td>
                                        <td><?= htmlspecialchars($doc['Asunto']) ?></td>
                                        <td><?= htmlspecialchars($doc['AreaOrigen']) ?></td>
                                        <td><?= htmlspecialchars($doc['AreaDestino']) ?></td>
                                        <td><?= htmlspecialchars($doc['NumeroFolios']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../backend/js/notificaciones.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], textarea');
            inputs.forEach(function(element) {
                element.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        });

        $('#area').selectize({
            allowEmptyOption: false,
            create: false,
            sortField: 'text',
            dropdownParent: 'body',
            onChange: function(value) {
                if (value !== null && value !== "") {
                    this.$input.closest('form').submit();
                }
            }
        });

        $(document).ready(function() {
            $('#tablaReporte').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    emptyTable: "Seleccione un área para mostrar documentos."
                }
            });
        });
    </script>
</body>

</html>