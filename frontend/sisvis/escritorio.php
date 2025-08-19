<?php
// escritorio.php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

// Área del usuario (para excluirla en el select, si aplica)
$area_id = $_SESSION['dg_area_id'] ?? null;
// Obtener estados desde la tabla estadodocumento
$estados = $pdo->query("SELECT IdEstadoDocumento, Estado FROM estadodocumento")->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las áreas (para elegir el destino)
$areas = $pdo->query("SELECT IdAreas, Nombre FROM areas")->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Escritorio - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css" />

    <!-- CSS Principal del Escritorio -->
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />

    <!-- Selectize CSS -->
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/css/selectize.default.min.css" rel="stylesheet" />

    <!-- jQuery (requerido por Selectize) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Selectize JS -->
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/js/selectize.min.js"></script>

    <script src="../../backend/js/notificaciones.js"></script>
</head>

<body>
    <div class="layout-escritorio">

        <aside class="sidebar">
            <h2>DIGI - MPP</h2>
            <nav>
                <a href="../sisvis/escritorio.php">🏠 Inicio</a>
                <a href="../archivos/recepcion.php">📥 Recepción</a>
                <a href="../archivos/enviados.php">📤 Enviados</a>
                <a href="../archivos/reenviar.php">📤 Reenviar</a>
                <a href="../seguimiento/busqueda.php">📤 Buscar</a>
                <a href="#">⚙️ Configuración</a>
                <!-- En tu navbar o barra lateral -->
                <div id="notificaciones" style="position: relative; cursor: pointer;">
                    🔔 <span id="contador" style="color: red; font-weight: bold;"></span>
                </div>

                <!-- Contenedor para la lista -->
                <div id="listaNotificaciones" style="display: none; position: absolute; background: #fff; color:black; border: 1px solid #ccc; max-height: 300px; overflow-y: auto; padding: 10px; width: 300px; z-index: 100;">
                    <strong>Notificaciones:</strong>
                    <ul id="contenedorNotificaciones" style="list-style: none; padding-left: 0;"></ul>
                </div>
                <a href="../logout.php">🚪 Cerrar sesión</a>
            </nav>
        </aside>

        <main class="contenido-principal">
            <div class="tarjeta bienvenida">
                <h3>BIENVENID@, <?php echo htmlspecialchars($_SESSION['dg_nombre']); ?>!</h3>
                <p>Nos alegra tenerte aquí en DIGI, el sistema avanzado para el seguimiento y gestión de tus documentos. Aquí podrás revisar tus reportes, gestionar usuarios, y mantener un control eficiente de tus tareas diarias.</p>
                <p>Explora el panel y sácale el máximo provecho a nuestras herramientas para optimizar tu trabajo.</p>
            </div>

            <div class="tarjeta tarjeta-formulario">
                <h2><i class="fas fa-plus-circle"></i> Registrar nuevo documento</h2>

                <?php if (!empty($mensaje)) : ?>
                    <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
                <?php endif; ?>

                <form method="POST" action="../../backend/php/archivos/registrar_archivo.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Número de Documento:</label>
                            <input type="text" name="numero" required placeholder="Ej: DOC-2025-001">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-flag"></i> Estado:</label>
                            <select name="estado" required>
                                <option value="">Seleccione un estado</option>
                                <?php foreach ($estados as $estado) : ?>
                                    <option value="<?= $estado['IdEstadoDocumento'] ?>"><?= htmlspecialchars($estado['Estado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Área de destino:</label>
                            <select name="area_destino" required>
                                <option value="">Seleccione un área</option>
                                <?php foreach ($areas as $area) : ?>
                                    <?php if ((int)$area['IdAreas'] !== (int)$area_id) : ?>
                                        <option value="<?= $area['IdAreas'] ?>"><?= htmlspecialchars($area['Nombre']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-sticky-note"></i> Observación:</label>
                            <textarea name="observacion" rows="3" placeholder="Escriba alguna observación opcional..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Asunto:</label>
                            <textarea name="asunto" required pla.ceholder="Describa el asunto del documento..." rows="4"></textarea>
                        </div>
                    </div>

                    <button type="submit">
                        <i class="fas fa-rocket"></i> Registrar y Enviar
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/js/selectize.min.js"></script>

    <script>
        $(function() {
            $('select').selectize({
                allowEmptyOption: true,
                placeholder: 'Seleccione una opción',
                sortField: 'text',
                create: false
            });
        });
    </script>
    <script>
        // Esperar a que todo esté cargado
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
</body>

</html>