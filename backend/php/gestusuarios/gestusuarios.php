<?php
session_start();
require '../../db/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['dg_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if (($_SESSION['dg_rol'] ?? 999) != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            $dni = trim($_POST['dni'] ?? '');
            $usuario = trim($_POST['usuario'] ?? '');
            $password = $_POST['password'] ?? '';
            $nombres = trim($_POST['nombres'] ?? '');
            $apellidoPat = trim($_POST['apellidoPat'] ?? '');
            $apellidoMat = trim($_POST['apellidoMat'] ?? '');
            $area = (int)($_POST['area'] ?? 0);
            $rol = (int)($_POST['rol'] ?? 0);
            $estado = 1; // Siempre activo por defecto

            // Validaciones básicas
            if (empty($usuario) || empty($password) || empty($nombres) || empty($apellidoPat) || !$area || !$rol) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben estar completos']);
                exit;
            }

            if (strlen($usuario) < 3) {
                echo json_encode(['success' => false, 'message' => 'El usuario debe tener al menos 3 caracteres']);
                exit;
            }

            if (strlen($password) < 4) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres']);
                exit;
            }

            // Validar DNI si se proporciona
            if (!empty($dni)) {
                if (!preg_match('/^\d{8}$/', $dni)) {
                    echo json_encode(['success' => false, 'message' => 'El DNI debe tener exactamente 8 dígitos']);
                    exit;
                }

                // Verificar que el DNI no esté en uso
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Dni = ?");
                $stmt->execute([$dni]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'El DNI ya está registrado']);
                    exit;
                }
            }

            // Verificar que el usuario no exista
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'El usuario ya existe']);
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar con DNI
            $stmt = $pdo->prepare("INSERT INTO usuarios (Dni, Usuario, Clave, Nombres, ApellidoPat, ApellidoMat, IdAreas, IdRol, Estado) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dni, $usuario, $passwordHash, $nombres, $apellidoPat, $apellidoMat, $area, $rol, $estado]);

            echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
            break;

        case 'editar':
            $id = (int)($_POST['userId'] ?? 0);
            $dni = trim($_POST['dni'] ?? '');
            $usuario = trim($_POST['usuario'] ?? '');
            $password = $_POST['password'] ?? '';
            $nombres = trim($_POST['nombres'] ?? '');
            $apellidoPat = trim($_POST['apellidoPat'] ?? '');
            $apellidoMat = trim($_POST['apellidoMat'] ?? '');
            $area = (int)($_POST['area'] ?? 0);
            $rol = (int)($_POST['rol'] ?? 0);

            if (!$id || empty($usuario) || empty($nombres) || empty($apellidoPat) || !$area || !$rol) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben estar completos']);
                exit;
            }

            if (strlen($usuario) < 3) {
                echo json_encode(['success' => false, 'message' => 'El usuario debe tener al menos 3 caracteres']);
                exit;
            }

            // Validar DNI si se proporciona
            if (!empty($dni)) {
                if (!preg_match('/^\d{8}$/', $dni)) {
                    echo json_encode(['success' => false, 'message' => 'El DNI debe tener exactamente 8 dígitos']);
                    exit;
                }

                // Verificar que el DNI no esté en uso por otro usuario
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Dni = ? AND IdUsuarios != ?");
                $stmt->execute([$dni, $id]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'El DNI ya está registrado por otro usuario']);
                    exit;
                }
            }

            // Verificar que el usuario no exista (excepto el actual)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE Usuario = ? AND IdUsuarios != ?");
            $stmt->execute([$usuario, $id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'El usuario ya existe']);
                exit;
            }

            // Actualizar con o sin contraseña
            if (!empty($password)) {
                if (strlen($password) < 4) {
                    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres']);
                    exit;
                }
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET Dni=?, Usuario=?, Clave=?, Nombres=?, ApellidoPat=?, ApellidoMat=?, IdAreas=?, IdRol=? WHERE IdUsuarios=?");
                $stmt->execute([$dni, $usuario, $passwordHash, $nombres, $apellidoPat, $apellidoMat, $area, $rol, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET Dni=?, Usuario=?, Nombres=?, ApellidoPat=?, ApellidoMat=?, IdAreas=?, IdRol=? WHERE IdUsuarios=?");
                $stmt->execute([$dni, $usuario, $nombres, $apellidoPat, $apellidoMat, $area, $rol, $id]);
            }

            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
            break;

        case 'cambiar_estado':
            $id = (int)($_POST['id'] ?? 0);
            $estado = (int)($_POST['estado'] ?? 1);

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
                exit;
            }

            if ($id == $_SESSION['dg_id']) {
                echo json_encode(['success' => false, 'message' => 'No puede cambiar el estado de su propio usuario']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET Estado = ? WHERE IdUsuarios = ?");
            $stmt->execute([$estado, $id]);

            $mensaje = $estado == 1 ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente';
            echo json_encode(['success' => true, 'message' => $mensaje]);
            break;

        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
                exit;
            }

            if ($id == $_SESSION['dg_id']) {
                echo json_encode(['success' => false, 'message' => 'No puede eliminar su propio usuario']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE IdUsuarios = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            }
            break;

        case 'obtener':
            $id = (int)($_POST['id'] ?? 0);

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE IdUsuarios = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                echo json_encode(['success' => true, 'usuario' => $usuario]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error en gestión de usuarios: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log("Error general en gestión de usuarios: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del sistema']);
}
