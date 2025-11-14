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
if (!$area_id) die("❌ No se pudo determinar el área del usuario.");

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
   2) MEMORÁNDUMS pendientes para mi área (solo los no recibidos)
   ========================================================== */
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
          AND md.Recibido = 0     
          AND m.IdEstadoDocumento = 1";
$stmt2 = $pdo->prepare($sqlMemos);
$stmt2->execute(['area' => $area_id]);
$memos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
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
    <!-- Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <!-- CSS Principal -->
    <link rel="stylesheet" href="../../backend/css/archivos/recepcion.css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />

    <style>
        .tabs-recep {
            display: flex;
            gap: .25rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab-btn {
            border: none;
            background: #f1f5f9;
            padding: .7rem 1.5rem;
            font-size: .9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            cursor: pointer;
            color: #475569;
            border-radius: 6px 6px 0 0;
            border: 1px solid transparent;
            border-bottom: none;
            transition: .2s;
        }

        .tab-btn:hover {
            background: #e2e8f0;
        }

        .tab-btn.active {
            background: #6c5ce7;
            color: #fff;
            border-color: #6c5ce7;
            box-shadow: 0 2px 8px rgba(108, 92, 231, .35);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .numero-memo-wrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
            max-width: 160px;
        }

        .badge-memo-tipo {
            font-size: .70rem;
            font-weight: 600;
            border-radius: 999px;
            white-space: nowrap;
        }

        .badge-numero {
            font-size: .78rem;
            white-space: nowrap;
        }

        /* Tablas ocupan 100% */
        .table-responsive {
            overflow-x: auto;
        }

        .dataTables_wrapper,
        .dataTables_wrapper .dataTables_scroll,
        table.dataTable {
            width: 100% !important;
        }
    </style>
</head>

<body>
    <div class="layout-escritorio">
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header" style="justify-content:space-between;align-items:center">
                    <h2 class="mb-0"><i class="fas fa-inbox"></i> Documentos para Recepción</h2>
                </div>

                <div class="tarjeta-body">
                    <div class="tabs-recep">
                        <button class="tab-btn active" data-target="#tab-docs"><i class="fas fa-file-alt"></i> Documentos</button>
                        <button class="tab-btn" data-target="#tab-memos"><i class="fas fa-file-signature"></i> Memorándums</button>
                    </div>

                    <!-- TAB DOCUMENTOS -->
                    <div id="tab-docs" class="tab-content active">
                        <?php if (empty($docs)): ?>
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="fas fa-info-circle me-3"></i>
                                <div><strong>Sin documentos pendientes</strong><br>No hay documentos por recepcionar en tu área.</div>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="tablaRecepcionDocs" class="table table-striped" style="width:100%">
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
                                    <tbody>
                                        <?php foreach ($docs as $doc): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary badge-numero">
                                                        <?= htmlspecialchars($doc['NumeroDocumento']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($doc['Asunto']) ?>">
                                                        <?= htmlspecialchars($doc['Asunto']) ?>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($doc['Estado']) ?></span></td>
                                                <td><i class="fas fa-building me-1"></i><?= htmlspecialchars($doc['AreaOrigen']) ?></td>
                                                <td><small class="text-muted"><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($doc['FechaMovimiento'])) ?></small></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($doc['NumeroFolios']) ?></span></td>
                                                <td><span class="badge bg-info"><?= htmlspecialchars($doc['TipoObjeto']) ?></span></td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($doc['Observacion']) ?>">
                                                        <?= htmlspecialchars($doc['Observacion']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <form method="POST" action="../../backend/php/archivos/recepcion_procesar.php" class="d-inline">
                                                        <input type="hidden" name="tipo" value="DOC">
                                                        <input type="hidden" name="id_movimiento" value="<?= (int)$doc['IdMovimientoDocumento'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Confirmar recepción del documento?')">
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

                    <!-- TAB MEMORÁNDUMS -->
                    <div id="tab-memos" class="tab-content">
                        <?php if (empty($memos)): ?>
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="fas fa-info-circle me-3"></i>
                                <div><strong>Sin memorándums pendientes</strong><br>No hay memorándums por recepcionar en tu área.</div>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="tablaRecepcionMemos" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th># / Tipo</th>
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
                                    <tbody>
                                        <?php foreach ($memos as $doc): ?>
                                            <?php
                                            $labelTipoMemo = '';
                                            if (!empty($doc['TipoMemo'])) {
                                                if ($doc['TipoMemo'] === 'MULTIPLE') $labelTipoMemo = 'MEMO MÚLTIPLE';
                                                elseif ($doc['TipoMemo'] === 'CIRCULAR') $labelTipoMemo = 'MEMO CIRCULAR';
                                                else $labelTipoMemo = 'MEMO';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="numero-memo-wrapper">
                                                        <?php if ($labelTipoMemo): ?>
                                                            <span class="badge bg-success badge-memo-tipo"><?= htmlspecialchars($labelTipoMemo) ?></span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-primary badge-numero"><?= htmlspecialchars($doc['NumeroDocumento']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($doc['Asunto']) ?>">
                                                        <?= htmlspecialchars($doc['Asunto']) ?>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($doc['Estado']) ?></span></td>
                                                <td><i class="fas fa-building me-1"></i><?= htmlspecialchars($doc['AreaOrigen']) ?></td>
                                                <td><small class="text-muted"><i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($doc['FechaMovimiento'])) ?></small></td>
                                                <td><span class="badge bg-secondary">-</span></td>
                                                <td><span class="badge bg-info"><?= htmlspecialchars($doc['TipoObjeto']) ?></span></td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 180px;">-</div>
                                                </td>
                                                <td>
                                                    <form method="POST" action="../../backend/php/archivos/recepcion_procesar.php" class="d-inline">
                                                        <input type="hidden" name="tipo" value="MEMO">
                                                        <input type="hidden" name="id_memo" value="<?= (int)$doc['IdMemo'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Confirmar recepción del memorándum?')">
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
            </div>
        </main>
    </div>

    <script src="../../backend/js/notificaciones.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // DataTables separados por tab
        const LANG_ES = {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        };

        let dtDocs, dtMemos;
        $(function() {
            dtDocs = $('#tablaRecepcionDocs').DataTable({
                autoWidth: false,
                responsive: true,
                pageLength: 25,
                language: LANG_ES,
                order: [
                    [4, 'desc']
                ],
                columnDefs: [{
                        targets: 0,
                        width: '18%'
                    },
                    {
                        targets: 2,
                        width: '2%'
                    },
                    {
                        targets: 3,
                        width: '16%'
                    },
                    {
                        targets: 6,
                        orderable: false
                    }
                ]
            });

            dtMemos = $('#tablaRecepcionMemos').DataTable({
                autoWidth: false,
                responsive: true,
                pageLength: 25,
                language: LANG_ES,
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
                        width: '16%'
                    },
                    {
                        targets: 6,
                        orderable: false
                    }
                ]
            });

            // Tabs
            $('.tab-btn').on('click', function() {
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                const t = $(this).data('target');
                $('.tab-content').removeClass('active');
                $(t).addClass('active');
                setTimeout(() => {
                    (t === '#tab-docs' ? dtDocs : dtMemos).columns.adjust();
                }, 60);
            });
        });
    </script>

    <script>
        // Navbar móvil
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