<?php
// dashboard.php
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
    <title>Escritorio</title>
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css" />

    <!-- Selectize CSS -->
    <link href="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/css/selectize.default.min.css" rel="stylesheet" />

    <!-- jQuery (requerido por Selectize) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Selectize JS -->
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.15.2/dist/js/selectize.min.js"></script>
</head>

<body>
    <div class="layout-escritorio">

        <aside class="sidebar">
            <h2>DIGI - MPP</h2>
            <nav>
                <a href="../sisvis/escritorio.php">🏠 Inicio</a>
                <a href="../archivos/recepcion.php">📊 recepción</a>
                <a href="../archivos/enviados.php">📤 Enviados</a>
                <a href="#">⚙️ Configuración</a>
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
                <h2>Registrar nuevo documento</h2>

                <?php if (isset($mensaje)) : ?>
                    <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
                <?php endif; ?>

                <form method="POST" action="../../backend/php/archivos/registrar_archivo.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Número de Documento:</label>
                            <input type="text" name="numero" required>
                        </div>

                        <div class="form-group">
                            <label>Estado:</label>
                            <select name="estado" required>
                                <option value="">Seleccione un estado</option>
                                <?php foreach ($estados as $estado) : ?>
                                    <option value="<?= $estado['IdEstadoDocumento'] ?>"><?= htmlspecialchars($estado['Estado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Área de destino:</label>
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
                            <label>Observación:</label>
                            <textarea name="observacion" rows="3" placeholder="Escriba alguna observación opcional..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label>Asunto:</label>
                            <textarea name="asunto" required></textarea>
                        </div>
                    </div>

                    <button type="submit">Registrar y Enviar</button>
                </form>
            </div>
        </main>
    </div>

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
</body>

</html>