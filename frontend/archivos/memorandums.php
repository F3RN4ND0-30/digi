<?php
// memorandums.php (MÓDULO MEMORÁNDUM)
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
$area_id    = $_SESSION['dg_area_id'] ?? null;

// Info del área emisora (Nombre + Abreviatura como prefijo de memo)
$areaInfo = null;
if ($area_id) {
    $stmt = $pdo->prepare("SELECT Nombre, Abreviatura FROM areas WHERE IdAreas = ?");
    $stmt->execute([$area_id]);
    $areaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Todas las áreas para elegir destinos
$areasStmt = $pdo->prepare("SELECT IdAreas, Nombre FROM areas ORDER BY Nombre");
$areasStmt->execute();
$areas = $areasStmt->fetchAll(PDO::FETCH_ASSOC);

// Mensaje de operación
$mensaje = $_SESSION['mensaje_memo'] ?? '';
unset($_SESSION['mensaje_memo']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registrar Memorándum - DIGI MPP</title>

    <!-- Fuentes y estilos generales -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- Reutilizamos el mismo estilo del formulario de registrar -->
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />

    <!-- Selectize -->
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
</head>

<body>
    <div class="layout-escritorio">
        <!-- Navbar -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta tarjeta-formulario">
                <h2><i class="fas fa-file-signature"></i> Registrar nuevo memorándum</h2>

                <?php if (!empty($mensaje)): ?>
                    <?php $claseMensaje = strpos($mensaje, '✅') === 0 ? 'exito' : 'error'; ?>
                    <p class="<?= $claseMensaje ?>"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
                <?php endif; ?>

                <form method="POST" action="../../backend/php/memorandum/guardar_memo.php">
                    <div class="form-grid">

                        <!-- TIPO DE MEMORÁNDUM -->
                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Tipo de memorándum:</label>
                            <select name="tipo_memo" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="CIRCULAR">MEMORÁNDUM CIRCULAR</option>
                                <option value="MULTIPLE">MEMORÁNDUM MÚLTIPLE</option>
                            </select>
                        </div>

                        <!-- CÓDIGO DE MEMORÁNDUM (SOLO VISTA PREVIA) -->
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Código de Memorándum (referencial):</label>
                            <input type="text"
                                value="<?php
                                        $anio = date('Y');
                                        if ($areaInfo && !empty($areaInfo['Abreviatura'])) {
                                            // Ejemplos:
                                            //  MEMORÁNDUM MÚLTIPLE N° 001-2025-MPP-OGAF-US
                                            //  MEMORÁNDUM CIRCULAR N° 043-2025-OGS
                                            echo htmlspecialchars('XXX-' . $anio . '-' . $areaInfo['Abreviatura'] . '-MEMO');
                                        } else {
                                            echo 'Se generará automáticamente al guardar';
                                        }
                                        ?>"
                                readonly
                                style="background-color: #a19f9fff; color: #555;">
                            <small style="font-size:0.8rem;color:#666;">
                                El número exacto se generará automáticamente por área y año.
                            </small>
                        </div>

                        <!-- ÁREA EMISORA -->
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Área emisora:</label>
                            <input type="text"
                                value="<?= $areaInfo ? htmlspecialchars($areaInfo['Nombre']) : 'No asignada' ?>"
                                readonly
                                style="background-color: #a19f9fff; color: #555;">
                            <input type="hidden" name="area_emisora" value="<?= (int)$area_id ?>">
                        </div>

                        <!-- ÁREAS DESTINO (MÚLTIPLE) -->
                        <div class="form-group">
                            <label><i class="fas fa-share-nodes"></i> Áreas de destino:</label>
                            <select name="areas_destino[]" id="areas_destino" multiple required>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?= $a['IdAreas'] ?>">
                                        <?= htmlspecialchars($a['Nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="font-size:0.8rem;color:#666;">Puede seleccionar una o varias áreas.</small>
                        </div>

                        <!-- NÚMERO DE FOLIOS (TOTAL DEL MEMO + ANEXOS) -->
                        <div class="form-group">
                            <label><i class="fas fa-copy"></i> Número de folios:</label>
                            <input type="number" name="numero_folios" min="1" placeholder="Ej: 3">
                        </div>

                        <!-- ASUNTO -->
                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Asunto:</label>
                            <input type="text" name="asunto" required placeholder="Asunto principal del memorándum">
                        </div>
                                    
                        <!-- (SIN CUERPO: SOLO CABECERA) -->
                    </div>

                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Guardar y enviar
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Notificaciones -->
    <script src="../../backend/js/notificaciones.js"></script>

    <script>
        // Forzar mayúsculas en texto
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], textarea');

            inputs.forEach(function(element) {
                element.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });
        });

        // Selectize para el select múltiple de áreas destino
        $(document).ready(function() {
            $('#areas_destino').selectize({
                plugins: ['remove_button'],
                placeholder: 'Seleccione una o varias áreas',
                sortField: 'text',
                create: false
            });
        });
    </script>
</body>

</html>