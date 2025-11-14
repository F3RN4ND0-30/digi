<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}
require '../../backend/db/conexion.php';

$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/Mobile|Android|iP(hone|od|ad)/i', $_SERVER['HTTP_USER_AGENT']);
}
$navbarFile = $isMobile ? 'navbar_mobil.php' : 'navbar.php';
$navbarCss  = $isMobile ? 'navbar_mobil.css' : 'navbar.css';

$area_id = $_SESSION['dg_area_id'] ?? null;
if (!$area_id) die('❌ No se pudo determinar el área del usuario.');

/* ---------- Documentos recibidos ---------- */
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
        u.ApellidoPat
    FROM movimientodocumento m
    INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
    INNER JOIN usuarios   u ON d.IdUsuarios   = u.IdUsuarios
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

/* ---------- Memorándums recibidos ---------- */
$sqlMemos = "
    SELECT
        m.IdMemo,
        m.CodigoMemo       AS NumeroDocumento,
        m.Asunto,
        m.TipoMemo,
        m.IdAreaOrigen,
        ao.Nombre          AS AreaOrigenNombre,
        u.Nombres,
        u.ApellidoPat
    FROM memorandums m
    INNER JOIN memorandum_destinos md ON md.IdMemo = m.IdMemo
    INNER JOIN areas ao               ON ao.IdAreas = m.IdAreaOrigen
    INNER JOIN usuarios u             ON u.IdUsuarios = m.IdUsuarioEmisor
    WHERE md.IdAreaDestino = :area
      AND md.Recibido = 1
      AND m.IdEstadoDocumento IN (1,2,3,6)
    ORDER BY m.IdMemo DESC
";
$stmtM = $pdo->prepare($sqlMemos);
$stmtM->execute(['area' => $area_id]);
$memos_recibidos = $stmtM->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Áreas ---------- */
$areas = $pdo->prepare("SELECT IdAreas, Nombre FROM areas WHERE IdAreas != ?");
$areas->execute([$area_id]);
$areas = $areas->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Flash (toast) ---------- */
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

        .numero-memo-wrapper {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-start
        }

        .badge-num {
            font-size: .80rem;
            border-radius: 999px;
            padding: .45rem .75rem
        }

        .badge-memo-tipo {
            font-size: .72rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .25rem .6rem;
            white-space: nowrap;
            letter-spacing: .2px
        }

        .badge-memo-circular {
            background: #0ea5a4;
            color: #fff
        }

        .badge-memo-multiple {
            background: #7c3aed;
            color: #fff
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
    </style>
</head>

<body>
    <div class="layout-escritorio">
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header" style="justify-content:space-between;align-items:center">
                    <h2 class="mb-0"><i class="fas fa-share"></i> Reenviar Documentos</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearInforme" onclick="abrirModalInforme()">
                        <i class="fas fa-file-alt"></i> Crear Informe
                    </button>
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
                                                <td><span class="badge badge-num bg-primary"><?= htmlspecialchars($d['NumeroDocumento']) ?></span></td>
                                                <td>
                                                    <div class="text-truncate" title="<?= htmlspecialchars($d['Asunto']) ?>"><?= htmlspecialchars($d['Asunto']) ?></div>
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
                                                <td class="informe-input area-select" data-id-documento="<?= (int)$d['IdDocumentos'] ?>">
                                                    <select class="select-informes area-pequena sel-informe">
                                                        <option>Cargando...</option>
                                                    </select>
                                                </td>
                                                <td class="accion-btn">
                                                    <!-- Reenviar DOC -->
                                                    <form method="POST" action="../../backend/php/archivos/procesar_reenvio.php" class="form-reenviar">
                                                        <input type="hidden" name="tipo" value="DOC">
                                                        <input type="hidden" name="id_documento" value="<?= (int)$d['IdDocumentos'] ?>">
                                                        <input type="hidden" name="nueva_area" value="">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="id_informe" value="">
                                                        <button type="submit" class="btn btn-success btn-sm btn-reenviar"><i class="fas fa-share"></i> Reenviar</button>
                                                    </form>

                                                    <!-- Finalizar DOC -->
                                                    <form method="POST" action="../../backend/php/archivos/finalizar_documento.php" class="finalizar-form btn-protegido-form">
                                                        <input type="hidden" name="id_documento" value="<?= (int)$d['IdDocumentos'] ?>">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="id_informe" value="">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="nueva_area" value="">
                                                        <button type="submit" class="btn btn-danger btn-sm btn-protegido" style="margin-top:5px;">Finalizar</button>
                                                    </form>

                                                    <!-- Observar DOC -->
                                                    <form method="POST" action="../../backend/php/archivos/observacion_documento.php" class="observacion-form btn-protegido-form">
                                                        <input type="hidden" name="id_documento" value="<?= (int)$d['IdDocumentos'] ?>">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="id_informe" value="">
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
                                                    <div class="numero-memo-wrapper">
                                                        <?php
                                                        $esMultiple = ($m['TipoMemo'] === 'MULTIPLE');
                                                        $label = $esMultiple ? 'MEMORÁNDUM MÚLTIPLE' : 'MEMORÁNDUM CIRCULAR';
                                                        $clase = $esMultiple ? 'badge-memo-multiple' : 'badge-memo-circular';
                                                        ?>
                                                        <span class="badge-memo-tipo <?= $clase ?>"><?= $label ?></span>
                                                        <span class="badge badge-num bg-primary"><?= htmlspecialchars($m['NumeroDocumento']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" title="<?= htmlspecialchars($m['Asunto']) ?>"><?= htmlspecialchars($m['Asunto']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="user-info-mini"><i class="fas fa-user-circle"></i><span><?= htmlspecialchars($m['Nombres'] . ' ' . $m['ApellidoPat']) ?></span></div>
                                                </td>
                                                <td><span class="badge-area"><?= htmlspecialchars($m['AreaOrigenNombre']) ?></span></td>

                                                <td><input type="number" class="form-control form-control-sm memo-folios" min="0" value="0"></td>
                                                <td><input type="text" class="form-control form-control-sm observacion-grande memo-obs" placeholder="Observación opcional..." maxlength="100"></td>

                                                <td class="informe-input area-select" data-id-memo="<?= (int)$m['IdMemo'] ?>">
                                                    <select class="select-informes area-pequena sel-informe">
                                                        <option>Cargando...</option>
                                                    </select>
                                                </td>

                                                <td class="accion-btn">
                                                    <!-- RESPONDER MEMO (envía al área origen, exige N° informe) -->
                                                    <form method="POST" action="../../backend/php/archivos/procesar_reenvio.php" class="form-responder-memo">
                                                        <input type="hidden" name="tipo" value="MEMO">
                                                        <input type="hidden" name="id_memo" value="<?= (int)$m['IdMemo'] ?>">
                                                        <input type="hidden" name="nueva_area" value="<?= (int)$m['IdAreaOrigen'] ?>">
                                                        <input type="hidden" name="numero_folios" value="">
                                                        <input type="hidden" name="observacion" value="">
                                                        <input type="hidden" name="id_informe" value="">
                                                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-reply"></i> Responder</button>
                                                    </form>

                                                    <!-- FINALIZAR MEMO (como DOCS, sin responder) -->
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

    <!-- Modal Crear Informe -->
    <div id="modalCrearInforme" class="modal-overlay" style="display:none;">
        <div class="modal-contenido tarjeta-modal animar-entrada">
            <div class="modal-header tarjeta-header">
                <h3><i class="fas fa-file-alt"></i> Crear Informe</h3>
                <button class="close-modal" onclick="cerrarModalInforme()">&times;</button>
            </div>
            <div class="modal-body tarjeta-body">
                <input type="hidden" id="idDocumentoModal" name="id_documento">
                <input type="hidden" id="idMemoModal" name="id_memo">
                <input type="hidden" id="idAreaModal" value="<?= $_SESSION['dg_area_id'] ?>">
                <div class="form-group">
                    <label for="buscarDocumento">Documento asociado:</label>
                    <input type="text" id="buscarDocumento" placeholder="Escriba número o asunto..." autocomplete="off">
                    <ul id="resultadosDocumento" class="resultados-lista"></ul>
                </div>
                <div class="form-group">
                    <label for="tituloInforme">Nombre del informe:</label>
                    <input type="text" id="tituloInforme" placeholder="Ingrese el título..." autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="nombreFinalInforme">Nombre final:</label>
                    <input type="text" id="nombreFinalInforme" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModalInforme()">Cancelar</button>
                <button class="btn btn-success" onclick="guardarInforme()">Guardar Informe</button>
            </div>
        </div>
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

    <script>
        // Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: t => {
                if (t && t.parentElement) t.parentElement.style.zIndex = '300000';
            }
        });
        <?php if ($flash_text): ?>
            Toast.fire({
                icon: '<?= $flash_type === 'success' ? 'success' : 'error' ?>',
                title: <?= json_encode($flash_text) ?>
            });
        <?php endif; ?>
    </script>

    <script>
        function abrirModalInforme() {
            document.getElementById('modalCrearInforme').style.display = 'flex';
            actualizarCorrelativo();
        }

        function cerrarModalInforme() {
            document.getElementById('modalCrearInforme').style.display = 'none';
            document.getElementById('buscarDocumento').value = '';
            document.getElementById('resultadosDocumento').innerHTML = '';
            document.getElementById('tituloInforme').value = '';
            document.getElementById('nombreFinalInforme').value = '';
            document.getElementById('idDocumentoModal').value = '';
            document.getElementById('idMemoModal').value = '';
        }

        window.addEventListener('click', e => {
            const m = document.getElementById('modalCrearInforme');
            if (e.target === m) cerrarModalInforme();
        });

        let idArea = <?= (int)$_SESSION['dg_area_id'] ?>;

        function actualizarCorrelativo() {
            fetch('../../backend/php/archivos/obtener_correlativo_informe.php?area=' + idArea)
                .then(r => r.json())
                .then(d => {
                    const c = d.correlativo,
                        an = d.año,
                        t = document.getElementById('tituloInforme').value;
                    document.getElementById('nombreFinalInforme').value = `INFORME N°. ${c}-${an}-${t}`;
                });
        }

        const buscarInput = document.getElementById('buscarDocumento');
        const resultadosLista = document.getElementById('resultadosDocumento');

        buscarInput?.addEventListener('input', function() {
            const q = this.value.trim();
            if (q.length < 2) {
                resultadosLista.innerHTML = '';
                return;
            }

            fetch(`../../backend/php/archivos/buscar_documentos.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    resultadosLista.innerHTML = '';

                    data.forEach(doc => {
                        const li = document.createElement('li');
                        li.textContent = `${doc.NumeroDocumento || doc.CodigoMemo} - ${doc.Asunto}`;

                        li.onclick = () => {
                            buscarInput.value = li.textContent;

                            // Si empieza con "MEMO" o "MEMORÁNDUM" → es memo
                            if ((doc.NumeroDocumento && doc.NumeroDocumento.toUpperCase().startsWith('MEMO')) ||
                                (doc.CodigoMemo && doc.CodigoMemo.toUpperCase().startsWith('MEMO'))) {
                                document.getElementById('idMemoModal').value = doc.IdMemo || doc.Id;
                                document.getElementById('idDocumentoModal').value = '';
                            } else {
                                // Documento normal
                                document.getElementById('idDocumentoModal').value = doc.IdDocumentos || doc.Id;
                                document.getElementById('idMemoModal').value = '';
                            }

                            resultadosLista.innerHTML = '';
                        };

                        resultadosLista.appendChild(li);
                    });
                });
        });

        document.getElementById('tituloInforme')?.addEventListener('input', actualizarCorrelativo);

        function guardarInforme() {
            const id_documento = document.getElementById('idDocumentoModal').value.trim();
            const id_memo = document.getElementById('idMemoModal').value.trim();
            const id_area = document.getElementById('idAreaModal').value.trim();
            const titulo = document.getElementById('tituloInforme').value.trim();

            // Log para depuración
            console.log("Validando datos:", {
                id_documento,
                id_memo,
                id_area,
                titulo
            });

            // Verificación de campos necesarios
            if ((!id_documento && !id_memo) || !id_area || !titulo) {
                Toast.fire({
                    icon: 'warning',
                    title: 'Faltan datos para crear el informe'
                });
                return;
            }

            // Datos que se enviarán al backend
            const body = new URLSearchParams();
            if (id_documento) body.append('id_documento', id_documento);
            if (id_memo) body.append('id_memo', id_memo);
            body.append('id_area', id_area);
            body.append('titulo', titulo);

            console.log("Iniciando la solicitud fetch...");
            console.log("id_documento:", id_documento);
            console.log("id_memo:", id_memo);
            console.log("id_area:", id_area);
            console.log("titulo:", titulo);

            // Envío de la solicitud al servidor para crear el informe
            fetch('../../backend/php/archivos/crear_informe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body.toString()
                })
                .then(r => {
                    console.log("Respuesta de la solicitud fetch recibida");
                    return r.json();
                })
                .then(d => {
                    if (d.status === 'success') {
                        console.log("Informe creado con éxito");
                        document.getElementById('nombreFinalInforme').value = d.nombre_final;
                        cerrarModalInforme();
                        location.reload();
                    } else {
                        console.log("Error al crear el informe: ", d.message);
                        Toast.fire({
                            icon: 'error',
                            title: 'Error: ' + d.message
                        });
                    }
                })
                .catch(error => {
                    console.error("Error en la solicitud fetch:", error);
                    Toast.fire({
                        icon: 'error',
                        title: 'Error al crear el informe'
                    });
                });
        }
    </script>

    <!-- Selectize SOLO para DOCS -->
    <script>
        function initInformeSelects(scopeSel) {
            document.querySelectorAll(scopeSel + ' .informe-input').forEach(td => {
                const idDocumento = parseInt(td.dataset.idDocumento || '0', 10);
                const select = td.querySelector('.select-informes');
                if (!select) return;
                if (idDocumento > 0) {
                    select.innerHTML = '<option>Cargando...</option>';
                    fetch(`../../backend/php/archivos/obtener_informes.php?id_documento=${idDocumento}`)
                        .then(res => res.json()).then(data => {
                            let opts = '';
                            if (!Array.isArray(data) || data.length === 0) {
                                opts = '<option value="">No hay informes</option>';
                            } else {
                                opts = '<option value=""></option>' + data.map(inf => `<option value="${inf.IdInforme}">${inf.NombreInforme}</option>`).join('');
                            }
                            select.innerHTML = opts;
                            const $s = $(select);
                            if ($s[0].selectize) $s[0].selectize.destroy();
                            $s.selectize({
                                allowEmptyOption: true,
                                placeholder: 'Seleccione un informe',
                                sortField: 'text',
                                create: false,
                                dropdownParent: 'body',
                                onFocus: function() {
                                    this.removeOption('');
                                    this.refreshOptions(false);
                                }
                            });
                        }).catch(() => {
                            select.innerHTML = '<option value="">No hay informes</option>';
                            $(select).selectize({
                                allowEmptyOption: true
                            });
                        });
                } else {
                    select.innerHTML = '<option value="">No hay informes</option>';
                    const $s = $(select);
                    if ($s[0].selectize) $s[0].selectize.destroy();
                    $s.selectize({
                        allowEmptyOption: true,
                        placeholder: 'No hay informes',
                        sortField: 'text',
                        create: false,
                        dropdownParent: 'body'
                    });
                }
            });
        }
        initInformeSelects('#tab-docs'); // sólo DOCS
    </script>

    <!-- SELECTIZE PARA MEMOS -->
    <script>
        function initInformeMemoSelects(scopeSel) {
            document.querySelectorAll(scopeSel + ' .informe-input').forEach(td => {
                const idMemo = parseInt(td.dataset.idMemo || '0', 10);
                console.log('Memo ID:', idMemo, td);
                const select = td.querySelector('.select-informes');
                if (!select) return;

                if (idMemo > 0) {
                    select.innerHTML = '<option>Cargando...</option>';
                    fetch(`../../backend/php/archivos/obtener_informe_memo.php?id_memo=${idMemo}`)
                        .then(res => res.json())
                        .then(data => {
                            let opts = '';
                            if (!Array.isArray(data) || data.length === 0) {
                                opts = '<option value="">No hay informes</option>';
                            } else {
                                opts = '<option value=""></option>' + data.map(inf => `<option value="${inf.IdInforme}">${inf.NombreInforme}</option>`).join('');
                            }
                            select.innerHTML = opts;

                            const $s = $(select);
                            if ($s[0].selectize) $s[0].selectize.destroy();
                            $s.selectize({
                                allowEmptyOption: true,
                                placeholder: 'Seleccione un informe',
                                sortField: 'text',
                                create: false,
                                dropdownParent: 'body',
                                onFocus: function() {
                                    this.removeOption('');
                                    this.refreshOptions(false);
                                }
                            });
                        })
                        .catch(() => {
                            select.innerHTML = '<option value="">No hay informes</option>';
                            $(select).selectize({
                                allowEmptyOption: true
                            });
                        });
                } else {
                    select.innerHTML = '<option value="">No hay informes</option>';
                    const $s = $(select);
                    if ($s[0].selectize) $s[0].selectize.destroy();
                    $s.selectize({
                        allowEmptyOption: true,
                        placeholder: 'No hay informes',
                        sortField: 'text',
                        create: false,
                        dropdownParent: 'body'
                    });
                }
            });
        }

        // Inicializa los selects solo en la sección de memos
        initInformeMemoSelects('#tab-memos');
    </script>


    <!-- DataTables -->
    <script>
        const LANG_ES = {
            decimal: ",",
            thousands: ".",
            processing: "Procesando...",
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Sin datos disponibles",
            paginate: {
                first: "Primero",
                previous: "Anterior",
                next: "Siguiente",
                last: "Último"
            },
            aria: {
                sortAscending: ": activar para ordenar ascendente",
                sortDescending: ": activar para ordenar descendente"
            }
        };
        let dtDocs, dtMemos;
        $(function() {
            const baseCfg = {
                language: LANG_ES,
                responsive: false,
                autoWidth: true,
                scrollX: true,
                scrollCollapse: true,
                pageLength: 25,
                order: [
                    [0, 'desc']
                ]
            };
            dtDocs = $('#tablaDocs').DataTable(baseCfg);
            dtMemos = $('#tablaMemos').DataTable(baseCfg);
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
            $(window).on('resize', () => {
                dtDocs.columns.adjust();
                dtMemos.columns.adjust();
            });
        });
    </script>

    <!-- Sincroniza valores y envíos -->
    <script>
        function toast(msg, type = 'warning') {
            Toast.fire({
                icon: type,
                title: msg
            });
        }

        $(function() {
            // Selectize DOC áreas
            $('#tab-docs .sel-area').each(function() {
                const $s = $(this),
                    btn = $s.closest('tr').find('.btn-reenviar');
                $s.selectize({
                    allowEmptyOption: true,
                    placeholder: 'Seleccione una opción',
                    sortField: 'text',
                    create: false,
                    dropdownParent: 'body',
                    onFocus: function() {
                        this.removeOption('');
                        this.refreshOptions(false);
                    },
                    onChange: function(v) {
                        if (v) {
                            btn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
                        } else {
                            btn.prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
                        }
                    }
                });
                btn.addClass('btn-secondary').prop('disabled', true);
            });

            // Reenviar DOC
            $(document).on('submit', '.form-reenviar', function(e) {
                e.preventDefault();
                const form = this,
                    $tr = $(form).closest('tr');
                const area = ($tr.find('.sel-area').val() || '').trim();
                const fol = ($tr.find('.inp-folios').val() || '').trim();
                const obs = ($tr.find('.inp-obs').val() || '').trim();
                const inf = ($tr.find('.sel-informe').val() || '').trim();
                if (!area) return toast('Seleccione el área de destino');
                if (!fol || Number(fol) < 1) return toast('Ingrese un número de folios válido');

                form.querySelector('input[name="nueva_area"]').value = area;
                form.querySelector('input[name="numero_folios"]').value = fol;
                form.querySelector('input[name="observacion"]').value = obs;
                form.querySelector('input[name="id_informe"]').value = inf;

                const btn = form.querySelector('button[type="submit"]');
                const old = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                form.submit();
                setTimeout(() => {
                    if (btn.disabled) {
                        btn.disabled = false;
                        btn.innerHTML = old;
                    }
                }, 3000);
            });

            // Responder MEMO (N° Informe OBLIGATORIO)
            $(document).on('submit', '.form-responder-memo', function(e) {
                e.preventDefault();
                const form = this,
                    $tr = $(form).closest('tr');
                const fol = ($tr.find('.memo-folios').val() || '0').trim();
                const obs = ($tr.find('.memo-obs').val() || '').trim();
                const inf = ($tr.find('.select-informes').val() || '').trim(); // <- Aquí estaba el error

                if (!inf) return toast('Ingrese el N° de Informe para responder el memorándum.');

                form.querySelector('input[name="numero_folios"]').value = fol;
                form.querySelector('input[name="observacion"]').value = obs;
                form.querySelector('input[name="id_informe"]').value = inf;

                const btn = form.querySelector('button[type="submit"]');
                const old = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                form.submit();
                setTimeout(() => {
                    if (btn.disabled) {
                        btn.disabled = false;
                        btn.innerHTML = old;
                    }
                }, 3000);
            });


            // Mayúsculas
            document.querySelectorAll('input[type="text"], textarea').forEach(el => {
                el.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        });

        // Modal Password (compartido)
        let formularioActual = null;

        function cerrarModalPassword() {
            document.getElementById('modalPassword').style.display = 'none';
            document.getElementById('passwordInput').value = '';
            document.getElementById('passwordError').style.display = 'none';
        }

        function togglePassword() {
            const i = document.getElementById('passwordInput');
            const ic = document.querySelector('.btn-toggle-pass i');
            if (i.type === 'password') {
                i.type = 'text';
                ic.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                i.type = 'password';
                ic.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function enviarFormularioConPassword() {
            if (!formularioActual) return;
            formularioActual.submit();
        }

        function confirmarPassword() {
            const password = document.getElementById('passwordInput').value.trim();
            const errorDiv = document.getElementById('passwordError');
            if (password === '') {
                errorDiv.innerText = 'Ingrese su contraseña';
                errorDiv.style.display = 'block';
                return;
            }
            fetch('../../backend/php/verificar_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        password
                    })
                })
                .then(r => r.json()).then(d => {
                    if (d.success) {
                        cerrarModalPassword();
                        enviarFormularioConPassword();
                    } else {
                        errorDiv.innerText = 'Contraseña incorrecta';
                        errorDiv.style.display = 'block';
                    }
                })
                .catch(() => {
                    errorDiv.innerText = 'Error de conexión';
                    errorDiv.style.display = 'block';
                });
        }
        document.getElementById('passwordInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmarPassword();
            }
        });
    </script>
</body>

</html>