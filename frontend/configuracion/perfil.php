<?php
session_start();
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

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
    <title>Perfil de Usuario - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- CSS del Navbar -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css" />

    <!-- CSS Principal -->
    <link rel="stylesheet" href="../../backend/css/configuracion/perfil.css" />

    <script src="../../backend/js/notificaciones.js"></script>
</head>

<body>
    <div class="layout-perfil">
        <?php include '../navbar/navbar.php'; ?>

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
                            <input type="text" id="dni" value="<?= htmlspecialchars($usuario['Dni']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="area"><i class="fas fa-building"></i> Área:</label>
                            <input type="text" id="area" value="<?= htmlspecialchars($usuario['AreaNombre']) ?>" readonly>
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

</body>

</html>