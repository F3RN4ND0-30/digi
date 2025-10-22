<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

// Solo defensoría (rol 4) y administradores (rol 1) pueden acceder
if (($_SESSION['dg_rol'] ?? 999) != 1 && ($_SESSION['dg_rol'] ?? 999) != 4) {
    header('Location: ../sisvis/escritorio.php');
    exit('Acceso denegado: Solo defensoría y administradores');
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

// Función para calcular días hábiles transcurridos
function calcularDiasHabiles($fechaInicio)
{
    try {
        $inicio = new DateTime($fechaInicio);
        $hoy = new DateTime();

        $inicio->setTime(0, 0, 0);
        $hoy->setTime(0, 0, 0);

        $diasHabiles = 0;

        while ($inicio < $hoy) {
            $diaSemana = (int)$inicio->format('N'); // 1 (Lunes) a 7 (Domingo)
            if ($diaSemana < 6) { // L-V
                $diasHabiles++;
            }
            $inicio->modify('+1 day');
        }

        return $diasHabiles;
    } catch (Exception $e) {
        return 0;
    }
}

// CONSULTA COMPLETA - Documentos externos no finalizados
$sql = "
    SELECT 
        d.*,
        COALESCE(a.Nombre, 'Sin área') as AreaDestino,
        COALESCE(u.Nombres, 'Sin') as NombreUsuario,
        COALESCE(u.ApellidoPat, 'usuario') as ApellidoUsuario,
        obs.Observacion as UltimaObservacion,
        obs.FechaMovimiento as FechaUltimaObservacion
    FROM documentos d
    LEFT JOIN areas a ON d.IdAreaFinal = a.IdAreas
    LEFT JOIN usuarios u ON d.IdUsuarios = u.IdUsuarios
    LEFT JOIN (
        SELECT 
            IdDocumentos,
            Observacion,
            FechaMovimiento,
            ROW_NUMBER() OVER (PARTITION BY IdDocumentos ORDER BY FechaMovimiento DESC) as rn
        FROM movimientodocumento 
        WHERE Observacion IS NOT NULL AND Observacion != ''
    ) obs ON d.IdDocumentos = obs.IdDocumentos AND obs.rn = 1
    WHERE d.Exterior = 1 AND d.Finalizado = 0
    ORDER BY d.IdDocumentos DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar los datos directamente
$documentos = [];
foreach ($resultado as $doc) {
    // Calcular días hábiles transcurridos
    $doc['DiasTranscurridos'] = calcularDiasHabiles($doc['FechaIngreso']);

    // Semáforo
    if ($doc['DiasTranscurridos'] <= 3) {
        $doc['SemaforoColor'] = 'verde';
        $doc['SemaforoTexto'] = 'En tiempo';
    } elseif ($doc['DiasTranscurridos'] <= 6) {
        $doc['SemaforoColor'] = 'amarillo';
        $doc['SemaforoTexto'] = 'Atención';
    } else {
        $doc['SemaforoColor'] = 'rojo';
        $doc['SemaforoTexto'] = 'Urgente';
    }

    // Auto–BLOQUEO si pasan 7 días hábiles
    if ($doc['DiasTranscurridos'] >= 7 && (int)$doc['IdEstadoDocumento'] !== 4) {
        // Persistir en BD para mantener consistencia
        $upd = $pdo->prepare("UPDATE documentos SET IdEstadoDocumento = 4 WHERE IdDocumentos = ?");
        $upd->execute([$doc['IdDocumentos']]);
        $doc['IdEstadoDocumento'] = 4;
    }

    // Estado como texto (alineado a tu tabla)
    switch ((int)$doc['IdEstadoDocumento']) {
        case 1:
            $doc['EstadoTexto'] = 'NUEVO';
            break;
        case 2:
            $doc['EstadoTexto'] = 'SEGUIMIENTO';
            break;
        case 3:
            $doc['EstadoTexto'] = 'RECIBIDO';
            break;
        case 4:
            $doc['EstadoTexto'] = 'BLOQUEADO';
            break;
        default:
            $doc['EstadoTexto'] = 'SIN ESTADO';
            break;
    }

    $documentos[] = $doc;
}

// Estadísticas
$total_externos = count($documentos);
$en_tiempo = count(array_filter($documentos, fn($d) => $d['SemaforoColor'] === 'verde'));
$atencion   = count(array_filter($documentos, fn($d) => $d['SemaforoColor'] === 'amarillo'));
$urgentes   = count(array_filter($documentos, fn($d) => $d['SemaforoColor'] === 'rojo'));

// Rol actual
$rolActual = (int)($_SESSION['dg_rol'] ?? 0);
$puedeDesbloquear = in_array($rolActual, [1, 4], true);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisión - Documentos Externos - DIGI</title>

    <link rel="stylesheet" href="../../backend/css/defensoria/supervision.css">
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
    <style>
        /* Estilitos mínimos para el botón y fila bloqueada (puedes moverlos a tu CSS) */
        .btn-desbloquear {
            background: #0984e3;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: .4rem .7rem;
            cursor: pointer
        }

        .btn-desbloquear:hover {
            background: #0770bf
        }

        .doc-bloqueado {
            opacity: .92
        }
    </style>
</head>

<body>
    <div class="layout-escritorio">
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <!-- Header -->
            <div class="supervision-header">
                <div class="header-content">
                    <div class="header-info">
                        <h1><i class="fas fa-shield-check"></i> Supervisión</h1>
                        <p>Monitoreo de documentos externos - Defensoría del Pueblo</p>
                    </div>
                    <div class="header-legend">
                        <div class="legend-item">
                            <span class="semaforo verde"></span>
                            <span>1-3 días (En tiempo)</span>
                        </div>
                        <div class="legend-item">
                            <span class="semaforo amarillo"></span>
                            <span>4-6 días (Atención)</span>
                        </div>
                        <div class="legend-item">
                            <span class="semaforo rojo"></span>
                            <span>7+ días (Urgente)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" data-filtro="todos">
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-content">
                        <h3><?= $total_externos ?></h3>
                        <p>Documentos Externos</p>
                    </div>
                </div>
                <div class="stat-card" data-filtro="verde">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #00b894, #55efc4);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $en_tiempo ?></h3>
                        <p>En Tiempo</p>
                    </div>
                </div>
                <div class="stat-card" data-filtro="amarillo">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fdcb6e, #e17055);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $atencion ?></h3>
                        <p>Requieren Atención</p>
                    </div>
                </div>
                <div class="stat-card" data-filtro="rojo">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e17055, #d63031);">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $urgentes ?></h3>
                        <p>Urgentes</p>
                    </div>
                </div>
            </div>

            <!-- Controles -->
            <div class="tarjeta mb-3">
                <div class="controles-supervision">
                    <div class="controles-busqueda">
                        <div class="search-container" style="position: relative; display: flex; align-items: center; gap: 1rem;">
                            <div style="position: relative; flex: 1;">
                                <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                                <input type="text" id="buscarDocumento" placeholder="Buscar documento, asunto o área..." style="padding-left: 3rem; width: 100%;">
                            </div>
                            <span id="contadorResultados" class="contador-busqueda" style="color: #6c757d; font-size: 0.9rem; white-space: nowrap;">
                                <?= $total_externos ?> documento<?= $total_externos !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>

                    <div class="controles-acciones">
                        <button onclick="exportarSupervision()" class="btn-primary" title="Exportar datos">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="tarjeta">
                <div class="tabla-container">
                    <table class="tabla-supervision" id="tablaSupervision">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>NÚMERO DOCUMENTO</th>
                                <th>ASUNTO</th>
                                <th>ESTADO</th>
                                <th>FECHA</th>
                                <th>ÁREA DESTINO</th>
                                <th>DÍAS TRANSCURRIDOS</th>
                                <th>OBSERVACIÓN</th>
                                <th>RECIBIDO</th>
                                <th>ACCIONES</th> <!-- NUEVO -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_externos > 0): ?>
                                <?php $contador = 1;
                                foreach ($documentos as $doc): ?>
                                    <tr data-semaforo="<?= htmlspecialchars($doc['SemaforoColor']) ?>"
                                        data-id="<?= (int)$doc['IdDocumentos'] ?>"
                                        class="fila-documento <?= ((int)$doc['IdEstadoDocumento'] === 4) ? 'doc-bloqueado' : '' ?>">
                                        <td class="numero-fila"><?= $contador ?></td>
                                        <td>
                                            <div class="documento-cell">
                                                <strong><?= htmlspecialchars($doc['NumeroDocumento']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="asunto-cell" title="<?= htmlspecialchars($doc['Asunto']) ?>">
                                                <?= htmlspecialchars(substr($doc['Asunto'], 0, 60)) ?><?= strlen($doc['Asunto']) > 60 ? '...' : '' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="estado-badge estado-<?= (int)$doc['IdEstadoDocumento'] ?>">
                                                <?= htmlspecialchars($doc['EstadoTexto']) ?>
                                            </span>
                                        </td>
                                        <td><span class="fecha-cell"><?= date('d/m/Y', strtotime($doc['FechaIngreso'])) ?></span></td>
                                        <td><span class="area-cell"><?= htmlspecialchars($doc['AreaDestino']) ?></span></td>
                                        <td>
                                            <div class="dias-habiles">
                                                <span class="semaforo <?= htmlspecialchars($doc['SemaforoColor']) ?>" title="<?= htmlspecialchars($doc['SemaforoTexto']) ?>"></span>
                                                <span class="dias-numero">
                                                    <?= (int)$doc['DiasTranscurridos'] ?> <?= ((int)$doc['DiasTranscurridos'] === 1 ? 'día' : 'días') ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($doc['UltimaObservacion'])): ?>
                                                <div class="observacion-existente"
                                                    title="<?= htmlspecialchars($doc['UltimaObservacion']) ?>"
                                                    style="cursor: pointer; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                    onclick="verObservacionCompleta('<?= htmlspecialchars(addslashes($doc['UltimaObservacion'])) ?>', '<?= date('d/m/Y', strtotime($doc['FechaUltimaObservacion'])) ?>')">
                                                    <?= htmlspecialchars(substr($doc['UltimaObservacion'], 0, 50)) ?><?= strlen($doc['UltimaObservacion']) > 50 ? '...' : '' ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="sin-observacion" style="color: #999; font-size: 0.8rem;">
                                                    <i class="fas fa-minus-circle"></i> Sin observaciones
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="usuario-cell"><?= htmlspecialchars(trim($doc['NombreUsuario'] . ' ' . $doc['ApellidoUsuario'])) ?></div>
                                        </td>
                                        <td>
                                            <?php if ((int)$doc['IdEstadoDocumento'] === 4 && $puedeDesbloquear): ?>
                                                <button class="btn-desbloquear" onclick="desbloquearDocumento(<?= (int)$doc['IdDocumentos'] ?>)">
                                                    <i class="fas fa-lock-open"></i> Desbloquear
                                                </button>
                                            <?php else: ?>
                                                <span style="color:#999; font-size:.85rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php $contador++;
                                endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="no-datos">
                                        <div class="mensaje-vacio">
                                            <i class="fas fa-inbox"></i>
                                            <p>No hay documentos externos</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="../../backend/js/notificaciones.js"></script>
    <script src="../../backend/js/defensoria/supervision.js"></script>
    <script src="../../backend/js/defensoria/exportar-supervision.js"></script>

    <script>
        // Menú móvil (tal cual lo tenías)
        $(document).ready(function() {
            window.toggleMobileMenu = function() {
                $('#mobileMenu').slideToggle(200);
            };
            $('#mobileMenu .dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                const parentDropdown = $(this).closest('.nav-dropdown');
                const dropdownMenu = parentDropdown.find('.dropdown-menu');
                const isOpen = parentDropdown.hasClass('active');
                $('#mobileMenu .nav-dropdown').not(parentDropdown).removeClass('active')
                    .find('.dropdown-menu').css('max-height', '0');
                if (isOpen) {
                    parentDropdown.removeClass('active');
                    dropdownMenu.css('max-height', '0');
                } else {
                    parentDropdown.addClass('active');
                    dropdownMenu.css('max-height', dropdownMenu[0].scrollHeight + 'px');
                }
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#mobileMenu .nav-dropdown').length &&
                    !$(e.target).closest('.fas.fa-bars').length) {
                    $('#mobileMenu .nav-dropdown').removeClass('active')
                        .find('.dropdown-menu').css('max-height', '0');
                }
            });
        });

        // Desbloquear (puedes moverlo a tu supervision.js)
        function desbloquearDocumento(idDoc) {
            Swal.fire({
                title: '¿Desbloquear documento?',
                text: 'El estado pasará a SEGUIMIENTO.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, desbloquear',
                cancelButtonText: 'Cancelar'
            }).then((r) => {
                if (!r.isConfirmed) return;

                const fd = new FormData();
                fd.append('accion', 'desbloquear');
                fd.append('id', idDoc);

                fetch('../../backend/php/desbloquear_documento.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Ok', data.message || 'Documento desbloqueado', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo desbloquear', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Falla de conexión con el servidor', 'error'));
            });
        }
    </script>
</body>

</html>