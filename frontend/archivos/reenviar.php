<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

$area_id = $_SESSION['dg_area_id'] ?? null;

$sql = "SELECT m.IdMovimientoDocumento, m.IdDocumentos, d.NumeroDocumento, d.Asunto, d.IdUsuarios, d.IdAreaFinal,
               u.Nombres, u.ApellidoPat
        FROM movimientodocumento m
        INNER JOIN documentos d ON m.IdDocumentos = d.IdDocumentos
        INNER JOIN usuarios u ON d.IdUsuarios = u.IdUsuarios
        WHERE m.AreaDestino = ?
          AND m.Recibido = 1
          AND d.Finalizado = 0
        ORDER BY m.IdMovimientoDocumento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$area_id]);
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

    <!-- CSS del Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css" />

    <!-- CSS de Reenvío -->
    <link rel="stylesheet" href="../../backend/css/archivos/reenviados.css" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Selectize CSS -->
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <!-- Selectize JS -->
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>

    <script src="../../backend/js/notificaciones.js"></script>
</head>

<body>
    <div class="layout-escritorio">

        <?php include '../navbar/navbar.php'; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2><i class="fas fa-share"></i> Reenviar Documentos</h2>
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
                                        <th><i class="fas fa-sticky-note"></i> Observación</th>
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
                                                <td class="observacion-input">
                                                    <input type="text"
                                                        name="observacion"
                                                        class="form-control form-control-sm observacion-grande"
                                                        placeholder="Observación opcional..."
                                                        maxlength="100">
                                                </td>
                                                <td class="accion-btn d-flex flex-column gap-1">
                                                    <!-- Botón Reenviar -->
                                                    <input type="hidden" name="id_documento" value="<?= $doc['IdDocumentos'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm w-100 btn-reenviar">
                                                        <i class="fas fa-paper-plane"></i> Reenviar
                                                    </button>
                                            </form>

                                            <?php if ($doc['IdAreaFinal'] == $_SESSION['dg_area_id']): ?>
                                                <!-- Botón Finalizar (solo para el creador del documento) -->
                                                <form method="POST" action="../../backend/php/archivos/finalizar_documento.php">
                                                    <input type="hidden" name="id_documento" value="<?= $doc['IdDocumentos'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm w-100 btn-finalizar"
                                                        onclick="return confirm('¿Estás seguro de que deseas marcar este documento como finalizado?');">
                                                        <i class="fas fa-check-circle"></i> Finalizar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

    <!-- Scripts -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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

            $('select[name="nueva_area"]').selectize({
                allowEmptyOption: true,
                placeholder: 'Seleccione una opción',
                sortField: 'text',
                create: false,
                dropdownParent: 'body', // 🔧 aquí está la clave
                onFocus: function() {
                    this.removeOption('');
                    this.refreshOptions(false);
                }
            });


            // Mejorar UX del formulario
            $('.reenvio-form').on('submit', function(e) {
                const button = $(this).find('button[type="submit"]');
                const originalText = button.html();

                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

                // Si hay error, restaurar botón después de 3 segundos
                setTimeout(() => {
                    if (button.prop('disabled')) {
                        button.prop('disabled', false).html(originalText);
                    }
                }, 3000);
            });

            // Validación en tiempo real
            $('select[name="nueva_area"]').on('change', function() {
                const row = $(this).closest('tr');
                const button = row.find('button[type="submit"]');

                if ($(this).val()) {
                    button.removeClass('btn-secondary').addClass('btn-success');
                    button.prop('disabled', false);
                } else {
                    button.removeClass('btn-success').addClass('btn-secondary');
                    button.prop('disabled', true);
                }
            });

            // Inicializar estado de botones
            $('select[name="nueva_area"]').each(function() {
                const row = $(this).closest('tr');
                const btnReenviar = row.find('.btn-reenviar');

                btnReenviar.removeClass('btn-success').addClass('btn-secondary');
                btnReenviar.prop('disabled', true);
            });

            // Al cambiar el área seleccionada, activar solo el botón de reenviar
            $('select[name="nueva_area"]').on('change', function() {
                const row = $(this).closest('tr');
                const btnReenviar = row.find('.btn-reenviar');

                if ($(this).val()) {
                    btnReenviar.removeClass('btn-secondary').addClass('btn-success');
                    btnReenviar.prop('disabled', false);
                } else {
                    btnReenviar.removeClass('btn-success').addClass('btn-secondary');
                    btnReenviar.prop('disabled', true);
                }
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
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], textarea');

            inputs.forEach(function(element) {
                element.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        });
    </script>
</body>

</html>