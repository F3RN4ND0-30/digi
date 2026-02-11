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

//Obtener tipo de objetos (CD, USB, etc)
$tipos_objeto = $pdo->query("SELECT IdTipoObjeto, Descripcion FROM tipo_objeto")->fetchAll(PDO::FETCH_ASSOC);

// =================================================================
// LÓGICA PARA OBTENER EL ÚLTIMO INFORME POR ÁREA
// =================================================================
$ultimo_informe = null;

if (!empty($area_id)) {

    $sql_ultimo_informe = "SELECT 
                                NombreInforme, Asunto, FechaEmision, Año FROM informes 
                            WHERE 
                                IdArea = ? 
                            ORDER BY 
                                FechaEmision DESC, IdInforme DESC 
                            LIMIT 1";

    $stmt_informe = $pdo->prepare($sql_ultimo_informe);
    $stmt_informe->execute([$area_id]);

    $ultimo_informe = $stmt_informe->fetch(PDO::FETCH_ASSOC);
}
// =================================================================
// NUEVA LÓGICA: OBTENER ABREVIATURA DEL ÁREA
// =================================================================
$anio = date("Y"); // Asignar el año actual por defecto
$abreviatura_area = 'DEF'; // Valor por defecto si no se encuentra
if (!empty($area_id)) {
    $stmt_abreviatura = $pdo->prepare("SELECT Abreviatura FROM areas WHERE IdAreas = ?");
    $stmt_abreviatura->execute([$area_id]);
    $result = $stmt_abreviatura->fetchColumn();

    if ($result) {
        $abreviatura_area = $result;
    }
}
$prefijo_base = "INFORME N°.";
$sufijo_dinamico = "-" . $anio . "-MPP-" . $abreviatura_area;

$valor_automatico = $prefijo_base . $sufijo_dinamico;
$longitud_prefijo_fijo = strlen($prefijo_base);
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
    <?php
    // Leer el estado del modo aviso global
    $estadoFile = __DIR__ . "/../../frontend/modo_aviso.json";
    $modoActivo = true; // Por defecto activo (sistema normal)

    if (file_exists($estadoFile)) {
        $jsonData = json_decode(file_get_contents($estadoFile), true);
        $modoActivo = $jsonData["modo_pago_activo"];
    }

    // Mostrar banner si NO han pagado
    if (!$modoActivo): ?>
        <div style="
        background: #d50000;
        color: white;
        padding: 15px;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        font-family: Inter, sans-serif;
        z-index: 9999;
        position: relative;
        border-bottom: 4px solid #7f0000;
    ">
            ⚠️ ¡NO HAN PAGADO! ESTA PÁGINA HA SIDO INTERVENIDA POR SISTEMAS ⚠️
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const nav = document.querySelector("nav");
                if (nav) {
                    nav.style.pointerEvents = "none";
                    nav.style.opacity = "0.4";
                }
            });
        </script>

    <?php endif; ?>
    <div class="layout-escritorio">
        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta tarjeta-formulario">
                <h2><i class="fas fa-plus-circle"></i> Registrar nuevo documento</h2>
                <?php if (!empty($ultimo_informe)) : ?>
                    <div class="info-ultimo-documento">
                        <label for="ultimo_doc_input" class="label-ultimo-doc">
                            <i class="fas fa-history"></i> Último Informe Creado (<?= htmlspecialchars($ultimo_informe['NombreInforme']) ?>)
                        </label>
                    </div>
                <?php else : ?>
                    <div class="info-ultimo-documento no-data">
                        <label><i class="fas fa-info-circle"></i> No hay informes previos para el Área <?= htmlspecialchars($area_id) ?></label>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensaje)) : ?>
                    <?php
                    $claseMensaje = strpos($mensaje, '✅') === 0 ? 'exito' : 'error';
                    ?>
                    <p class="<?= $claseMensaje ?>"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
                <?php endif; ?>

                <form method="POST" action="../../backend/php/archivos/registrar_archivo.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Nombre de Documento:</label>

                            <!-- Campo visible (NO editable) -->
                            <input
                                type="text"
                                id="nombre_documento"
                                name="numero"
                                readonly
                                style="background:#e9ecef; font-weight:bold;"
                                value="<?= htmlspecialchars($valor_automatico) ?>">

                            <!-- Campo donde solo se escribe el número -->
                            <input
                                type="number"
                                id="solo_numero"
                                placeholder="Ingrese solo número (máx. 4 dígitos)"
                                min="1"
                                max="9999"
                                maxlength="4"
                                style="margin-top:5px;">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-flag"></i> Estado:</label>
                            <input type="text" value="Nuevo" readonly disabled style="background-color: #a19f9fff; color: #555;">
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

                        <?php if ($rol_id === 1 || $rol_id === 99): ?>
                            <div class="form-group">
                                <label><i class="fas fa-external-link-alt"></i> ¿Es exterior?</label>
                                <?php if ($rol_id === 1 || $rol_id === 99): ?>
                                    <input type="hidden" id="campoExterior" name="exterior" value="NO">
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

                        <?php if ($rol_id === 1 || $rol_id === 99): ?>
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> DNI o RUC del contribuyente:</label>
                                <input
                                    type="text"
                                    name="dni_ruc"
                                    id="dni_ruc"
                                    placeholder="Ingrese DNI, RUC o Extranjería(MANUAL)"
                                    pattern="\d{8}|\d{11}|\d{12,}"
                                    title="Debe ser DNI (8), RUC (11) o código especial (12 dígitos o más)">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nombre del contribuyente:</label>
                                <input
                                    type="text"
                                    name="nombre_contribuyente"
                                    id="nombre_contribuyente"
                                    placeholder="Ingrese nombres y apellidos o razón social"
                                    readonly
                                    style="background-color: #a19f9fff; color: #555;">
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const dniInput = document.getElementById('dni_ruc');
                                    const nombreInput = document.getElementById('nombre_contribuyente');

                                    dniInput.addEventListener('input', function() {
                                        const value = this.value.trim();

                                        if (value.length === 8 || value.length === 11) {
                                            nombreInput.readOnly = true;
                                            nombreInput.style.backgroundColor = '#a19f9fff';
                                            nombreInput.style.color = '#555';
                                        } else if (value.length >= 12) {
                                            nombreInput.readOnly = false;
                                            nombreInput.style.backgroundColor = '';
                                            nombreInput.style.color = '';
                                        } else {
                                            nombreInput.readOnly = true;
                                            nombreInput.style.backgroundColor = '#a19f9fff';
                                            nombreInput.style.color = '#555';
                                            nombreInput.value = '';
                                        }
                                    });
                                });
                            </script>
                        <?php endif; ?>

                        <div class="form-group">
                            <label><i class="fas fa-copy"></i> Número de folios:</label>
                            <input type="number" name="numero_folios" min="1" max="99999" placeholder="Ej: 3" oninput="this.value = this.value.slice(0, 5)">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-copy"></i> ¿Trae objeto?</label>
                            <select name="tipo_objeto" required>
                                <option value="">--SELECCIONE TIPO--</option>
                                <?php foreach ($tipos_objeto as $tipo) : ?>
                                    <option value="<?= htmlspecialchars($tipo['IdTipoObjeto']) ?>"><?= htmlspecialchars($tipo['Descripcion']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Asunto:</label>
                            <textarea name="asunto" required placeholder="Describa el asunto del documento..." rows="4"></textarea>
                        </div>
                    </div>

                    <button type="submit">
                        <i class="fas fa-rocket"></i> Registrar y Enviar
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
    <script src="../../backend/js/archivos/buscar_contribuyente.js"></script>
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
        document.addEventListener("DOMContentLoaded", () => {

            const inputVisible = document.getElementById("nombre_documento");
            const inputNumero = document.getElementById("solo_numero");

            const prefijo = "<?= $prefijo_base ?>";
            const sufijo = "<?= $sufijo_dinamico ?>";

            inputNumero.addEventListener("input", function() {

                // 🔒 Limitar a 3 dígitos
                this.value = this.value.slice(0, 4);

                const numero = this.value;
                inputVisible.value = prefijo + numero + sufijo;
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