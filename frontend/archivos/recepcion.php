<?php
session_start();

if (isset($_SESSION['mensaje'])) {
    $mensaje = addslashes($_SESSION['mensaje']);
    echo "<script>window.onload=function(){alert('$mensaje');};</script>";
    unset($_SESSION['mensaje']);
}

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
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

$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    die("❌ No se pudo determinar el área del usuario.");
}

/* ==========================================================
   1) DOCUMENTOS pendientes (flujo existente)
   ========================================================== */
$sqlDocs = "SELECT 
            'DOC' AS TipoRegistro,
            m.IdMovimientoDocumento,
            NULL  AS IdMemo,
            d.NumeroDocumento,
            d.Asunto,
            e.Estado,
            a_origen.Nombre AS AreaOrigen,
            m.Observacion,
            m.FechaMovimiento,
            d.NumeroFolios,
            t.Descripcion   AS TipoObjeto,
            NULL AS TipoMemo
        FROM movimientodocumento m
        INNER JOIN documentos d        ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN estadodocumento e   ON d.IdEstadoDocumento = e.IdEstadoDocumento
        INNER JOIN areas a_origen      ON m.AreaOrigen = a_origen.IdAreas
        INNER JOIN tipo_objeto t       ON d.IdTipoObjeto = t.IdTipoObjeto
        WHERE m.AreaDestino = :area AND m.Recibido = 0";

$stmt = $pdo->prepare($sqlDocs);
$stmt->execute(['area' => $area_id]);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   2) MEMORÁNDUMS pendientes para mi área
   ========================================================== */
/* OJO: acá ya NO usamos m.Estado, sino IdEstadoDocumento + tabla estadodocumento */
$sqlMemos = "SELECT
            'MEMO' AS TipoRegistro,
            NULL AS IdMovimientoDocumento,
            m.IdMemo,
            m.CodigoMemo      AS NumeroDocumento,
            m.Asunto,
            e.Estado          AS Estado,
            a_origen.Nombre   AS AreaOrigen,
            ''                AS Observacion,
            m.FechaEmision    AS FechaMovimiento,
            NULL              AS NumeroFolios,
            'SIN OBJETO'      AS TipoObjeto,
            m.TipoMemo        AS TipoMemo
        FROM memorandums m
        INNER JOIN memorandum_destinos md ON md.IdMemo = m.IdMemo
        INNER JOIN areas a_origen         ON a_origen.IdAreas = m.IdAreaOrigen
        INNER JOIN estadodocumento e      ON e.IdEstadoDocumento = m.IdEstadoDocumento
        WHERE md.IdAreaDestino = :area
          AND m.IdEstadoDocumento = 1"; 

$stmt2 = $pdo->prepare($sqlMemos);
$stmt2->execute(['area' => $area_id]);
$memos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

/* Unir y ordenar (desc por fecha) */
$documentos_pendientes = array_merge($docs, $memos);
usort($documentos_pendientes, function ($a, $b) {
    return strtotime($b['FechaMovimiento']) <=> strtotime($a['FechaMovimiento']);
});
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recepción de Documentos - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- CSS Principal del Escritorio -->
    <link rel="stylesheet" href="../../backend/css/archivos/recepcion.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />

    <style>
        /* Contenedor vertical para el número + etiqueta de memo */
        .numero-memo-wrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
            max-width: 160px;
        }

        .badge-memo-tipo {
            font-size: 0.70rem;
            font-weight: 600;
            border-radius: 999px;
            white-space: nowrap;
        }

        .badge-numero {
            font-size: 0.78rem;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="layout-escritorio">

        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

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
                                No hay documentos o memorándums pendientes de recepción en tu área.
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
                                        <th>N° Folios</th>
                                        <th>Tipo de Objeto</th>
                                        <th>Observación</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaRecepcionBody">
                                    <?php foreach ($documentos_pendientes as $doc): ?>
                                        <?php
                                        $esMemo = ($doc['TipoRegistro'] === 'MEMO');
                                        $labelTipoMemo = '';

                                        if ($esMemo && !empty($doc['TipoMemo'])) {
                                            if ($doc['TipoMemo'] === 'MULTIPLE') {
                                                $labelTipoMemo = 'MEMO MÚLTIPLE';
                                            } elseif ($doc['TipoMemo'] === 'CIRCULAR') {
                                                $labelTipoMemo = 'MEMO CIRCULAR';
                                            } else {
                                                $labelTipoMemo = 'MEMO';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="numero-memo-wrapper">
                                                    <?php if ($esMemo && $labelTipoMemo): ?>
                                                        <span class="badge bg-success badge-memo-tipo">
                                                            <?= htmlspecialchars($labelTipoMemo) ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <!-- Número: mismo estilo morado para todos -->
                                                    <span class="badge bg-primary badge-numero">
                                                        <?= htmlspecialchars($doc['NumeroDocumento']) ?>
                                                    </span>
                                                </div>
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
                                                <span class="badge bg-secondary">
                                                    <?= $doc['NumeroFolios'] !== null ? htmlspecialchars($doc['NumeroFolios']) : '-' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($doc['TipoObjeto']) ?></span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($doc['Observacion']) ?>">
                                                    <?= htmlspecialchars($doc['Observacion']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" action="../../backend/php/archivos/recepcion_procesar.php" class="d-inline">
                                                    <input type="hidden" name="tipo" value="<?= $doc['TipoRegistro'] ?>">
                                                    <?php if ($doc['TipoRegistro'] === 'DOC'): ?>
                                                        <input type="hidden" name="id_movimiento" value="<?= (int)$doc['IdMovimientoDocumento'] ?>">
                                                    <?php else: ?>
                                                        <input type="hidden" name="id_memo" value="<?= (int)$doc['IdMemo'] ?>">
                                                    <?php endif; ?>
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

    <!-- Notificaciones -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $('#tablaRecepcion').DataTable({
            autoWidth: false,
            responsive: true,
            pageLength: 25,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            order: [
                [4, 'desc']
            ],
            columnDefs: [{
                    targets: 0,
                    width: '20%'
                },
                {
                    targets: 2,
                    width: '2%'
                },
                {
                    targets: 3,
                    width: '15%'
                },
                {
                    targets: 6,
                    orderable: false
                }
            ]
        });
    </script>

    <script>
        $(document).ready(function() {
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                $('.nav-dropdown').not($(this).parent()).removeClass('active');
                $(this).parent().toggleClass('active');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });
        });
    </script>
</body>
</html>
