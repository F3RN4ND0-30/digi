<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}
require '../../backend/db/conexion.php';

// Detectar móvil -> navbar/css
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/Mobile|Android|iP(hone|od|ad)/i', $_SERVER['HTTP_USER_AGENT']);
}
$navbarFile = $isMobile ? 'navbar_mobil.php' : 'navbar.php';
$navbarCss  = $isMobile ? 'navbar_mobil.css' : 'navbar.css';

$area_id = $_SESSION['dg_area_id'] ?? null;
if (!$area_id) die('❌ No se pudo determinar el área del usuario.');

/* -------------------- DOCS recibidos -------------------- */
$sqlDocs = "
    SELECT 
        m.IdMovimientoDocumento,
        m.IdDocumentos,
        d.NumeroDocumento,
        d.NumeroFolios,
        d.Asunto,
        d.IdUsuarios,
        d.IdAreaFinal,
        u.Nombres,
        u.ApellidoPat,

        -- 🔥 INFORME
        i.IdInforme,
        i.NombreInforme AS NombreInforme

    FROM movimientodocumento m
    INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
    INNER JOIN usuarios   u ON d.IdUsuarios   = u.IdUsuarios

    -- 🔥 TRAE SOLO EL ÚLTIMO INFORME DEL DOCUMENTO
    LEFT JOIN informes i 
        ON i.IdDocumento = d.IdDocumentos
        AND i.IdInforme = (
            SELECT MAX(i2.IdInforme)
            FROM informes i2
            WHERE i2.IdDocumento = d.IdDocumentos
        )

    WHERE m.AreaDestino = ?
      AND m.Recibido = 1
      AND d.Finalizado = 0
      AND d.IdEstadoDocumento IN (1,2,3,6)
      AND m.IdMovimientoDocumento = (
          SELECT MAX(m3.IdMovimientoDocumento)
          FROM movimientodocumento m3
          WHERE m3.IdDocumentos = d.IdDocumentos
      )

    ORDER BY m.IdMovimientoDocumento DESC
";
$stmt = $pdo->prepare($sqlDocs);
$stmt->execute([$area_id]);
$documentos_recibidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------- MEMOS recibidos -------------------- */
$sqlMemos = "
    SELECT
        m.IdMemo,
        m.CodigoMemo       AS NumeroDocumento,
        m.Asunto,
        m.TipoMemo,
        m.NumeroFolios     AS NumeroFolios,
        m.IdAreaOrigen,
        ao.Nombre          AS AreaOrigenNombre,
        u.Nombres,
        u.ApellidoPat,

        -- 🔥 INFORME DEL MEMO
        i.IdInforme,
        i.NombreInforme AS NombreInforme

    FROM memorandums m
    INNER JOIN memorandum_destinos md ON md.IdMemo = m.IdMemo
    INNER JOIN areas ao               ON ao.IdAreas = m.IdAreaOrigen
    INNER JOIN usuarios u             ON u.IdUsuarios = m.IdUsuarioEmisor

    -- 🔥 ÚLTIMO INFORME DEL MEMO
    LEFT JOIN informes i
        ON i.IdMemo = m.IdMemo
        AND i.IdInforme = (
            SELECT MAX(i2.IdInforme)
            FROM informes i2
            WHERE i2.IdMemo = m.IdMemo
        )

    WHERE md.IdAreaDestino = :area
      AND md.Recibido = 1
      AND m.IdEstadoDocumento IN (1,2,3,6)

    ORDER BY m.IdMemo DESC
";
$stmtM = $pdo->prepare($sqlMemos);
$stmtM->execute(['area' => $area_id]);
$memos_recibidos = $stmtM->fetchAll(PDO::FETCH_ASSOC);

/* -------------------- Áreas -------------------- */
$areas = $pdo->prepare("SELECT IdAreas, Nombre FROM areas WHERE IdAreas != ?");
$areas->execute([$area_id]);
$areas = $areas->fetchAll(PDO::FETCH_ASSOC);

/* -------------------- Flash -------------------- */
$flash_type = $_SESSION['flash_type'] ?? null;
$flash_text = $_SESSION['flash_text'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_text']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenviar Documentos - DIGI MPP</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <link rel="stylesheet" href="../../backend/css/archivos/reenviados.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.15/dist/sweetalert2.min.css">

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.15/dist/sweetalert2.min.js"></script>
    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .tabs-enviados {
            display: flex;
            gap: .25rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0
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
            transition: .2s
        }

        .tab-btn:hover {
            background: #e2e8f0
        }

        .tab-btn.active {
            background: #6c5ce7;
            color: #fff;
            border-color: #6c5ce7;
            box-shadow: 0 2px 8px rgba(108, 92, 231, .35)
        }

        .tab-content {
            display: none
        }

        .tab-content.active {
            display: block
        }

        .cell-clip {
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .badge-area {
            background: #e0f2fe;
            color: #0c4a6e;
            border-radius: 999px;
            padding: .25rem .6rem;
            font-size: .78rem;
            display: inline-block
        }

        .table-responsive {
            overflow-x: auto
        }

        .dataTables_wrapper,
        .dataTables_wrapper .dataTables_scroll,
        table.dataTable {
            width: 100% !important
        }

        td input.form-control {
            max-width: 260px
        }

        td.area-select select {
            min-width: 220px
        }

        @media (max-width:1200px) {
            td input.form-control {
                max-width: 200px
            }

            td.area-select select {
                min-width: 180px
            }
        }

        .input-mini {
            max-width: 180px
        }

        /* Pill que envuelve todo el código de memo, permite salto de línea sin desbordar */
        .badge-num-wrap {
            display: inline-block;
            max-width: 240px;
            white-space: normal;
            line-height: 1.15;
            border-radius: 999px;
            padding: .45rem .75rem;
            font-size: .80rem;
        }

        /* ya existía para DOCS */
        .badge-num {
            font-size: .80rem;
            border-radius: 999px;
            padding: .45rem .75rem
        }
    </style>
</head>

<body>
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
                <div class="tarjeta-header" style="justify-content:space-between;align-items:center">
                    <h2 class="mb-0"><i class="fas fa-share"></i> Reenviar Documentos</h2>
                    <span id="correlativoLabel" data-area="<?= $_SESSION['dg_area_id'] ?>" class="badge bg-purple"
                        style="font-size:1rem;padding:.6rem .9rem;background:#6c5ce7;color:white;">
                        Siguiente Informe: CARGANDO...
                    </span>
                </div>

                <div class="tarjeta-body">
                    <div class="tabs-enviados">
                        <button class="tab-btn active" data-target="#tab-docs"><i class="fas fa-file-alt"></i> Documentos</button>
                        <button class="tab-btn" data-target="#tab-memos"><i class="fas fa-file-signature"></i> Memorándums</button>
                    </div>

                    <!-- DOCS -->
                    <div id="tab-docs" class="tab-content active">
                        <?php if (empty($documentos_recibidos)): ?>
                            <div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay documentos recibidos.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="tablaDocs" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th># Número</th>
                                            <th>Asunto</th>
                                            <th>Remitente</th>
                                            <th>Reenviar a</th>
                                            <th>N° de Folios</th>
                                            <th>Observación</th>
                                            <th>Informe</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documentos_recibidos as $d): ?>
                                            <tr data-id-doc="<?= (int)$d['IdDocumentos'] ?>">
                                                <td data-id-documento="<?= htmlspecialchars($d['IdDocumentos']) ?>">
                                                    <span class="badge badge-num bg-primary">
                                                        <?= htmlspecialchars($d['NumeroDocumento']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="cell-clip" title="<?= htmlspecialchars($d['Asunto']) ?>"><?= htmlspecialchars($d['Asunto']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="user-info-mini"><i class="fas fa-user-circle"></i><span><?= htmlspecialchars($d['Nombres'] . ' ' . $d['ApellidoPat']) ?></span></div>
                                                </td>
                                                <td class="area-select">
                                                    <select class="area-pequena sel-area">
                                                        <option value="">Seleccione área</option>
                                                        <?php foreach ($areas as $a): ?>
                                                            <option value="<?= $a['IdAreas'] ?>"><?= htmlspecialchars($a['Nombre']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" class="form-control form-control-sm inp-folios" min="<?= (int)$d['NumeroFolios'] ?>" value="<?= (int)$d['NumeroFolios'] ?>"></td>
                                                <td><input type="text" class="form-control form-control-sm observacion-grande inp-obs" placeholder="Observación opcional..." maxlength="100"></td>
                                                <td class="informe-input"
                                                    data-id-documento="<?= (int)$d['IdDocumentos'] ?>"
                                                    data-informe="<?= $d['IdInforme'] ?? '' ?>">

                                                    <!-- Botón para crear informe -->
                                                    <button class="btn btn-outline-primary btn-sm btn-crear-informe">
                                                        <i class="fas fa-plus"></i> Crear Informe
                                                    </button>

                                                    <!-- Espacio donde se mostrará el nombre del informe después de crearlo -->
                                                    <div class="informes-previos"></div>

                                                </td>

                                                <td class="accion-btn">
                                                    <form method="POST" action="../../backend/php/archivos/procesar_reenvio.php" class="form-reenviar">
                                                        <input type="hidden" name="tipo" value="DOC">
                                                        <input type="hidden" name="id_documento" value="<?= (int)$d['IdDocumentos'] ?>">
                                                        <input type="hidden" name="nueva_area" value="">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="id_informe" value="<?= $d['IdInforme'] ?? '' ?>">
                                                        <button type="submit" class="btn btn-success btn-sm btn-reenviar"><i class="fas fa-share"></i> Reenviar</button>
                                                    </form>
                                                    <form method="POST" action="../../backend/php/archivos/finalizar_documento.php" class="finalizar-form btn-protegido-form">
                                                        <input type="hidden" name="id_documento" value="<?= (int)$d['IdDocumentos'] ?>">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="id_informe" value="<?= $d['IdInforme'] ?? '' ?>">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="nueva_area" value="">
                                                        <button type="submit" class="btn btn-danger btn-sm btn-protegido" style="margin-top:5px;">Finalizar</button>
                                                    </form>
                                                    <form method="POST" action="../../backend/php/archivos/observacion_documento.php" class="observacion-form btn-protegido-form">
                                                        <input type="hidden" name="id_documento" value="<?= (int)$d['IdDocumentos'] ?>">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="id_informe" value="<?= $d['IdInforme'] ?? '' ?>">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="nueva_area" value="">
                                                        <button type="submit" class="btn btn-observacion btn-sm btn-protegido" style="margin-top:5px;">Observar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- MEMOS -->
                    <div id="tab-memos" class="tab-content">
                        <?php if (empty($memos_recibidos)): ?>
                            <div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay memorándums recibidos.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="tablaMemos" class="table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th># / Tipo</th>
                                            <th>Asunto</th>
                                            <th>Remitente</th>
                                            <th>Responder a</th>
                                            <th>N° de Folios</th>
                                            <th>Observación</th>
                                            <th>N° Informe</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($memos_recibidos as $m): ?>
                                            <tr data-id-memo="<?= (int)$m['IdMemo'] ?>" data-area-origen="<?= (int)$m['IdAreaOrigen'] ?>">
                                                <td>
                                                    <span class="badge-num-wrap bg-primary" title="<?= htmlspecialchars($m['NumeroDocumento']) ?>">
                                                        <?= htmlspecialchars($m['NumeroDocumento']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="cell-clip" title="<?= htmlspecialchars($m['Asunto']) ?>"><?= htmlspecialchars($m['Asunto']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="user-info-mini"><i class="fas fa-user-circle"></i><span><?= htmlspecialchars($m['Nombres'] . ' ' . $m['ApellidoPat']) ?></span></div>
                                                </td>
                                                <td><span class="badge-area"><?= htmlspecialchars($m['AreaOrigenNombre']) ?></span></td>
                                                <td><input type="number" class="form-control form-control-sm memo-folios" min="<?= (int)$m['NumeroFolios'] ?>" value="<?= (int)$m['NumeroFolios'] ?>"></td>
                                                <td><input type="text" class="form-control form-control-sm observacion-grande memo-obs" placeholder="Observación opcional..." maxlength="100"></td>

                                                <td class="informe-input"
                                                    data-id-memo="<?= (int)$m['IdMemo'] ?>"
                                                    data-informe="<?= $m['IdInforme'] ?? '' ?>">

                                                    <!-- Botón para crear informe -->
                                                    <button class="btn btn-outline-primary btn-sm btn-crear-informe">
                                                        <i class="fas fa-plus"></i> Crear Informe
                                                    </button>

                                                    <!-- Espacio donde se mostrará el nombre del informe después de crearlo -->
                                                    <div class="informes-previos"></div>

                                                </td>

                                                <td class="accion-btn">
                                                    <form method="POST" action="../../backend/php/archivos/procesar_reenvio.php" class="form-responder-memo">
                                                        <input type="hidden" name="tipo" value="MEMO">
                                                        <input type="hidden" name="id_memo" value="<?= (int)$m['IdMemo'] ?>">
                                                        <input type="hidden" name="nueva_area" value="<?= (int)$m['IdAreaOrigen'] ?>">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="id_informe" value="">
                                                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-reply"></i> Responder</button>
                                                    </form>
                                                    <form method="POST" action="../../backend/php/archivos/finalizar_memo.php" class="finalizar-memo-form btn-protegido-form">
                                                        <input type="hidden" name="id_memo" value="<?= (int)$m['IdMemo'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm btn-protegido" style="margin-top:5px;"><i class="fas fa-check"></i> Finalizar</button>
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

    <!-- Modal Password -->
    <div id="modalPassword" class="modal-overlay" style="display:none;">
        <div class="modal-contenido tarjeta-modal animar-entrada">
            <div class="modal-header tarjeta-header">
                <h3><i class="fas fa-lock"></i> Confirmar acción</h3>
                <button class="close-modal" onclick="cerrarModalPassword()">&times;</button>
            </div>
            <div class="modal-body tarjeta-body">
                <p>Ingrese su contraseña para continuar:</p>
                <div class="password-wrapper">
                    <input type="password" id="passwordInput" placeholder="Contraseña" autocomplete="off">
                    <button type="button" class="btn-toggle-pass" onclick="togglePassword()"><i class="fas fa-eye"></i></button>
                </div>
                <div id="passwordError" style="color:red;display:none;margin-top:5px;">Contraseña incorrecta.</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModalPassword()">Cancelar</button>
                <button class="btn btn-success" onclick="confirmarPassword()">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>

    <script src="../../backend/js/notificaciones.js"></script>

    <!-- SCRIPTS NECESARIOS -->

    <script src="../../backend/js/reenvio/informes.js"></script>
    <script src="../../backend/js/reenvio/formularios.js"></script>
    <script src="../../backend/js/reenvio/password.js"></script>
    <script src="../../backend/js/reenvio/tablas.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            document.querySelectorAll(".inp-folios").forEach(input => {

                input.addEventListener("input", function() {
                    let min = parseInt(this.min);
                    let val = parseInt(this.value);

                    // Si el valor es menor al mínimo, lo corregimos automáticamente
                    if (val < min || isNaN(val)) {
                        this.value = min;
                    }
                });

            });

        });
    </script>

</body>

</html>