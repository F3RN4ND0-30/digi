<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';

$usuario_id = $_SESSION['dg_id'];

// Obtener área y rol del usuario
$consulta = $pdo->prepare("SELECT IdAreas, IdRol FROM usuarios WHERE IdUsuarios = ?");
$consulta->execute([$usuario_id]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("❌ Error: no se encontró el usuario o no tiene un área asignada.");
}

$area_id = $usuario['IdAreas'] ?? null;
$rol_id = (int)($usuario['IdRol'] ?? 0);

if ($area_id === null) {
    die("❌ Error: El usuario no tiene un área asignada.");
}

// 🟡 Obtener nombre del área origen para notificación
$stmtOrigen = $pdo->prepare("SELECT Nombre FROM areas WHERE IdAreas = ?");
$stmtOrigen->execute([$area_id]);
$areaOrigenNombre = $stmtOrigen->fetchColumn();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero']);
    $asunto = trim($_POST['asunto']);
    $estado_id = 1;
    $area_destino = $_POST['area_destino'] ?? null;

    // Por defecto
    $exterior_bool = 0;
    $area_final = $area_id;

    // Si el usuario tiene rol 1 (Admin) o 3 (Mesa de Entrada)
    if ($rol_id === 1 || $rol_id === 3) {
        $exterior = strtoupper(trim($_POST['exterior'] ?? 'NO'));
        $area_final = $_POST['area_final'] ?? null;
        $exterior_bool = ($exterior === 'SI') ? 1 : 0;
    }

    // Validaciones
    if (empty($area_destino)) {
        $_SESSION['mensaje'] = "❌ Debe seleccionar un área de destino.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    if (($rol_id === 1 || $rol_id === 3) && empty($area_final)) {
        $_SESSION['mensaje'] = "❌ Debe seleccionar un área final.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    // Verificar si el número ya existe
    $check = $pdo->prepare("SELECT IdDocumentos FROM documentos WHERE NumeroDocumento = ?");
    $check->execute([$numero]);

    if ($check->rowCount() > 0) {
        $_SESSION['mensaje'] = "❌ Ya existe un documento con ese número.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    // Insertar nuevo documento
    $stmt = $pdo->prepare("INSERT INTO documentos 
    (NumeroDocumento, Asunto, IdEstadoDocumento, IdUsuarios, IdAreas, Exterior, IdAreaFinal, Finalizado) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $insert_ok = $stmt->execute([
        $numero,
        $asunto,
        $estado_id,
        $usuario_id,
        $area_id,
        $exterior_bool,
        $area_final,
        0 // Finalizado
    ]);


    if ($insert_ok) {
        $idDocumentoNuevo = $pdo->lastInsertId();

        // Insertar movimiento
        $mov = $pdo->prepare("INSERT INTO movimientodocumento (IdDocumentos, AreaOrigen, AreaDestino, Recibido, Observacion)
                              VALUES (?, ?, ?, 0, '')");
        $mov->execute([$idDocumentoNuevo, $area_id, $area_destino]);

        // Crear notificación
        $mensaje = "Nuevo documento recibido: N° $numero - '$asunto' desde $areaOrigenNombre";
        crearNotificacion($pdo, $area_destino, $mensaje);

        $_SESSION['mensaje'] = "✅ Documento registrado y enviado al área destino.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "❌ Error al registrar el documento.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }
}
