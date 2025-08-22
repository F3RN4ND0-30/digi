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

// Función para calcular días corridos transcurridos
function calcularDiasHabiles($fechaInicio)
{
    try {
        $inicio = new DateTime($fechaInicio);
        $hoy = new DateTime();

        $inicio->setTime(0, 0, 0);
        $hoy->setTime(0, 0, 0);

        $diasHabiles = 0;

        while ($inicio < $hoy) {
            $diaSemana = $inicio->format('N'); // 1 (Lunes) a 7 (Domingo)
            if ($diaSemana < 6) {
                $diasHabiles++;
            }
            $inicio->modify('+1 day');
        }

        return $diasHabiles;
    } catch (Exception $e) {
        return 0;
    }
}

// CONSULTA COMPLETA - Documentos externos finalizados con área, usuario y observaciones
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
    // Calcular días transcurridos
    $doc['DiasTranscurridos'] = calcularDiasHabiles($doc['FechaIngreso']);

    // Determinar color del semáforo
    if ($doc['DiasTranscurridos'] <= 2) {
        $doc['SemaforoColor'] = 'verde';
        $doc['SemaforoTexto'] = 'En tiempo';
    } elseif ($doc['DiasTranscurridos'] <= 5) {
        $doc['SemaforoColor'] = 'amarillo';
        $doc['SemaforoTexto'] = 'Atención';
    } else {
        $doc['SemaforoColor'] = 'rojo';
        $doc['SemaforoTexto'] = 'Urgente';
    }

    // Estado como texto
    switch ($doc['IdEstadoDocumento']) {
        case 1:
            $doc['EstadoTexto'] = 'PENDIENTE';
            break;
        case 2:
            $doc['EstadoTexto'] = 'EN PROCESO';
            break;
        case 3:
            $doc['EstadoTexto'] = 'REVISADO';
            break;
        case 4:
            $doc['EstadoTexto'] = 'FINALIZADO';
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
$atencion = count(array_filter($documentos, fn($d) => $d['SemaforoColor'] === 'amarillo'));
$urgentes = count(array_filter($documentos, fn($d) => $d['SemaforoColor'] === 'rojo'));
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisión - Documentos Externos - DIGI</title>

    <!-- CSS Framework -->
    <link rel="stylesheet" href="../../backend/css/defensoria/supervision.css">
    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="layout-escritorio">
        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <!-- Header del módulo -->
            <div class="supervision-header">
                <div class="header-content">
                    <div class="header-info">
                        <h1><i class="fas fa-shield-check"></i> Supervisión</h1>
                        <p>Monitoreo de documentos externos - Defensoría del Pueblo</p>
                    </div>
                    <div class="header-legend">
                        <div class="legend-item">
                            <span class="semaforo verde"></span>
                            <span>1-2 días (En tiempo)</span>
                        </div>
                        <div class="legend-item">
                            <span class="semaforo amarillo"></span>
                            <span>3-5 días (Atención)</span>
                        </div>
                        <div class="legend-item">
                            <span class="semaforo rojo"></span>
                            <span>6+ días (Urgente)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card" data-filtro="todos">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
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

            <!-- Tabla de supervisión -->
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_externos > 0): ?>
                                <?php
                                $contador = 1;
                                foreach ($documentos as $doc):
                                ?>
                                    <tr data-semaforo="<?= $doc['SemaforoColor'] ?>" data-id="<?= $doc['IdDocumentos'] ?>" class="fila-documento">
                                        <td class="numero-fila"><?= $contador ?></td>
                                        <td>
                                            <div class="documento-cell">
                                                <strong><?= htmlspecialchars($doc['NumeroDocumento']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="asunto-cell" title="<?= htmlspecialchars($doc['Asunto']) ?>">
                                                <?= htmlspecialchars(substr($doc['Asunto'], 0, 60)) ?>
                                                <?= strlen($doc['Asunto']) > 60 ? '...' : '' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="estado-badge estado-<?= $doc['IdEstadoDocumento'] ?>">
                                                <?= $doc['EstadoTexto'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fecha-cell"><?= date('d/m/Y', strtotime($doc['FechaIngreso'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="area-cell"><?= htmlspecialchars($doc['AreaDestino']) ?></span>
                                        </td>
                                        <td>
                                            <div class="dias-habiles">
                                                <span class="semaforo <?= $doc['SemaforoColor'] ?>" title="<?= $doc['SemaforoTexto'] ?>"></span>
                                                <span class="dias-numero">
                                                    <?= $doc['DiasTranscurridos'] ?>
                                                    <?= $doc['DiasTranscurridos'] == 1 ? 'día' : 'días' ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($doc['UltimaObservacion'])): ?>
                                                <div class="observacion-existente"
                                                    title="<?= htmlspecialchars($doc['UltimaObservacion']) ?>"
                                                    style="cursor: pointer; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                    onclick="verObservacionCompleta('<?= htmlspecialchars(addslashes($doc['UltimaObservacion'])) ?>', '<?= date('d/m/Y', strtotime($doc['FechaUltimaObservacion'])) ?>')">
                                                    <?= htmlspecialchars(substr($doc['UltimaObservacion'], 0, 50)) ?>
                                                    <?= strlen($doc['UltimaObservacion']) > 50 ? '...' : '' ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="sin-observacion" style="color: #999; font-size: 0.8rem;">
                                                    <i class="fas fa-minus-circle"></i> Sin observaciones
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="usuario-cell">
                                                <?= htmlspecialchars(trim($doc['NombreUsuario'] . ' ' . $doc['ApellidoUsuario'])) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    $contador++;
                                endforeach;
                                ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="no-datos">
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

    <!-- JavaScript del módulo -->
    <script src="../../backend/js/defensoria/supervision.js"></script>
    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- JavaScript adicional -->
    <script>
        // Función para ver observación completa
        function verObservacionCompleta(observacion, fecha) {
            Swal.fire({
                title: 'Observación del Documento',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 1rem; color: #666; font-size: 0.9rem;">
                            <i class="fas fa-calendar"></i> Fecha: ${fecha}
                        </p>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #6c5ce7;">
                            <p style="margin: 0; line-height: 1.5;">${observacion}</p>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#6c5ce7',
                width: '500px'
            });
        }

        // Función de exportación FUNCIONAL
        function exportarSupervision() {
            Swal.fire({
                title: 'Exportar Reporte de Supervisión',
                html: `
                    <div style="text-align: left; padding: 1rem;">
                        <p style="margin-bottom: 1rem; color: #666;">Seleccione el formato de exportación:</p>
                        <div style="margin: 1.5rem 0;">
                            <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;">
                                <input type="radio" name="formato" value="excel" checked style="margin-right: 1rem;"> 
                                <i class="fas fa-file-excel" style="color: #10B981; margin-right: 0.5rem; width: 20px;"></i>
                                Excel (.xlsx) - Recomendado
                            </label>
                            <label style="display: flex; align-items: center; margin-bottom: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;">
                                <input type="radio" name="formato" value="pdf" style="margin-right: 1rem;"> 
                                <i class="fas fa-file-pdf" style="color: #EF4444; margin-right: 0.5rem; width: 20px;"></i>
                                PDF - Para impresión
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: background 0.2s;">
                                <input type="radio" name="formato" value="csv" style="margin-right: 1rem;"> 
                                <i class="fas fa-file-csv" style="color: #3B82F6; margin-right: 0.5rem; width: 20px;"></i>
                                CSV - Datos básicos
                            </label>
                        </div>
                        <hr style="margin: 1rem 0; border: none; border-top: 1px solid #e9ecef;">
                        <label style="display: flex; align-items: center; margin-top: 1rem; cursor: pointer;">
                            <input type="checkbox" id="incluirFiltros" checked style="margin-right: 0.75rem;">
                            <span>Incluir solo documentos filtrados actualmente</span>
                        </label>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-download"></i> Generar Reporte',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                confirmButtonColor: '#6c5ce7',
                cancelButtonColor: '#6c757d',
                width: '450px',
                allowOutsideClick: false,
                allowEscapeKey: false,
                preConfirm: () => {
                    const formato = document.querySelector('input[name="formato"]:checked').value;
                    const incluirFiltros = document.getElementById('incluirFiltros').checked;
                    return {
                        formato,
                        incluirFiltros
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    procesarExportacionReal(result.value);
                }
            });
        }

        // Procesamiento REAL de exportación
        function procesarExportacionReal(opciones) {
            let progreso = 0;
            const timer = setInterval(() => {
                progreso += 10;
                Swal.update({
                    html: `
                        <div style="text-align: center; padding: 1rem;">
                            <i class="fas fa-file-${opciones.formato === 'excel' ? 'excel' : opciones.formato === 'pdf' ? 'pdf' : 'csv'}" style="font-size: 3rem; color: #6c5ce7; margin-bottom: 1rem;"></i>
                            <p style="margin-bottom: 1rem;">Generando archivo <strong>${opciones.formato.toUpperCase()}</strong>...</p>
                            <div style="background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 1rem 0; height: 25px;">
                                <div style="background: linear-gradient(90deg, #6c5ce7, #74b9ff); height: 100%; width: ${progreso}%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; font-weight: 600;">
                                    ${progreso}%
                                </div>
                            </div>
                            <p style="color: #666; font-size: 0.9rem;">Recopilando datos de documentos...</p>
                        </div>
                    `
                });

                if (progreso >= 100) {
                    clearInterval(timer);

                    setTimeout(() => {
                        generarArchivoReal(opciones);

                        Swal.fire({
                            icon: 'success',
                            title: 'Archivo generado',
                            html: `El archivo <strong>${opciones.formato.toUpperCase()}</strong> se ha generado correctamente.<br><br><small style="color: #666;">El archivo se está descargando...</small>`,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }, 500);
                }
            }, 200);

            Swal.fire({
                title: 'Procesando exportación...',
                html: `
                    <div style="text-align: center; padding: 1rem;">
                        <i class="fas fa-cog fa-spin" style="font-size: 3rem; color: #6c5ce7; margin-bottom: 1rem;"></i>
                        <p>Preparando datos para exportación...</p>
                    </div>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });
        }

        // Generar archivo REAL
        function generarArchivoReal(opciones) {
            const filas = document.querySelectorAll('.fila-documento:not([style*="display: none"])');
            const datos = [];

            filas.forEach((fila, index) => {
                const celdas = fila.querySelectorAll('td');
                datos.push({
                    numero: index + 1,
                    documento: celdas[1].textContent.trim(),
                    asunto: celdas[2].textContent.trim(),
                    estado: celdas[3].textContent.trim(),
                    fecha: celdas[4].textContent.trim(),
                    area: celdas[5].textContent.trim(),
                    dias: celdas[6].textContent.trim(),
                    observacion: celdas[7].textContent.trim(),
                    recibido: celdas[8].textContent.trim()
                });
            });

            if (opciones.formato === 'csv') {
                descargarCSV(datos);
            } else {
                simularDescargaBackend(opciones.formato, datos);
            }
        }

        // Descargar CSV (funcional en frontend)
        function descargarCSV(datos) {
            const headers = ['N°', 'Documento', 'Asunto', 'Estado', 'Fecha', 'Área', 'Días', 'Observación', 'Recibido'];
            const csvContent = [headers.join(',')];

            datos.forEach(row => {
                const fila = [
                    row.numero,
                    `"${row.documento.replace(/"/g, '""')}"`,
                    `"${row.asunto.replace(/"/g, '""')}"`,
                    `"${row.estado.replace(/"/g, '""')}"`,
                    row.fecha,
                    `"${row.area.replace(/"/g, '""')}"`,
                    `"${row.dias.replace(/"/g, '""')}"`,
                    `"${row.observacion.replace(/"/g, '""')}"`,
                    `"${row.recibido.replace(/"/g, '""')}"`
                ];
                csvContent.push(fila.join(','));
            });

            const blob = new Blob([csvContent.join('\n')], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const fecha = new Date().toISOString().split('T')[0];

            link.href = URL.createObjectURL(blob);
            link.download = `supervision_documentos_${fecha}.csv`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Backend para Excel y PDF
        function simularDescargaBackend(formato, datos) {
            fetch('../../backend/php/exportar-supervision.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        formato: formato,
                        datos: datos
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en el servidor: ' + response.status);
                    }

                    const contentType = response.headers.get('content-type');
                    if (contentType && (contentType.includes('application/') || contentType.includes('text/csv'))) {
                        return response.blob();
                    } else {
                        return response.json();
                    }
                })
                .then(blob => {
                    if (blob instanceof Blob) {
                        const fecha = new Date().toISOString().split('T')[0];
                        const extension = formato === 'excel' ? 'xlsx' : formato === 'pdf' ? 'pdf' : 'csv';
                        const nombreArchivo = `supervision_documentos_${fecha}.${extension}`;

                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = nombreArchivo;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        setTimeout(() => URL.revokeObjectURL(link.href), 100);
                    } else {
                        throw new Error(blob.error || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('Error en exportación:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la exportación',
                        text: 'No se pudo generar el archivo. ' + error.message,
                        confirmButtonColor: '#6c5ce7'
                    });
                });
        }

        // Búsqueda con contador
        document.addEventListener('DOMContentLoaded', function() {
            const campoBusqueda = document.getElementById('buscarDocumento');
            const contador = document.getElementById('contadorResultados');

            if (campoBusqueda && contador) {
                campoBusqueda.addEventListener('input', function() {
                    const busqueda = this.value.toLowerCase().trim();
                    const filas = document.querySelectorAll('.fila-documento');
                    let visible = 0;

                    filas.forEach(fila => {
                        const texto = fila.textContent.toLowerCase();
                        if (texto.includes(busqueda)) {
                            fila.style.display = '';
                            visible++;
                        } else {
                            fila.style.display = 'none';
                        }
                    });

                    contador.textContent = `${visible} documento${visible !== 1 ? 's' : ''}`;
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // 1. Mostrar/Ocultar el menú móvil completo
            window.toggleMobileMenu = function() {
                $('#mobileMenu').slideToggle(200); // Usa slide para transición suave
            };

            // 2. Controlar los dropdowns internos del menú móvil
            $('#mobileMenu .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                const parentDropdown = $(this).closest('.nav-dropdown');
                const dropdownMenu = parentDropdown.find('.dropdown-menu');

                const isOpen = parentDropdown.hasClass('active');

                // Cerrar todos los demás
                $('#mobileMenu .nav-dropdown').not(parentDropdown).removeClass('active')
                    .find('.dropdown-menu').css('max-height', '0');

                // Toggle el actual
                if (isOpen) {
                    parentDropdown.removeClass('active');
                    dropdownMenu.css('max-height', '0');
                } else {
                    parentDropdown.addClass('active');
                    dropdownMenu.css('max-height', dropdownMenu[0].scrollHeight + 'px');
                }
            });

            // 3. (Opcional) Cerrar dropdowns si se hace clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#mobileMenu .nav-dropdown').length &&
                    !$(e.target).closest('.fas.fa-bars').length) {
                    $('#mobileMenu .nav-dropdown').removeClass('active')
                        .find('.dropdown-menu').css('max-height', '0');
                }
            });
        });
    </script>
</body>

</html>