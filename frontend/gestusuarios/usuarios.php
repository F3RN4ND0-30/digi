<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

if (($_SESSION['dg_rol'] ?? 999) != 1) {
    header('Location: ../sisvis/escritorio.php');
    exit('Acceso denegado: Solo administradores');
}

require '../../backend/db/conexion.php';

$stmt_usuarios = $pdo->query("SELECT u.*, a.Nombre as NombreArea 
                              FROM usuarios u 
                              LEFT JOIN areas a ON u.IdAreas = a.IdAreas 
                              ORDER BY u.IdUsuarios DESC");
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

$stmt_areas = $pdo->query("SELECT IdAreas, Nombre FROM areas ORDER BY Nombre");
$areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

$total_usuarios = count($usuarios);
$usuarios_activos = count(array_filter($usuarios, fn($u) => $u['Estado'] == 1));
$administradores = count(array_filter($usuarios, fn($u) => $u['IdRol'] == 1));
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - DIGI</title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Selectize CSS y JS - Versión moderna con tema Bootstrap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.bootstrap5.min.css">

    <!-- CSS del sistema -->
    <link rel="stylesheet" href="../../backend/css/navbar/navbar.css">
    <link rel="stylesheet" href="../../backend/css/sisvis/escritorio.css">
    <link rel="stylesheet" href="../../backend/css/gestusuarios/gestusuarios.css">

    <!-- jQuery (requerido para Selectize) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Selectize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"></script>

    <!-- Scripts del sistema -->
    <script src="../../backend/js/notificaciones.js"></script>
</head>

<body>
    <div class="layout-escritorio">
        <?php include '../navbar/navbar.php'; ?>

        <main class="contenido-principal">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $total_usuarios ?></h3>
                        <p>Total Usuarios</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $usuarios_activos ?></h3>
                        <p>Usuarios Activos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $administradores ?></h3>
                        <p>Administradores</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="tarjeta tarjeta-formulario">
                <div class="header-usuarios">
                    <div class="tabs-usuarios">
                        <button class="tab-usuario active" onclick="cambiarTab('activos')">
                            <i class="fas fa-users"></i> Usuarios Activos
                        </button>
                        <button class="tab-usuario" onclick="cambiarTab('inactivos')">
                            <i class="fas fa-user-slash"></i> Usuarios Desactivados
                        </button>
                    </div>
                    <button onclick="abrirModal()" class="btn-crear">
                        <i class="fas fa-plus"></i> Crear Usuario
                    </button>
                </div>

                <div id="contenido-activos" class="contenido-tab active">
                    <div class="tabla-container">
                        <table class="tabla-usuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>DNI</th>
                                    <th>Usuario</th>
                                    <th>Información Personal</th>
                                    <th>Área</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <?php if ($user['Estado'] == 1): ?>
                                        <tr>
                                            <td><span class="id-badge"><?= $user['IdUsuarios'] ?></span></td>
                                            <td><span class="dni-badge"><?= htmlspecialchars($user['Dni'] ?? 'N/A') ?></span></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <strong><?= htmlspecialchars($user['Usuario']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="personal-info">
                                                    <div class="name"><?= htmlspecialchars($user['Nombres']) ?></div>
                                                    <div class="surname"><?= htmlspecialchars($user['ApellidoPat'] . ' ' . $user['ApellidoMat']) ?></div>
                                                </div>
                                            </td>
                                            <td><span class="area-badge"><?= htmlspecialchars($user['NombreArea']) ?></span></td>
                                            <td>
                                                <span class="rol-badge rol-<?= $user['IdRol'] ?>">
                                                    <?php
                                                    $roles = [1 => 'Administrador', 2 => 'Supervisor', 3 => 'Usuario'];
                                                    echo $roles[$user['IdRol']] ?? 'Sin definir';
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="acciones">
                                                <button onclick="editarUsuario(<?= $user['IdUsuarios'] ?>)" class="btn-editar" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['IdUsuarios'] != $_SESSION['dg_id']): ?>
                                                    <button onclick="cambiarEstado(<?= $user['IdUsuarios'] ?>, 0)" class="btn-desactivar" title="Desactivar">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="contenido-inactivos" class="contenido-tab">
                    <div class="tabla-container">
                        <table class="tabla-usuarios">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>DNI</th>
                                    <th>Usuario</th>
                                    <th>Información Personal</th>
                                    <th>Área</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <?php if ($user['Estado'] == 0): ?>
                                        <tr class="usuario-inactivo">
                                            <td><span class="id-badge inactive"><?= $user['IdUsuarios'] ?></span></td>
                                            <td><span class="dni-badge inactive"><?= htmlspecialchars($user['Dni'] ?? 'N/A') ?></span></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar inactive">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <strong style="color: #999;"><?= htmlspecialchars($user['Usuario']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="personal-info">
                                                    <div class="name" style="color: #999;"><?= htmlspecialchars($user['Nombres']) ?></div>
                                                    <div class="surname"><?= htmlspecialchars($user['ApellidoPat'] . ' ' . $user['ApellidoMat']) ?></div>
                                                </div>
                                            </td>
                                            <td><span class="area-badge inactive"><?= htmlspecialchars($user['NombreArea']) ?></span></td>
                                            <td><span class="rol-badge rol-inactive">Desactivado</span></td>
                                            <td class="acciones">
                                                <button onclick="cambiarEstado(<?= $user['IdUsuarios'] ?>, 1)" class="btn-activar" title="Reactivar">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal optimizado SIN campo Estado -->
    <div id="modalUsuario" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="tituloModal">Crear Usuario</h3>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formUsuario">
                    <input type="hidden" id="userId" name="userId">

                    <div class="form-container">
                        <div class="form-column">
                            <div class="campo dni-field">
                                <label for="dni">DNI *</label>
                                <div class="dni-input-container">
                                    <input type="text" id="dni" name="dni" required maxlength="8"
                                        pattern="[0-9]{8}" placeholder="12345678">
                                    <button type="button" id="btnBuscarDNI" class="btn-buscar-dni">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <small>8 dígitos numéricos - Búsqueda automática</small>
                            </div>

                            <div class="campo">
                                <label for="nombres">Nombres *</label>
                                <input type="text" id="nombres" name="nombres" required maxlength="100"
                                    placeholder="Nombres completos">
                            </div>

                            <div class="campo">
                                <label for="apellidoPat">Apellido Paterno *</label>
                                <input type="text" id="apellidoPat" name="apellidoPat" required maxlength="100"
                                    placeholder="Apellido paterno">
                            </div>

                            <div class="campo">
                                <label for="area">Área *</label>
                                <select id="area" name="area" required>
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?= $area['IdAreas'] ?>"><?= htmlspecialchars($area['Nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-column">
                            <div class="campo">
                                <label for="usuario">Usuario *</label>
                                <input type="text" id="usuario" name="usuario" required maxlength="50"
                                    placeholder="Nombre de usuario único">
                            </div>

                            <div class="campo">
                                <label for="password">Contraseña *</label>
                                <input type="password" id="password" name="password" required minlength="4"
                                    placeholder="Mínimo 4 caracteres">
                                <small id="passwordHelp">Mínimo 4 caracteres</small>
                            </div>

                            <div class="campo">
                                <label for="apellidoMat">Apellido Materno</label>
                                <input type="text" id="apellidoMat" name="apellidoMat" maxlength="100"
                                    placeholder="Apellido materno (opcional)">
                            </div>

                            <div class="campo">
                                <label for="rol">Rol *</label>
                                <select id="rol" name="rol" required>
                                    <option value="">Seleccione un rol</option>
                                    <option value="1">Administrador</option>
                                    <option value="2">Supervisor</option>
                                    <option value="3">Usuario</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" onclick="cerrarModal()" class="btn-cancelar">Cancelar</button>
                        <button type="submit" class="btn-guardar">
                            <i class="fas fa-save"></i> Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript del módulo -->
    <script src="../../backend/js/gestusuarios/gestusuarios.js"></script>
</body>

</html>