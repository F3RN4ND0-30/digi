<?php
session_start();
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

$sql = "
SELECT m.IdMovimientoDocumento, m.IdDocumentos, d.NumeroDocumento, d.NumeroFolios, d.Asunto, d.IdUsuarios, d.IdAreaFinal,
               u.Nombres, u.ApellidoPat
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN usuarios u ON d.IdUsuarios = u.IdUsuarios
        WHERE m.AreaDestino = ?  -- El área que recibe el documento
          AND m.Recibido = 1  -- Asegurarnos de que el documento ha sido recibido
          AND d.Finalizado = 0  -- El documento no ha sido finalizado
          AND d.IdEstadoDocumento != 5  -- Excluir los documentos reenviados
          AND d.IdEstadoDocumento IN (1, 2, 3, 6)  -- Solo los documentos en estado NUEVO, SEGUIMIENTO o RECIBIDO
          
          -- Excluir el último emisor del documento
          AND NOT EXISTS (
              SELECT 1
              FROM movimientodocumento m2
              WHERE m2.IdDocumentos = d.IdDocumentos
              AND m2.IdMovimientoDocumento = (
                  SELECT MAX(IdMovimientoDocumento) 
                  FROM movimientodocumento 
                  WHERE IdDocumentos = d.IdDocumentos
              )
              AND m2.AreaOrigen = ?
          )
          
          -- Seleccionamos solo el último movimiento para evitar duplicados
          AND m.IdMovimientoDocumento = (
              SELECT MAX(IdMovimientoDocumento)
              FROM movimientodocumento m3
              WHERE m3.IdDocumentos = d.IdDocumentos
          )
          
        ORDER BY m.IdMovimientoDocumento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id, $area_id]);  // Usamos el mismo $area_id para ambas subconsultas
$documentos_recibidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$areas = $pdo->prepare("SELECT IdAreas, Nombre FROM areas WHERE IdAreas != ?");
$areas->execute([$area_id]);
$areas = $areas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenvío de Documentos - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- CSS de Reenvío -->
    <link rel="stylesheet" href="../../backend/css/archivos/reenviados.css" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">

    <!-- Selectize CSS -->
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <!-- Selectize JS -->
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />

</head>

<body>
    <div class="layout-escritorio">

        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2 class="mb-0"><i class="fas fa-share"></i> Reenviar Documentos</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearInforme" onclick="abrirModalInforme()">
                        <i class="fas fa-file-alt"></i> Crear Informe
                    </button>
                </div>

                <div class="tarjeta-body">
                    <?php if (empty($documentos_recibidos)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span>No hay documentos recibidos para reenviar en este momento.</span>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="tablaReenvio" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag"></i> Número</th>
                                        <th><i class="fas fa-align-left"></i> Asunto</th>
                                        <th><i class="fas fa-user"></i> Remitente</th>
                                        <th><i class="fas fa-building"></i> Reenviar a</th>
                                        <th><i class="fas fa-copy"></i> N° de Folios</th>
                                        <th><i class="fas fa-comment-dots"></i> Observación</th>
                                        <th><i class="fas fa-file-alt"></i> Informe</th>
                                        <th><i class="fas fa-cogs"></i> Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documentos_recibidos as $doc): ?>
                                        <tr>
                                            <form method="POST" action="../../backend/php/archivos/procesar_reenvio.php" class="reenvio-form">
                                                <td class="numero-doc">
                                                    <span class="badge bg-primary">
                                                        <?= htmlspecialchars($doc['NumeroDocumento']) ?>
                                                    </span>
                                                </td>
                                                <td class="asunto-doc">
                                                    <div class="text-truncate" title="<?= htmlspecialchars($doc['Asunto']) ?>">
                                                        <?= htmlspecialchars($doc['Asunto']) ?>
                                                    </div>
                                                </td>
                                                <td class="remitente-doc">
                                                    <div class="user-info-mini">
                                                        <i class="fas fa-user-circle"></i>
                                                        <span><?= htmlspecialchars($doc['Nombres'] . ' ' . $doc['ApellidoPat']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="area-select">
                                                    <select name="nueva_area" required class="area-pequena">
                                                        <option value="">Seleccione área</option>
                                                        <?php foreach ($areas as $a): ?>
                                                            <option value="<?= $a['IdAreas'] ?>"><?= htmlspecialchars($a['Nombre']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="folios-doc">
                                                    <input
                                                        type="number"
                                                        name="numero_folios"
                                                        min="<?= $doc['NumeroFolios'] ?>"
                                                        value="<?= $doc['NumeroFolios'] ?>"
                                                        class="form-control form-control-sm"
                                                        required>
                                                </td>
                                                <td class="observacion-input">
                                                    <input type="text"
                                                        name="observacion"
                                                        class="form-control form-control-sm observacion-grande"
                                                        placeholder="Observación opcional..."
                                                        maxlength="100">
                                                </td>
                                                <td class="informe-input area-select" data-id-documento="<?= $doc['IdDocumentos'] ?>">
                                                    <select class="select-informes area-pequena" name="id_informe">
                                                        <option>Cargando...</option>
                                                    </select>
                                                </td>
                                                <td class="accion-btn">
                                                    <form method="POST" action="procesar_reenvio.php">
                                                        <input type="hidden" name="id_documento" value="<?= $doc['IdDocumentos'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm">Reenviar</button>
                                                    </form>

                                                    <form method="POST" action="../../backend/php/archivos/finalizar_documento.php"
                                                        onsubmit="return confirm('¿Seguro que quieres finalizar el documento? Si lo finalizas ya nadie lo podrá reenviar.')">
                                                        <input type="hidden" name="id_documento" value="<?= $doc['IdDocumentos'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Finalizar</button>
                                                    </form>
                                                </td>
                                            </form>
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

    <!-- Modal Crear Informe -->
    <div id="modalCrearInforme" class="modal-overlay" style="display: none;">
        <div class="modal-contenido tarjeta-modal animar-entrada">
            <div class="modal-header tarjeta-header">
                <h3><i class="fas fa-file-alt"></i> Crear Informe</h3>
                <button class="close-modal" onclick="cerrarModalInforme()">&times;</button>
            </div>

            <div class="modal-body tarjeta-body">
                <input type="hidden" id="idDocumentoModal" name="id_documento">
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



    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // Abrir el modal
        function abrirModalInforme() {
            document.getElementById('modalCrearInforme').style.display = 'flex';
        }

        // Cerrar el modal
        function cerrarModalInforme() {
            document.getElementById('modalCrearInforme').style.display = 'none';
        }

        // Cerrar al hacer clic fuera del contenido
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('modalCrearInforme');
            if (e.target === modal) {
                cerrarModalInforme();
            }
        });
    </script>
    <script>
        document.querySelectorAll('.informe-input').forEach(td => {
            const idDocumento = td.dataset.idDocumento;
            const select = td.querySelector('.select-informes');
            if (!idDocumento || !select) return;

            // Mostrar estado de carga
            select.innerHTML = '<option>Cargando...</option>';

            // Cargar informes por documento
            fetch(`../../backend/php/archivos/obtener_informes.php?id_documento=${idDocumento}`)
                .then(res => res.json())
                .then(data => {
                    let opciones = '';

                    if (!Array.isArray(data) || data.length === 0) {
                        opciones = '<option value="">No hay informes</option>';
                    } else {
                        opciones = '<option value=""></option>'; // opción vacía inicial
                        opciones += data.map(inf =>
                            `<option value="${inf.IdInforme}">${inf.NombreInforme}</option>`
                        ).join('');
                    }

                    select.innerHTML = opciones;

                    // --- Inicializar Selectize ---
                    const $select = $(select);

                    // Destruir instancia previa si ya existe (por recargas dinámicas)
                    if ($select[0].selectize) {
                        $select[0].selectize.destroy();
                    }

                    $select.selectize({
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
                .catch(err => {
                    console.error('Error cargando informes:', err);
                    select.innerHTML = '<option>Error al cargar</option>';
                });
        });
    </script>
    <script>
        let documentoSeleccionado = null;
        let idArea = <?= $_SESSION['dg_area_id'] ?>;

        function abrirModalInforme() {
            document.getElementById('modalCrearInforme').style.display = 'flex';
            actualizarCorrelativo();
        }

        function cerrarModalInforme() {
            document.getElementById('modalCrearInforme').style.display = 'none';
            documentoSeleccionado = null;
            document.getElementById('buscarDocumento').value = '';
            document.getElementById('resultadosDocumento').innerHTML = '';
            document.getElementById('tituloInforme').value = '';
            document.getElementById('nombreFinalInforme').value = '';
        }

        // Función para actualizar correlativo y mostrar en el input de nombre final
        function actualizarCorrelativo() {
            fetch('../../backend/php/archivos/obtener_correlativo_informe.php?area=' + idArea)
                .then(res => res.json())
                .then(data => {
                    const correlativo = data.correlativo;
                    const año = data.año;
                    const tituloInput = document.getElementById('tituloInforme').value;
                    document.getElementById('nombreFinalInforme').value = `INFORME N°. ${correlativo}-${año}-${tituloInput}`;
                });
        }

        // Buscador de documentos
        const buscarInput = document.getElementById('buscarDocumento');
        const resultadosLista = document.getElementById('resultadosDocumento');

        buscarInput.addEventListener('input', function() {
            const q = this.value.trim();
            if (q.length < 2) {
                resultadosLista.innerHTML = '';
                return;
            }

            fetch(`../../backend/php/archivos/buscar_documentos.php?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    resultadosLista.innerHTML = '';
                    data.forEach(doc => {
                        const li = document.createElement('li');
                        li.textContent = `${doc.NumeroDocumento} - ${doc.Asunto}`;
                        li.onclick = () => {
                            documentoSeleccionado = doc.IdDocumentos;
                            buscarInput.value = `${doc.NumeroDocumento} - ${doc.Asunto}`;
                            document.getElementById('idDocumentoModal').value = doc.IdDocumentos; // <--- importante
                            resultadosLista.innerHTML = '';
                        };
                        resultadosLista.appendChild(li);
                    });
                });
        });

        // Actualizar nombre final mientras escribe el usuario
        document.getElementById('tituloInforme').addEventListener('input', function() {
            actualizarCorrelativo();
        });

        function seleccionarDocumento(id, texto) {
            document.getElementById('buscarDocumento').value = texto;
            document.getElementById('idDocumentoModal').value = id; // <--- aquí guardamos el id
            document.getElementById('resultadosDocumento').innerHTML = '';
        }

        // Guardar informe
        function guardarInforme() {
            const id_documento = document.getElementById('idDocumentoModal').value;
            const id_area = document.getElementById('idAreaModal').value;
            const titulo = document.getElementById('tituloInforme').value.trim();

            if (!id_documento || !id_area || !titulo) {
                alert("Faltan datos para crear el informe.");
                return;
            }

            fetch('../../backend/php/archivos/crear_informe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id_documento=${encodeURIComponent(id_documento)}&id_area=${encodeURIComponent(id_area)}&titulo=${encodeURIComponent(titulo)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Actualizar nombre final en el modal
                        document.getElementById('nombreFinalInforme').value = data.nombre_final;

                        // Guardamos el IdInforme en un hidden (opcional)
                        let inputIdInforme = document.getElementById('idInformeModal');
                        if (!inputIdInforme) {
                            inputIdInforme = document.createElement('input');
                            inputIdInforme.type = 'hidden';
                            inputIdInforme.id = 'idInformeModal';
                            inputIdInforme.value = data.id_informe;
                            document.getElementById('modalCrearInforme').appendChild(inputIdInforme);
                        } else {
                            inputIdInforme.value = data.id_informe;
                        }

                        // Cerrar modal
                        cerrarModalInforme();

                        // ---- NUEVO: recargar página para actualizar tabla ----
                        location.reload();

                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Error al crear el informe");
                });
        }
    </script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tablaReenvio').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                responsive: true,
                pageLength: 25,
                order: [
                    [0, 'desc']
                ]
            });

            // Inicializar Selectize y botones por fila
            $('select[name="nueva_area"]').each(function() {
                const $select = $(this);
                const row = $select.closest('tr');
                const btnReenviar = row.find('.btn-reenviar');
                const btnFinalizar = row.find('.btn-finalizar');

                // Inicializamos Selectize
                const selectize = $select.selectize({
                    allowEmptyOption: true,
                    placeholder: 'Seleccione una opción',
                    sortField: 'text',
                    create: false,
                    dropdownParent: 'body',
                    onFocus: function() {
                        this.removeOption('');
                        this.refreshOptions(false);
                    }
                })[0].selectize;

                // Inicializar estado de botones
                btnReenviar.removeClass('btn-success').addClass('btn-secondary').prop('disabled', true);
                btnFinalizar.prop('disabled', false); // Finalizar siempre activo

                // Función para habilitar/deshabilitar solo Reenviar
                function actualizarBotonReenviar() {
                    if (selectize.getValue()) {
                        btnReenviar.removeClass('btn-secondary').addClass('btn-success').prop('disabled', false);
                    } else {
                        btnReenviar.removeClass('btn-success').addClass('btn-secondary').prop('disabled', true);
                    }
                }

                // Escuchar solo change en Selectize
                selectize.on('change', actualizarBotonReenviar);
            });

            // Mejorar UX del formulario
            $('.reenvio-form').on('submit', function(e) {
                const button = $(this).find('button[type="submit"]');
                const originalText = button.html();

                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

                // Restaurar botón después de 3 segundos si algo falla
                setTimeout(() => {
                    if (button.prop('disabled')) {
                        button.prop('disabled', false).html(originalText);
                    }
                }, 3000);
            });
        });
    </script>
    <!-- JavaScript del Navbar -->
    <script>
        $(document).ready(function() {
            // Mobile toggle
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            // Dropdown functionality
            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                // Cerrar otros dropdowns
                $('.nav-dropdown').not($(this).parent()).removeClass('active');

                // Toggle este dropdown
                $(this).parent().toggleClass('active');
            });

            // Cerrar dropdown al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], textarea');

            inputs.forEach(function(element) {
                element.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        });
    </script>
    <script>
        function incrementarFolios(button) {
            const input = button.previousElementSibling;
            let currentValue = parseInt(input.value);
            if (currentValue >= 1) {
                input.value = currentValue + 1;
            }
        }
    </script>
</body>

</html>