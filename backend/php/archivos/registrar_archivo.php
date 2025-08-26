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

// Obtener nombre del área origen
$stmtOrigen = $pdo->prepare("SELECT Nombre FROM areas WHERE IdAreas = ?");
$stmtOrigen->execute([$area_id]);
$areaOrigenNombre = $stmtOrigen->fetchColumn();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero']);
    $asunto = trim($_POST['asunto']);
    $dni_ruc = trim($_POST['dni_ruc']);
    $nombre_contribuyente = trim($_POST['nombre_contribuyente']);
    $numero_folios = intval($_POST['numero_folios']);
    $estado_id = 1;
    $area_destino = $_POST['area_destino'] ?? null;

    // Valores por defecto
    $exterior_bool = 0;
    $area_final = $area_id;

    // Solo para ADMIN o MESA DE ENTRADA
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

    // Validar número de documento y nombre del contribuyente
    if (empty($dni_ruc) || (!preg_match('/^\d{8}$|^\d{11}$|^\d{12,}$/', $dni_ruc))) {
        $_SESSION['mensaje'] = "❌ El número ingresado no es válido. Debe ser DNI (8), RUC (11) o mayor a 11 dígitos para casos de extranjeria.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    if (empty($nombre_contribuyente)) {
        $_SESSION['mensaje'] = "❌ El nombre del contribuyente está vacío. Verifique que el campo se haya completado automáticamente.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    if ($numero_folios <= 0) {
        $_SESSION['mensaje'] = "❌ El número de folios debe ser mayor a cero.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    // Verificar si ya existe ese número de documento
    $check = $pdo->prepare("SELECT IdDocumentos FROM documentos WHERE NumeroDocumento = ?");
    $check->execute([$numero]);
    if ($check->rowCount() > 0) {
        $_SESSION['mensaje'] = "❌ Ya existe un documento con ese número.";
        header("Location: ../../../frontend/archivos/registrar.php");
        exit();
    }

    // Insertar documento
    $stmt = $pdo->prepare("INSERT INTO documentos 
        (NumeroDocumento, Asunto, DniRuc, NombreContribuyente, NumeroFolios, IdEstadoDocumento, IdUsuarios, IdAreas, Exterior, IdAreaFinal, Finalizado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $insert_ok = $stmt->execute([
        $numero,
        $asunto,
        $dni_ruc,
        $nombre_contribuyente,
        $numero_folios,
        $estado_id,
        $usuario_id,
        $area_id,
        $exterior_bool,
        $area_final,
        0 // No finalizado
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
    } else {
        $_SESSION['mensaje'] = "❌ Error al registrar el documento.";
    }

    header("Location: ../../../frontend/archivos/registrar.php");
    exit();
}
