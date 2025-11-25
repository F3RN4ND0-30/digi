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

// Info del área emisora
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

// Mensaje flash
$mensaje = $_SESSION['mensaje_memo'] ?? '';
unset($_SESSION['mensaje_memo']);

// Base del código (sin el texto de tipo)
$anio = date('Y');
if ($areaInfo && !empty($areaInfo['Abreviatura'])) {
    $baseCodigo = 'N° XXX-' . $anio . '-' . $areaInfo['Abreviatura'];
} else {
    $baseCodigo = 'Se generará automáticamente al guardar';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registrar Memorándum - DIGI MPP</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />

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
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta tarjeta-formulario">
                <h2><i class="fas fa-file-signature"></i> Registrar nuevo memorándum</h2>

                <?php if (!empty($mensaje)): ?>
                    <?php $claseMensaje = strpos($mensaje, '✅') === 0 ? 'exito' : 'error'; ?>
                    <p class="<?= $claseMensaje ?>"><strong><?= htmlspecialchars($mensaje) ?></strong></p>
                <?php endif; ?>

                <form id="formMemo" method="POST" action="../../backend/php/memorandum/guardar_memo.php">
                    <div class="form-grid">

                        <!-- TIPO -->
                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Tipo de memorándum:</label>
                            <select name="tipo_memo" id="tipo_memo" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="CIRCULAR">MEMORÁNDUM CIRCULAR</option>
                                <option value="MULTIPLE">MEMORÁNDUM MÚLTIPLE</option>
                            </select>
                        </div>

                        <!-- CÓDIGO REFERENCIAL -->
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Código de Memorándum (referencial):</label>
                            <input
                                type="text"
                                id="codigo_preview"
                                value="<?= htmlspecialchars($baseCodigo) ?>"
                                data-base="<?= htmlspecialchars($baseCodigo) ?>"
                                readonly
                                style="background-color:#a19f9fff;color:#555;">
                            <small style="font-size:.8rem;color:#666;">
                                El número correlativo real se generará por área y año.
                            </small>
                        </div>

                        <!-- ÁREA EMISORA -->
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Área emisora:</label>
                            <input type="text"
                                value="<?= $areaInfo ? htmlspecialchars($areaInfo['Nombre']) : 'No asignada' ?>"
                                readonly
                                style="background-color:#a19f9fff;color:#555;">
                            <input type="hidden" name="area_emisora" value="<?= (int)$area_id ?>">
                        </div>

                        <!-- ÁREAS DESTINO -->
                        <div class="form-group">
                            <label><i class="fas fa-share-nodes"></i> Áreas de destino:</label>
                            <select name="areas_destino[]" id="areas_destino" multiple required>
                                <?php foreach ($areas as $a): ?>
                                    <?php if ((int)$a['IdAreas'] !== (int)$area_id): ?>
                                        <option value="<?= $a['IdAreas'] ?>"><?= htmlspecialchars($a['Nombre']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small style="font-size:.8rem;color:#666;">Puede seleccionar una o varias áreas.</small>
                        </div>

                        <!-- FOLIOS (OPCIONAL, con checkbox) -->
                        <div class="form-group">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.35rem;">
                                <span style="font-weight:600;color:#334155;display:flex;align-items:center;gap:.5rem;">
                                    <i class="fas fa-copy"></i> Número de folios (Opcional)
                                </span>
                                <label style="display:inline-flex;align-items:center;gap:.4rem;cursor:pointer;user-select:none;">
                                    <input type="checkbox" id="chk_usa_folios" />
                                    <span style="font-size:.85rem;font-weight:600;color:#334155;">Agregar</span>
                                </label>
                            </div>

                            <input
                                type="number"
                                name="numero_folios"
                                id="numero_folios"
                                min="1"
                                placeholder="Ej: 3"
                                disabled
                                style="width:100%;padding:.6rem .75rem;border:1px solid #e2e8f0;border-radius:8px;background:#f1f5f9;color:#64748b;">
                            <small style="font-size:.8rem;color:#64748b;display:block;margin-top:.25rem;">
                                Si no activas “Agregar”, se guardará 0 folios.
                            </small>
                        </div>

                        <!-- ASUNTO -->
                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Asunto:</label>
                            <input type="text" name="asunto" required placeholder="Asunto principal del memorándum">
                        </div>

                        <!-- Tipo de objeto fijo SIN OBJETO (id=3) -->
                        <input type="hidden" name="tipo_objeto" value="3">

                    </div>

                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Guardar y enviar
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="../../backend/js/notificaciones.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mayúsculas en texto
            document.querySelectorAll('input[type="text"]').forEach(el => {
                el.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });

            // Selectize destinos
            $('#areas_destino').selectize({
                plugins: ['remove_button'],
                placeholder: 'Seleccione una o varias áreas',
                sortField: 'text',
                create: false
            });

            // Código referencial dinámico (solo visual)
            const tipoSelect = document.getElementById('tipo_memo');
            const codigoInput = document.getElementById('codigo_preview');
            const baseCodigo = codigoInput ? codigoInput.dataset.base : '';

            function actualizarCodigo() {
                if (!codigoInput) return;
                const tipo = tipoSelect.value;

                if (!tipo || !baseCodigo || baseCodigo.indexOf('Se generará') === 0) {
                    codigoInput.value = baseCodigo;
                    return;
                }
                let prefijo = (tipo === 'MULTIPLE') ?
                    'MEMORÁNDUM MÚLTIPLE ' :
                    'MEMORÁNDUM CIRCULAR ';
                codigoInput.value = prefijo + baseCodigo;
            }
            if (tipoSelect && codigoInput) {
                tipoSelect.addEventListener('change', actualizarCodigo);
                actualizarCodigo();
            }

            // Folios opcional
            const chkFolios = document.getElementById('chk_usa_folios');
            const inpFolios = document.getElementById('numero_folios');

            function toggleFolios() {
                const on = chkFolios.checked;
                inpFolios.disabled = !on;
                inpFolios.required = on;
                inpFolios.style.background = on ? '#ffffff' : '#f1f5f9';
                inpFolios.style.color = on ? '#111827' : '#64748b';
                if (!on) inpFolios.value = '';
            }
            chkFolios.addEventListener('change', toggleFolios);
            toggleFolios();

            // Validación suave en submit: si activan folios, exigir >=1
            document.getElementById('formMemo').addEventListener('submit', function(e) {
                if (chkFolios.checked) {
                    const v = (inpFolios.value || '').trim();
                    if (v === '' || Number(v) < 1) {
                        e.preventDefault();
                        alert('Ingrese un número de folios válido (>= 1) o desactive “Agregar”.');
                    }
                }
            });
        });
    </script>
</body>

</html>