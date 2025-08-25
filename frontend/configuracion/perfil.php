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

$id_usuario = $_SESSION['dg_id'];

// Obtener datos del usuario
$sql = "SELECT u.Dni, u.Nombres, u.ApellidoPat, u.ApellidoMat, u.Usuario, u.Clave, a.Nombre AS AreaNombre
        FROM usuarios u
        LEFT JOIN areas a ON u.IdAreas = a.IdAreas
        WHERE u.IdUsuarios = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    $_SESSION['mensaje'] = "❌ Usuario no encontrado.";
    header('Location: escritorio.php');
    exit;
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil de Usuario - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- CSS Principal -->
    <link rel="stylesheet" href="../../backend/css/configuracion/perfil.css" />
    
    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
</head>

<body>
    <div class="layout-perfil">
        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta tarjeta-formulario">
                <h2><i class="fas fa-user-circle"></i> Mi Perfil</h2>

                <?php if ($mensaje) : ?>
                    <p><?= htmlspecialchars($mensaje) ?></p>
                <?php endif; ?>

                <form method="POST" action="../../backend/php/configuracion/perfil_usuario.php" onsubmit="return validarClaves()">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="dni"><i class="fas fa-id-card"></i> DNI:</label>
                            <input type="text" id="dni" value="<?= htmlspecialchars($usuario['Dni']) ?>" readonly style="background-color: #a19f9fff; color: #555;">
                        </div>

                        <div class="form-group">
                            <label for="area"><i class="fas fa-building"></i> Área:</label>
                            <input type="text" id="area" value="<?= htmlspecialchars($usuario['AreaNombre']) ?>" readonly style="background-color: #a19f9fff; color: #555;">
                        </div>

                        <div class="form-group">
                            <label for="nombres"><i class="fas fa-user"></i> Nombres:</label>
                            <input type="text" name="nombres" id="nombres" value="<?= htmlspecialchars($usuario['Nombres']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="apellidoPat"><i class="fas fa-user"></i> Apellido Paterno:</label>
                            <input type="text" name="apellidoPat" id="apellidoPat" value="<?= htmlspecialchars($usuario['ApellidoPat']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="apellidoMat"><i class="fas fa-user"></i> Apellido Materno:</label>
                            <input type="text" name="apellidoMat" id="apellidoMat" value="<?= htmlspecialchars($usuario['ApellidoMat']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="usuario"><i class="fas fa-user-circle"></i> Usuario:</label>
                            <input type="text" name="usuario" id="usuario" value="<?= htmlspecialchars($usuario['Usuario']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="clave"><i class="fas fa-lock"></i> Nueva Clave:</label>
                            <input type="password" name="clave" id="clave">
                        </div>

                        <div class="form-group">
                            <label for="repetir_clave"><i class="fas fa-lock"></i> Repetir Nueva Clave:</label>
                            <input type="password" name="repetir_clave" id="repetir_clave">
                        </div>
                    </div>

                    <button type="submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                </form>
            </div>
        </main>
    </div>

    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
    <script src="../../backend/js/notificaciones.js"></script>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/js/selectize.min.js"></script>

    <script>
        function validarClaves() {
            const clave = document.getElementById('clave').value;
            const repetir = document.getElementById('repetir_clave').value;

            if (clave !== '' && clave !== repetir) {
                alert("❌ Las contraseñas no coinciden.");
                return false;
            }

            return true;
        }
    </script>
    <script>
        $(document).ready(function() {
            // Botón de menú móvil
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            // Dropdown
            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                // Cerrar otros
                $('.nav-dropdown').not($(this).parent()).removeClass('active');
                $(this).parent().toggleClass('active');
            });

            // Cerrar dropdown si se hace clic afuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lista de IDs a convertir a mayúsculas
            const camposMayusculas = ['nombres', 'apellidoPat', 'apellidoMat'];

            camposMayusculas.forEach(function(id) {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
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