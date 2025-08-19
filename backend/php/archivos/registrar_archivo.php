<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../../db/conexion.php';
require_once '../util/notificaciones_util.php';


// Ya sabemos que dg_id existe, pero chequeamos de nuevo para evitar errores
if (!isset($_SESSION['dg_id'])) {
    die("❌ Error: No se encontró el ID del usuario en la sesión.");
}

$usuario_id = $_SESSION['dg_id'];

// Obtener área del usuario
$consulta = $pdo->prepare("SELECT IdAreas FROM usuarios WHERE IdUsuarios = ?");
$consulta->execute([$usuario_id]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("❌ Error: no se encontró el usuario o no tiene un área asignada.");
}

$area_id = $usuario['IdAreas'] ?? null;

if ($area_id === null) {
    die("❌ Error: El usuario no tiene un área asignada.");
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = trim($_POST['numero']);
    $asunto = trim($_POST['asunto']);
    $estado_id = $_POST['estado'] ?? null;
    $area_destino = $_POST['area_destino'] ?? null;
    $observacion = trim($_POST['observacion'] ?? '');

    // Validaciones básicas
    if (empty($area_destino)) {
        $_SESSION['mensaje'] = "❌ Debe seleccionar un área de destino.";
        header("Location: ../../../frontend/sisvis/escritorio.php");
        exit();
    } else {
        // Verificar si el número ya existe
        $check = $pdo->prepare("SELECT IdDocumentos FROM documentos WHERE NumeroDocumento = ?");
        $check->execute([$numero]);

        if ($check->rowCount() > 0) {
            $_SESSION['mensaje'] = "❌ Ya existe un documento con ese número.";
            header("Location: ../../../frontend/sisvis/escritorio.php");
            exit();
        } else {
            // Insertar nuevo documento
            $stmt = $pdo->prepare("INSERT INTO documentos (NumeroDocumento, Asunto, IdEstadoDocumento, IdUsuarios, IdAreas) VALUES (?, ?, ?, ?, ?)");
            $insert_ok = $stmt->execute([$numero, $asunto, $estado_id, $usuario_id, $area_id]);

            if ($insert_ok) {
                // Insertar movimiento
                $idDocumentoNuevo = $pdo->lastInsertId();

                $mov = $pdo->prepare("INSERT INTO movimientodocumento (IdDocumentos, AreaOrigen, AreaDestino, Recibido, Observacion)
                      VALUES (?, ?, ?, 0, ?)");
                $mov->execute([$idDocumentoNuevo, $area_id, $area_destino, $observacion]);

                $mensaje = "Nuevo documento recibido: N° $numero - '$asunto' desde $areaOrigenNombre";
                crearNotificacion($pdo, $area_destino, $mensaje);


                $_SESSION['mensaje'] = "✅ Documento registrado y enviado al área destino.";
                header("Location: ../../../frontend/sisvis/escritorio.php");
                exit();
            } else {
                $_SESSION['mensaje'] = "❌ Error al registrar el documento.";
                header("Location: ../../../frontend/sisvis/escritorio.php");
                exit();
            }
        }
    }
}
