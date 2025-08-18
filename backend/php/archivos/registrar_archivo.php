<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

require '../../db/conexion.php';

if (!isset($_SESSION['id'])) {
    die("❌ Error: No se encontró el ID del usuario en la sesión.");
}

$usuario_id = $_SESSION['id'];

// Obtener área del usuario
$consulta = $pdo->prepare("SELECT IdAreas FROM usuarios WHERE IdUsuarios = ?");
$consulta->execute([$usuario_id]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("❌ Error: no se encontró el usuario o no tiene un área asignada.");
}

$area_id = $usuario['IdAreas'];

// Obtener estados desde la tabla estadodocumento
$estados = $pdo->query("SELECT IdEstadoDocumento, Estado FROM estadodocumento")->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las áreas (para elegir el destino)
$areas = $pdo->query("SELECT IdAreas, Nombre FROM areas")->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero']);
    $asunto = trim($_POST['asunto']);
    $estado_id = $_POST['estado'];
    $area_destino = $_POST['area_destino'];

    // Validaciones básicas
    if (empty($area_destino)) {
        $mensaje = "❌ Debe seleccionar un área de destino.";
    } else {
        // Verificar si el número ya existe
        $check = $pdo->prepare("SELECT IdDocumentos FROM documentos WHERE NumeroDocumento = ?");
        $check->execute([$numero]);

        if ($check->rowCount() > 0) {
            $mensaje = "❌ Ya existe un documento con ese número.";
        } else {
            // Insertar nuevo documento (el área creadora sigue siendo la actual)
            $stmt = $pdo->prepare("INSERT INTO documentos (NumeroDocumento, Asunto, IdEstadoDocumento, IdUsuarios, IdAreas) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$numero, $asunto, $estado_id, $usuario_id, $area_id])) {

                // Insertar movimiento hacia el área destino seleccionada
                $idDocumentoNuevo = $pdo->lastInsertId();

                $mov = $pdo->prepare("INSERT INTO movimientodocumento (IdDocumentos, AreaOrigen, AreaDestino, Recibido, Observacion)
                                      VALUES (?, ?, ?, 0, '')");
                $mov->execute([$idDocumentoNuevo, $area_id, $area_destino]);

                $mensaje = "✅ Documento registrado y enviado al área destino.";
            } else {
                $mensaje = "❌ Error al registrar el documento.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registrar Documento</title>
</head>

<body>
    <h2>Registrar nuevo documento</h2>

    <?php if (isset($mensaje)) : ?>
        <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Número de Documento:</label><br>
        <input type="text" name="numero" required><br><br>

        <label>Asunto:</label><br>
        <textarea name="asunto" required></textarea><br><br>

        <label>Estado:</label><br>
        <select name="estado" required>
            <option value="">Seleccione un estado</option>
            <?php foreach ($estados as $estado) : ?>
                <option value="<?= $estado['IdEstadoDocumento'] ?>"><?= htmlspecialchars($estado['Estado']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Área de destino:</label><br>
        <select name="area_destino" required>
            <option value="">Seleccione un área</option>
            <?php foreach ($areas as $area) : ?>
                <?php if ($area['IdAreas'] != $area_id) : // No mostrar su propia área 
                ?>
                    <option value="<?= $area['IdAreas'] ?>"><?= htmlspecialchars($area['Nombre']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Registrar y Enviar</button>
    </form>

    <p><a href="/digi/frontend/sisvis/escritorio.php">← Volver al escritorio</a></p>
</body>

</html>