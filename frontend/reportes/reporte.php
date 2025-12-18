<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}
// Obtener el rol del usuario desde la sesión
$rolUsuario = $_SESSION['dg_rol'] ?? null;

// Si el rol es 1 o 5, se mostrará la opción "Expedientes"
$mostrarExpedientes = in_array($rolUsuario, [1, 5]);

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

// Verificar qué tipo de reporte queremos cargar: documentos o memorándums
$tipoReporte = $_GET['tipo'] ?? 'documentos'; // 'documentos' o 'memorandums'

$documentos = [];
$memorandums = [];
$expedientes = [];

if ($areaFiltro !== '') {
    if ($tipoReporte === 'documentos') {
        // Consulta para documentos
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
    } elseif ($tipoReporte === 'memorandums') {
        // Consulta para memorándums
        $sql = "SELECT m.CodigoMemo, 
               DATE(m.FechaEmision) AS FechaEmision, 
               m.Año, 
               a.Nombre AS AreaOrigen,  -- Nombre del área
               m.TipoMemo, 
               m.Asunto, 
               CONCAT(u.Nombres, ' ', u.ApellidoPat, ' ', u.ApellidoMat) AS UsuarioEmisor,  -- Concatenar nombre, apellido paterno y apellido materno
               m.NumeroFolios
        FROM memorandums m
        LEFT JOIN areas a ON m.IdAreaOrigen = a.IdAreas
        LEFT JOIN usuarios u ON m.IdUsuarioEmisor = u.IdUsuarios
        WHERE m.IdAreaOrigen = ?
        ORDER BY m.FechaEmision DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$areaFiltro]);
        $memorandums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($tipoReporte === 'expedientes') {
        // Consulta para documentos
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
                WHERE d.IdAreas = ? AND d.NumeroDocumento LIKE '%EXP.%'
                ORDER BY d.FechaIngreso DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$areaFiltro]);
        $expedientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reportes - DIGI MPP</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <link rel="stylesheet" href="../../backend/css/archivos/reportes.css" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        .btn-custom-excel {
            background-color: #28a745;
            color: #fff;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-custom-excel i {
            margin-right: 5px;
        }

        .btn-custom-pdf {
            background-color: #d9534f;
            color: #fff;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-custom-pdf i {
            margin-right: 5px;
        }
    </style>
</head>

<body class="p-4">
    <?php
    // Leer el estado del modo aviso global
    $estadoFile = __DIR__ . "/../../frontend/modo_aviso.json";
    $modoActivo = true; // Por defecto activo (sistema normal)

    if (file_exists($estadoFile)) {
        $jsonData = json_decode(file_get_contents($estadoFile), true);
        $modoActivo = $jsonData["modo_pago_activo"];
    }

    // Mostrar banner si NO han pagado
    if (!$modoActivo): ?>
        <div style="
        background: #d50000;
        color: white;
        padding: 15px;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        font-family: Inter, sans-serif;
        z-index: 9999;
        position: relative;
        border-bottom: 4px solid #7f0000;
    ">
            ⚠️ ¡NO HAN PAGADO! ESTA PÁGINA HA SIDO INTERVENIDA POR SISTEMAS ⚠️
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const nav = document.querySelector("nav");
                if (nav) {
                    nav.style.pointerEvents = "none";
                    nav.style.opacity = "0.4";
                }
            });
        </script>

    <?php endif; ?>
    <div class="layout-escritorio">
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h1>Reportes de Documentos y Memorándums</h1>
                        <?php if ($areaFiltro !== ''): ?>
                            <div>
                                <!-- Botón de Excel -->
                                <a href="exportar_excel.php?area=<?= urlencode($areaFiltro) ?>&tipo=<?= urlencode($tipoReporte) ?>" class="btn-custom-excel" target="_blank">
                                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                                </a>

                                <!-- Botón de PDF -->
                                <a href="exportar_pdf.php?area=<?= urlencode($areaFiltro) ?>&tipo=<?= urlencode($tipoReporte) ?>" class="btn-custom-pdf" target="_blank">
                                    <i class="fa-solid fa-file-pdf"></i> Exportar PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Formulario de filtro -->
                    <form method="GET" class="mb-3" id="filtroForm">
                        <label for="area" class="form-label">Filtrar por Área:</label>
                        <select name="area" id="area" onchange="this.form.submit();">
                            <option value="" selected disabled hidden>-- SELECCIONE UN AREA --</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= htmlspecialchars($area['IdAreas']) ?>" <?= ($areaFiltro == $area['IdAreas']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($area['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="tipo" class="form-label">Tipo de Reporte:</label>
                        <select name="tipo" id="tipo" onchange="this.form.submit();">
                            <option value="documentos" <?= $tipoReporte === 'documentos' ? 'selected' : '' ?>>Documentos</option>
                            <option value="memorandums" <?= $tipoReporte === 'memorandums' ? 'selected' : '' ?>>Memorándums</option>
                            <?php if ($mostrarExpedientes): ?>
                                <option value="expedientes" <?= $tipoReporte === 'expedientes' ? 'selected' : '' ?>>Expedientes</option>
                            <?php endif; ?>
                        </select>
                    </form>
                </div>

                <div class="tarjeta-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tablaReporte">
                            <thead>
                                <tr>
                                    <?php if ($tipoReporte === 'documentos'): ?>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Razón Social</th>
                                        <th>Asunto</th>
                                        <th>Área</th>
                                        <th>Para</th>
                                        <th>Folios</th>
                                    <?php elseif ($tipoReporte === 'memorandums'): ?>
                                        <th>Código Memo</th>
                                        <th>Fecha Emisión</th>
                                        <th>Año</th>
                                        <th>Área Origen</th>
                                        <th>Usuario Emisor</th>
                                        <th>Tipo Memo</th>
                                        <th>Asunto</th>
                                        <th>Folios</th>
                                    <?php elseif ($tipoReporte === 'expedientes'): ?>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Razón Social</th>
                                        <th>Asunto</th>
                                        <th>Área</th>
                                        <th>Para</th>
                                        <th>Folios</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($tipoReporte === 'documentos'): ?>
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
                                <?php elseif ($tipoReporte === 'memorandums'): ?>
                                    <?php foreach ($memorandums as $mem): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mem['CodigoMemo']) ?></td>
                                            <td><?= htmlspecialchars($mem['FechaEmision']) ?></td>
                                            <td><?= htmlspecialchars($mem['Año']) ?></td>
                                            <td><?= htmlspecialchars($mem['AreaOrigen']) ?></td>
                                            <td><?= htmlspecialchars($mem['UsuarioEmisor']) ?></td>
                                            <td><?= htmlspecialchars($mem['TipoMemo']) ?></td>
                                            <td><?= htmlspecialchars($mem['Asunto']) ?></td>
                                            <td><?= htmlspecialchars($mem['NumeroFolios']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php elseif ($tipoReporte === 'expedientes'): ?>
                                    <?php foreach ($expedientes as $exp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($exp['NumeroDocumento']) ?></td>
                                            <td><?= htmlspecialchars($exp['Fecha']) ?></td>
                                            <td><?= htmlspecialchars($exp['Hora']) ?></td>
                                            <td><?= htmlspecialchars($exp['NombreContribuyente']) ?></td>
                                            <td><?= htmlspecialchars($exp['Asunto']) ?></td>
                                            <td><?= htmlspecialchars($exp['AreaOrigen']) ?></td>
                                            <td><?= htmlspecialchars($exp['AreaDestino']) ?></td>
                                            <td><?= htmlspecialchars($exp['NumeroFolios']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                if (value) {
                    this.$input.closest('form').submit();
                }
            }
        });

        $('#tipo').selectize({
            allowEmptyOption: false,
            create: false,
            sortField: 'text',
            dropdownParent: 'body',
            onChange: function(value) {
                if (value) {
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