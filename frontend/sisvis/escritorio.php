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

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- CSS Principal del Escritorio -->
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />
    <link rel="stylesheet" href="../../backend/css/sisvis/asistente.css" />

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="layout-escritorio">
        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta bienvenida">
                <h3>BIENVENID@, <?= htmlspecialchars($_SESSION['dg_nombre']); ?>!</h3>
                <p>Nos alegra tenerte aquí en DIGI, el sistema avanzado para el seguimiento y gestión de tus documentos. Aquí podrás revisar tus reportes, gestionar usuarios, y mantener un control eficiente de tus tareas diarias.</p>
                <p>Explora el panel y sácale el máximo provecho a nuestras herramientas para optimizar tu trabajo.</p>
            </div>

            <?php
            $gifs = [
                'durando_trabajo.gif',
                'ocupado.gif',
                'hola.gif',
                'mensaje.gif',
                'notificaciones.gif',
                'señalar.gif',
                'cafe.gif',
                'pengu-nice.gif',
                'pengu-teclado.gif',
                'pengu-zzz.gif'
            ];
            $gifAleatorio = $gifs[array_rand($gifs)];
            ?>
            <div class="seccion-asistente">
                <div class="asistente-gif">
                    <img src="../../backend/img/asistentes/<?= $gifAleatorio ?>" alt="Asistente animado">
                </div>
                <div class="asistente-mensaje">
                    <h4>HOLA, <?= htmlspecialchars($_SESSION['dg_nombre']); ?></h4>
                    <p>Yo soy <strong>Pengu</strong>, tu asistente digital. Estaré aquí para traer notificaciones importantes, recordatorios de documentos y mucho más. ¡No te preocupes, te ayudaré a mantenerte al día!</p>
                </div>
            </div>
        </main>
    </div>

    <!-- JS de notificaciones -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- JS para controlar el dropdown del navbar -->
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


</body>

</html>