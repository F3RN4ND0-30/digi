<?php
session_start();
require_once '../Backend/db/conexion.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION["dg_id"])) {
    header("Location: sisvis/escritorio.php");
    exit;
}

$error = '';
$success = '';
$intentos_fallidos = $_SESSION['intentos_login'] ?? 0;
$tiempo_bloqueo = 300; // 5 minutos
$bloqueado = false;

if (isset($_SESSION['tiempo_bloqueo']) && time() < $_SESSION['tiempo_bloqueo']) {
    $bloqueado = true;
    $tiempo_restante = $_SESSION['tiempo_bloqueo'] - time();
    $error = "Demasiados intentos fallidos. Espere " . ceil($tiempo_restante / 60) . " minutos.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    $usuario = trim($_POST['username'] ?? '');
    $clave = $_POST['password'] ?? '';

    if (empty($usuario) || empty($clave)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        $stmt = $pdo->prepare("SELECT u.*, a.Nombre AS nombre_area 
                               FROM usuarios u 
                               INNER JOIN areas a ON u.IdAreas = a.IdAreas 
                               WHERE u.Usuario = :usuario AND u.Estado = 1 
                               LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioDB && password_verify($clave, $usuarioDB['Clave'])) {
            // Login correcto: resetear intentos fallidos
            unset($_SESSION['intentos_login'], $_SESSION['tiempo_bloqueo']);

            $nombre = !empty($usuarioDB['Nombres']) ? $usuarioDB['Nombres'] : '';
            $apellido = !empty($usuarioDB['ApellidoPat']) ? $usuarioDB['ApellidoPat'] : '';

            $_SESSION['dg_usuario'] = $usuarioDB['Usuario'];
            $_SESSION['dg_area'] = $usuarioDB['nombre_area'];
            $_SESSION['dg_nombre'] = trim($nombre . ' ' . $apellido);
            $_SESSION['dg_rol'] = $usuarioDB['IdRol'];
            $_SESSION['dg_id'] = $usuarioDB['IdUsuarios'];
            $_SESSION['dg_login_time'] = time();
            $_SESSION['dg_last_activity'] = time();

            header('Location: sisvis/escritorio.php');
            exit;
        } else {
            $intentos_fallidos++;
            $_SESSION['dg_intentos_login'] = $intentos_fallidos;

            if ($intentos_fallidos >= 5) {
                $_SESSION['tiempo_bloqueo'] = time() + $tiempo_bloqueo;
                $error = "Cuenta bloqueada temporalmente por 5 minutos.";
            } else {
                $error = "Usuario o contraseña incorrectos. Intento $intentos_fallidos de 5.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DIGI</title>
    <meta name="description" content="Sistema de Gestión de Documentos - Municipalidad Provincial de Pisco">
    <meta name="keywords" content="gestión documentos, municipalidad, pisco, sistema">
    <meta name="author" content="Municipalidad Provincial de Pisco">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Backend/css/login.css">
    <link rel="stylesheet" href="../Backend/css/login-responsive.css">
</head>

<body>
    <div class="contenedor-login">
        <div class="fondo-login">
            <div class="formas-geometricas">
                <div class="forma forma-1"></div>
                <div class="forma forma-2"></div>
                <div class="forma forma-3"></div>
                <div class="forma forma-4"></div>
                <div class="forma forma-5"></div>
            </div>
        </div>

        <!-- Contenedor unificado -->
        <div class="contenedor-principal">
            <!-- Panel de login -->
            <div class="panel-login">
                <!-- Header del login -->
                <div class="encabezado-login">
                    <div class="logo-principal">
                        <img src="../Backend/img/logoPisco.png" alt="Escudo de Pisco" class="imagen-logo">
                    </div>
                    <div class="contenido-encabezado">
                        <h1>DIGI</h1>
                        <p>Sistema de Gestión de Documentos</p>
                    </div>

                    <!-- Información móvil - eliminada según requerimiento -->
                </div>

                <!-- Alertas del sistema -->
                <?php if (!empty($error)): ?>
                    <div class="alerta alerta-error" id="alertaError">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                        <button type="button" class="cerrar-alerta" onclick="cerrarAlerta('alertaError')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alerta alerta-exito" id="alertaExito">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                        <button type="button" class="cerrar-alerta" onclick="cerrarAlerta('alertaExito')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($intentos_fallidos > 0 && $intentos_fallidos < 5 && !$bloqueado): ?>
                    <div class="alerta alerta-advertencia" id="alertaAdvertencia">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Intento <?= $intentos_fallidos ?> de 5. Quedan <?= (5 - $intentos_fallidos) ?> intentos.</span>
                        <button type="button" class="cerrar-alerta" onclick="cerrarAlerta('alertaAdvertencia')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Formulario de login -->
                <form method="post" action="login.php" class="formulario-login" id="formularioLogin">
                    <div class="grupo-campo">
                        <label for="username">
                            <i class="fas fa-user-tie"></i>
                            Usuario
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            autocomplete="username"
                            placeholder="Ingrese su usuario municipal"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            <?= $bloqueado ? 'disabled' : '' ?>
                            class="campo-formulario">
                    </div>

                    <div class="grupo-campo">
                        <label for="password">
                            <i class="fas fa-key"></i>
                            Contraseña
                        </label>
                        <div class="contenedor-password">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Ingrese su contraseña"
                                <?= $bloqueado ? 'disabled' : '' ?>
                                class="campo-formulario">
                            <button type="button" class="mostrar-password" id="mostrarPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="opciones-formulario">
                        <label class="contenedor-checkbox">
                            <input type="checkbox" name="remember">
                            <span class="marca-checkbox"></span>
                            Mantener sesión activa
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="boton-login"
                        id="botonLogin"
                        <?= $bloqueado ? 'disabled' : '' ?>>
                        <span class="texto-boton">
                            <?php if ($bloqueado): ?>
                                <i class="fas fa-lock"></i> Acceso Bloqueado
                            <?php else: ?>
                                <i class="fas fa-sign-in-alt"></i> Acceder al Sistema
                            <?php endif; ?>
                        </span>
                        <div class="cargador-boton">
                            <div class="spinner"></div>
                        </div>
                    </button>
                </form>

                <!-- Footer simplificado -->
                <div class="pie-login">
                    <p>Sistema DIGI v1.0 - 2025</p>
                </div>
            </div>

            <!-- Panel visual - solo desktop -->
            <div class="panel-visual">
                <div class="ilustracion-documentos">
                    <div class="pila-documentos">
                        <div class="documento doc-1">
                            <div class="encabezado-doc"></div>
                            <div class="lineas-doc">
                                <div class="linea"></div>
                                <div class="linea"></div>
                                <div class="linea corta"></div>
                            </div>
                            <div class="icono-doc">
                                <i class="fas fa-file-text"></i>
                            </div>
                        </div>
                        <div class="documento doc-2">
                            <div class="encabezado-doc"></div>
                            <div class="lineas-doc">
                                <div class="linea"></div>
                                <div class="linea"></div>
                                <div class="linea corta"></div>
                            </div>
                            <div class="icono-doc">
                                <i class="fas fa-file-signature"></i>
                            </div>
                        </div>
                        <div class="documento doc-3">
                            <div class="encabezado-doc"></div>
                            <div class="lineas-doc">
                                <div class="linea"></div>
                                <div class="linea"></div>
                                <div class="linea corta"></div>
                            </div>
                            <div class="icono-doc">
                                <i class="fas fa-file-check"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flechas-flujo">
                        <div class="flecha flecha-1">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="flecha flecha-2">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>

                    <div class="puntos-proceso">
                        <div class="punto punto-1"></div>
                        <div class="punto punto-2"></div>
                        <div class="punto punto-3"></div>
                    </div>
                </div>

                <div class="contenido-visual">
                    <h3>Gestión Digital Inteligente</h3>
                    <p>Transformando la administración municipal con tecnología de vanguardia</p>

                    <div class="vista-estadisticas">
                        <div class="item-estadistica">
                            <div class="numero-estadistica">100%</div>
                            <div class="etiqueta-estadistica">Digital</div>
                        </div>
                        <div class="item-estadistica">
                            <div class="numero-estadistica">24/7</div>
                            <div class="etiqueta-estadistica">Disponible</div>
                        </div>
                        <div class="item-estadistica">
                            <div class="numero-estadistica">Seguro</div>
                            <div class="etiqueta-estadistica">Encriptado</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/login.js"></script>
</body>

</html>