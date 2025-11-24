<?php
session_start();
require_once '../Backend/db/conexion.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
session_regenerate_id(true);

function responderJSON($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Detectar IP real del usuario
function obtenerIP()
{
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Verificar si IP está bloqueada usando BD
function ipBloqueada($pdo, $ip)
{
    try {
        // Contar intentos fallidos de esta IP en los últimos 5 minutos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM intentos_login 
                               WHERE ip_address = :ip 
                               AND tipo = 'fallido' 
                               AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->execute(['ip' => $ip]);
        $intentos = $stmt->fetchColumn();

        // Si tiene 5 o más intentos fallidos en 5 minutos, está bloqueada
        if ($intentos >= 5) {
            return [
                'bloqueada' => true,
                'intentos' => $intentos,
                'mensaje' => "IP bloqueada por $intentos intentos fallidos en 5 minutos"
            ];
        }

        return false;
    } catch (PDOException $e) {
        error_log("Error verificando bloqueo IP: " . $e->getMessage());
        return false;
    }
}

// Registrar intento en BD (reemplaza las funciones JSON)
function registrarIntentoEnBD($pdo, $ip, $usuario, $tipo)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO intentos_login (ip_address, usuario, tipo) 
                               VALUES (:ip, :usuario, :tipo)");
        $stmt->execute([
            'ip' => $ip,
            'usuario' => $usuario,
            'tipo' => $tipo
        ]);
    } catch (PDOException $e) {
        error_log("Error registrando intento en BD: " . $e->getMessage());
    }
}

// Limpiar intentos antiguos para mantener BD limpia
function limpiarIntentosAntiguos($pdo)
{
    try {
        // Eliminar registros de más de 7 días
        $stmt = $pdo->prepare("DELETE FROM intentos_login 
                               WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error limpiando intentos antiguos: " . $e->getMessage());
    }
}

$ip_cliente = obtenerIP();

if (isset($_SESSION["dg_id"])) {
    if (isset($_POST['ajax'])) {
        responderJSON(['success' => false, 'redirect' => 'sisvis/escritorio.php']);
    }
    header("Location: sisvis/escritorio.php");
    exit;
}

$error = '';
$success = '';
$intentos_fallidos = $_SESSION['dg_intentos_login'] ?? 0;
$tiempo_bloqueo = 300;
$bloqueado_usuario = false;
$bloqueado_ip = false;

// Verificar bloqueo por usuario (sesión)
if (isset($_SESSION['tiempo_bloqueo']) && time() < $_SESSION['tiempo_bloqueo']) {
    $bloqueado_usuario = true;
    $tiempo_restante = $_SESSION['tiempo_bloqueo'] - time();
    $error = "Usuario bloqueado. Espere " . ceil($tiempo_restante / 60) . " minutos.";
}

// Verificar bloqueo por IP usando BD
$bloqueo_ip = ipBloqueada($pdo, $ip_cliente);
if ($bloqueo_ip) {
    $bloqueado_ip = true;
    $error = "IP bloqueada por exceso de intentos. Espere 5 minutos.";
}

$bloqueado = $bloqueado_usuario || $bloqueado_ip;

if (isset($_GET['timeout'])) {
    $error = "Sesión expirada por inactividad.";
}
if (isset($_GET['logout'])) {
    $success = "Sesión cerrada correctamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $usuario = trim($_POST['username'] ?? '');
    $clave = $_POST['password'] ?? '';

    if ($bloqueado) {
        responderJSON([
            'success' => false,
            'bloqueado' => true,
            'error' => $error,
            'tipo' => $bloqueado_ip ? 'ip' : 'usuario'
        ]);
    }

    if (empty($usuario) || empty($clave)) {
        responderJSON([
            'success' => false,
            'error' => 'Complete todos los campos obligatorios.',
            'bloqueado' => false
        ]);
    }

    if (strlen($usuario) < 3 || strlen($clave) < 4) {
        responderJSON([
            'success' => false,
            'error' => 'Usuario o contraseña muy cortos.',
            'bloqueado' => false
        ]);
    }

    try {
        // Consulta preparada contra SQL injection
        $stmt = $pdo->prepare("SELECT u.*, a.IdAreas, a.Nombre AS nombre_area 
            FROM usuarios u 
            INNER JOIN areas a ON u.IdAreas = a.IdAreas 
            WHERE u.Usuario = :usuario AND u.Estado = 1 
            LIMIT 1");
        $stmt->execute(['usuario' => $usuario]);
        $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificación con protección timing attack
        $login_valido = false;
        if ($usuarioDB) {
            $login_valido = password_verify($clave, $usuarioDB['Clave']);
        } else {
            password_verify($clave, '$2y$10$dummy.hash.timing.protection');
        }

        if ($login_valido) {
            // LOGIN EXITOSO
            unset($_SESSION['dg_intentos_login'], $_SESSION['tiempo_bloqueo']);
            registrarIntentoEnBD($pdo, $ip_cliente, $usuario, 'exitoso');
            session_regenerate_id(true); // Prevenir session fixation

            $nombre = trim(($usuarioDB['Nombres'] ?? '') . ' ' . ($usuarioDB['ApellidoPat'] ?? '') . ' ' . ($usuarioDB['ApellidoMat'] ?? ''));

            $_SESSION['dg_usuario'] = $usuarioDB['Usuario'];
            $_SESSION['dg_area'] = $usuarioDB['nombre_area'];
            $_SESSION['dg_area_id'] = $usuarioDB['IdAreas'];
            $_SESSION['dg_nombre'] = $nombre;
            $_SESSION['dg_rol'] = (int)$usuarioDB['IdRol'];
            $_SESSION['dg_id'] = (int)$usuarioDB['IdUsuarios'];
            $_SESSION['dg_login_time'] = time();
            $_SESSION['dg_last_activity'] = time();

            error_log("Login exitoso: {$usuario} desde IP {$ip_cliente}");

            responderJSON([
                'success' => true,
                'redirect' => 'sisvis/escritorio.php',
                'usuario' => $nombre
            ]);
        } else {
            // LOGIN FALLIDO
            $intentos_fallidos++;
            $_SESSION['dg_intentos_login'] = $intentos_fallidos;

            // Registrar intento fallido en BD
            registrarIntentoEnBD($pdo, $ip_cliente, $usuario, 'fallido');

            // Bloqueo por usuario (5 intentos en sesión)
            if ($intentos_fallidos >= 5) {
                $_SESSION['tiempo_bloqueo'] = time() + $tiempo_bloqueo;
                responderJSON([
                    'success' => false,
                    'bloqueado' => true,
                    'error' => 'Usuario bloqueado por 5 minutos por seguridad.',
                    'tipo' => 'usuario',
                    'intentos' => $intentos_fallidos
                ]);
            }

            // Verificar si IP debe bloquearse (5 intentos en 5 min)
            $nuevo_bloqueo_ip = ipBloqueada($pdo, $ip_cliente);
            if ($nuevo_bloqueo_ip) {
                responderJSON([
                    'success' => false,
                    'bloqueado' => true,
                    'error' => 'IP bloqueada por exceso de intentos.',
                    'tipo' => 'ip',
                    'intentos' => $nuevo_bloqueo_ip['intentos']
                ]);
            }

            error_log("Login fallido: {$usuario} desde IP {$ip_cliente}");

            responderJSON([
                'success' => false,
                'error' => 'Credenciales incorrectas.',
                'bloqueado' => false,
                'intentos' => $intentos_fallidos,
                'restantes' => 5 - $intentos_fallidos
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error BD login: " . $e->getMessage());
        responderJSON([
            'success' => false,
            'error' => 'Error del sistema. Intente nuevamente.',
            'bloqueado' => false
        ]);
    }
}

// Limpiar registros antiguos ocasionalmente
if (rand(1, 100) === 1) {
    limpiarIntentosAntiguos($pdo);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DIGI</title>
    <meta name="description" content="Sistema de Gestión de Documentos - Municipalidad Provincial de Pisco">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../Backend/css/login.css">
    <link rel="stylesheet" href="../Backend/css/login-responsive.css">

    <link rel="icon" type="image/png" href="../backend/img/logoPisco.png" />
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

        <div class="contenedor-principal">
            <div class="panel-login">
                <div class="encabezado-login">
                    <div class="logo-principal">
                        <img src="../Backend/img/logoPisco.png" alt="Escudo de Pisco" class="imagen-logo">
                    </div>
                    <div class="contenido-encabezado">
                        <h1>DIGI</h1>
                        <p>Sistema de Gestión de Documentos</p>
                    </div>
                </div>

                <div id="contador-intentos" style="display: none;" class="alerta alerta-advertencia">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="texto-intentos"></span>
                </div>

                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="formulario-login" id="formularioLogin">
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
                            maxlength="50"
                            minlength="3"
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
                                maxlength="100"
                                minlength="4"
                                class="campo-formulario">
                            <button type="button" class="mostrar-password" id="mostrarPassword" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- <div class="opciones-formulario">
                        <label class="contenedor-checkbox">
                            <input type="checkbox" name="remember" value="1">
                            <span class="marca-checkbox"></span>
                            Mantener sesión activa
                        </label>
                    </div> -->

                    <button type="submit" class="boton-login" id="botonLogin">
                        <span class="texto-boton">
                            <i class="fas fa-sign-in-alt"></i> Acceder al Sistema
                        </span>
                        <div class="cargador-boton">
                            <div class="spinner"></div>
                        </div>
                    </button>


                    <a href="../index.php" class="volver">volver al inicio</a>

                </form>

                <div class="pie-login">
                    <p>Sistema DIGI v1.0 - 2025 | <i class="fas fa-shield-alt"></i> Conexión Segura</p>
                </div>
            </div>

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
                            <div class="etiqueta-estadistica">Protegido</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../backend/js/login.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($success)): ?>
                if (typeof window.mostrarMensajeExito === 'function') {
                    window.mostrarMensajeExito('<?= addslashes($success) ?>');
                }
            <?php endif; ?>

            <?php if (!empty($error) && !$bloqueado): ?>
                if (typeof window.mostrarMensajeError === 'function') {
                    window.mostrarMensajeError('<?= addslashes($error) ?>');
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>