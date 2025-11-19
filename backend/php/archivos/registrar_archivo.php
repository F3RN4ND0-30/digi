<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header("Location: ../login.php");
    exit();
}

// Asegúrate de que las rutas a estos archivos sean correctas
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

    // Iniciar Transacción
    // Esto asegura que si alguna de las inserciones falla (documento, informe, movimiento), todas se revierten.
    $pdo->beginTransaction();

    try {
        $numero = trim($_POST['numero']);
        $asunto = trim($_POST['asunto']);
        $dni_ruc = trim($_POST['dni_ruc'] ?? '');
        $nombre_contribuyente = trim($_POST['nombre_contribuyente'] ?? '');
        $numero_folios = intval($_POST['numero_folios']);
        $estado_id = 1;
        $area_destino = $_POST['area_destino'] ?? null;
        $tipo_objeto = $_POST['tipo_objeto'] ?? null;  // Nuevo campo para tipo de objeto

        // Valores por defecto
        $exterior_bool = 0;
        $area_final = $area_destino;

        // Lógica de roles
        if ($rol_id === 1 || $rol_id === 3) {
            $exterior = strtoupper(trim($_POST['exterior'] ?? 'NO'));
            $area_final = $area_id;
            $exterior_bool = ($exterior === 'SI') ? 1 : 0;
        } else {
            $dni_ruc = "0";
            $nombre_contribuyente = "No correspondiente";
        }

        // --- Validaciones (manteniendo tu lógica) ---
        if (empty($area_destino)) {
            $_SESSION['mensaje'] = "❌ Debe seleccionar un área de destino.";
            throw new Exception("Validation Error");
        }
        if (($rol_id === 1 || $rol_id === 3) && (empty($dni_ruc) || (!preg_match('/^\d{8}$|^\d{11}$|^\d{12,}$/', $dni_ruc)))) {
            $_SESSION['mensaje'] = "❌ El número ingresado no es válido. Debe ser DNI (8), RUC (11) o mayor a 11 dígitos para casos de extranjeria.";
            throw new Exception("Validation Error");
        }
        if (($rol_id === 1 || $rol_id === 3) && empty($nombre_contribuyente)) {
            $_SESSION['mensaje'] = "❌ El nombre del contribuyente está vacío.";
            throw new Exception("Validation Error");
        }
        if ($numero_folios <= 0) {
            $_SESSION['mensaje'] = "❌ El número de folios debe ser mayor a cero.";
            throw new Exception("Validation Error");
        }

        // Verificar si ya existe ese número de documento
        $check = $pdo->prepare("SELECT IdDocumentos FROM documentos WHERE NumeroDocumento = ?");
        $check->execute([$numero]);
        if ($check->rowCount() > 0) {
            $_SESSION['mensaje'] = "❌ Ya existe un documento con ese número.";
            throw new Exception("Validation Error");
        }
        // --- Fin Validaciones ---

        // 1. INSERTAR DOCUMENTO
        $stmt = $pdo->prepare("INSERT INTO documentos 
            (NumeroDocumento, Asunto, DniRuc, NombreContribuyente, NumeroFolios, IdEstadoDocumento, IdUsuarios, IdAreas, Exterior, IdAreaFinal, Finalizado, IdTipoObjeto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
            0, // No finalizado
            $tipo_objeto
        ]);

        if (!$insert_ok) {
            throw new Exception("Error al insertar el documento principal.");
        }

        $idDocumentoNuevo = $pdo->lastInsertId();

        // =================================================================
        // 2. LÓGICA: REGISTRAR EN LA TABLA 'INFORMES' (Si el prefijo coincide)
        // =================================================================
        $prefijo_informe = 'INFORME N°.';
        $es_informe = strpos($numero, $prefijo_informe) === 0;

        if ($es_informe) {
            $año_actual = date('Y');

            // a) Calcular el nuevo correlativo para el área y año
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM informes WHERE IdArea = ? AND Año = ?");
            $stmt_count->execute([$area_id, $año_actual]);
            $total_informes_area = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            $correlativo = $total_informes_area + 1;

            // b) Insertar el registro en la tabla informes
            $stmt_informe = $pdo->prepare("
                INSERT INTO informes (NombreInforme, IdDocumento, IdMemo, IdArea, FechaEmision, Año, Asunto, Correlativo)
                VALUES (?, ?, NULL, ?, NOW(), ?, ?, ?)
            ");

            $stmt_informe->execute([
                $numero,           // NombreInforme
                $idDocumentoNuevo, // IdDocumento
                $area_id,          // IdArea
                $año_actual,       // Año
                $asunto,           // Asunto
                $correlativo       // Correlativo
            ]);
        }
        // =================================================================

        // 3. INSERTAR MOVIMIENTO
        $mov = $pdo->prepare("INSERT INTO movimientodocumento (IdDocumentos, AreaOrigen, AreaDestino, Recibido, Observacion, NumeroFolios)
                              VALUES (?, ?, ?, 0, '', ?)");
        $mov->execute([$idDocumentoNuevo, $area_id, $area_destino, $numero_folios]);

        // 4. CREAR NOTIFICACIÓN
        $mensaje_notificacion = "Nuevo documento recibido: N° $numero - '$asunto' desde $areaOrigenNombre";
        crearNotificacion($pdo, $area_destino, $mensaje_notificacion);

        // 5. CONFIRMAR TRANSACCIÓN
        $pdo->commit();
        $_SESSION['mensaje'] = "✅ Documento registrado y enviado al área destino.";
    } catch (Exception $e) {
        // Revertir Transacción si algo falló
        $pdo->rollBack();

        // Si no es un error de validación, establecer un mensaje genérico de error
        if ($e->getMessage() !== "Validation Error") {
            $_SESSION['mensaje'] = "❌ Error en el proceso de registro: " . $e->getMessage();
        }
    }

    // Redirección final
    header("Location: ../../../frontend/archivos/registrar.php");
    exit();
}
