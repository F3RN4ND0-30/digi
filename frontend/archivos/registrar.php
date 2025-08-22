<?php
// escritorio.php
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

$usuario_id = $_SESSION['dg_id'] ?? null;
$area_id = $_SESSION['dg_area_id'] ?? null;

// Obtener el rol del usuario (si no lo tienes en sesión)
$rol_stmt = $pdo->prepare("SELECT IdRol FROM usuarios WHERE IdUsuarios = ?");
$rol_stmt->execute([$usuario_id]);
$rol_id = (int)$rol_stmt->fetchColumn();

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

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />
    <link rel="stylesheet" href="../../backend/css/archivos/modal_exterior.css" />

    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>
    
    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
</head>

<body>
    <div class="layout-escritorio">
        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta tarjeta-formulario">
                <h2><i class="fas fa-plus-circle"></i> Registrar nuevo documento</h2>

                <?php if (!empty($mensaje)) : ?>
                    <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
                <?php endif; ?>

                <form method="POST" action="../../backend/php/archivos/registrar_archivo.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Nombre de Documento:</label>
                            <input type="text" name="numero" required placeholder="Ej: DOC-2025-001">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-flag"></i> Estado:</label>
                            <input type="text" value="Nuevo" readonly disabled>
                            <input type="hidden" name="estado" value="1"> <!-- ID del estado "Nuevo" -->
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

                        <?php if ($rol_id === 1 || $rol_id === 3): ?>
                            <div class="form-group">
                                <label><i class="fas fa-external-link-alt"></i> ¿Es exterior?</label>
                                <?php if ($rol_id === 1 || $rol_id === 3): ?>
                                    <input type="text" id="campoExterior" name="exterior" readonly required placeholder="Seleccione en el modal..." />
                                <?php else: ?>
                                    <select name="exterior" required>
                                        <option value="">Seleccione una opción</option>
                                        <option value="SI">Sí</option>
                                        <option value="NO">No</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="exterior" value="NO" />
                        <?php endif; ?>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Asunto:</label>
                            <textarea name="asunto" required placeholder="Describa el asunto del documento..." rows="4"></textarea>
                        </div>

                        <?php if ($rol_id === 1 || $rol_id === 3): ?>
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> Área Final:</label>
                                <select name="area_final" required>
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($areas as $area) : ?>
                                        <?php if ((int)$area['IdAreas'] !== (int)$area_id) : ?>
                                            <option value="<?= $area['IdAreas'] ?>"><?= htmlspecialchars($area['Nombre']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit">
                        <i class="fas fa-rocket"></i> Registrar y Enviar
                    </button>
                </form>
            </div>
        </main>
    </div>

    <?php if ($rol_id === 1 || $rol_id === 3): ?>
        <!-- Modal Exterior -->
        <div id="modalExterior" class="modal-overlay">
            <div class="modal-contenido">
                <h3><i class="fas fa-question-circle"></i> ¿Este documento es exterior?</h3>
                <div class="modal-botones">
                    <button class="btn-si" onclick="seleccionarExterior('SI')">Sí</button>
                    <button class="btn-no" onclick="seleccionarExterior('NO')">No</button>
                </div>
                <a href="../sisvis/escritorio.php" class="btn-link">Regresar al inicio</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
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

        $(document).ready(function() {
            $('select').selectize({
                allowEmptyOption: true,
                placeholder: 'Seleccione una opción',
                sortField: 'text',
                create: false,
                onFocus: function() {
                    this.removeOption('');
                    this.refreshOptions(false);
                }
            });
        });

        <?php if ($rol_id === 1 || $rol_id === 3): ?>
            // Mostrar modal para rol MESA
            document.addEventListener("DOMContentLoaded", function() {
                const modal = document.getElementById('modalExterior');
                const campoExterior = document.getElementById('campoExterior');

                if (modal) {
                    modal.style.display = 'flex';
                }

                window.seleccionarExterior = function(opcion) {
                    if (campoExterior) {
                        campoExterior.value = opcion;
                    }
                    if (modal) {
                        modal.style.display = 'none';
                    }
                };
            });
        <?php endif; ?>


        // Funciones del navbar y dropdowns
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
</body>

</html>