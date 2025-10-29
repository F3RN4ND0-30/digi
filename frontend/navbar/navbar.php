<?php
// Verificar sesión
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

// Detectar página actual para navegación activa
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = htmlspecialchars($_SESSION['dg_nombre'] ?? 'Usuario');
$user_role = $_SESSION['dg_rol'] ?? 999;
?>

<!-- Navbar Horizontal Moderna -->
<nav class="navbar-horizontal">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="navbar-brand">
            <span class="logo-icon"><i class="fas fa-stamp"></i></span>
            <span class="logo-text">DIGI - MPP</span>
        </div>
        <!-- Navegación Principal -->
        <div class="navbar-nav">
            <!-- Inicio -->
            <a href="../sisvis/escritorio.php" class="nav-link <?= ($current_page === 'escritorio') ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>

            <!-- Dropdown Módulos -->
            <div class="nav-dropdown">
                <a href="#" class="nav-link dropdown-toggle">
                    <i class="fas fa-th-large"></i>
                    <span>Módulos</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="dropdown-menu dropdown-grid">

                    <!-- Columna 1: Gestión Documental -->
                    <div class="module-column">
                        <h6 class="column-header">
                            <i class="fas fa-folder-open"></i>
                            GESTIÓN DOCUMENTAL
                        </h6>
                        <div class="column-items">
                            <a href="../archivos/registrar.php" class="module-item <?= ($current_page === 'registrar') ? 'active' : '' ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Registrar</span>
                            </a>
                            <a href="../archivos/recepcion.php" class="module-item <?= ($current_page === 'recepcion') ? 'active' : '' ?>">
                                <i class="fas fa-inbox"></i>
                                <span>Recepción</span>
                            </a>
                            <a href="../archivos/enviados.php" class="module-item <?= ($current_page === 'enviados') ? 'active' : '' ?>">
                                <i class="fas fa-paper-plane"></i>
                                <span>Enviados</span>
                            </a>
                            <a href="../archivos/reenviar.php" class="module-item <?= ($current_page === 'reenviar') ? 'active' : '' ?>">
                                <i class="fas fa-share"></i>
                                <span>Reenviar/Finalizar</span>
                            </a>
                            <a href="../seguimiento/busqueda.php" class="module-item <?= ($current_page === 'busqueda') ? 'active' : '' ?>">
                                <i class="fas fa-route"></i>
                                <span>Seguimiento</span>
                            </a>
                        </div>
                    </div>

                    <!-- Columna 2: Administración (Solo admin) -->
                    <?php if ($user_role == 1): ?>
                        <div class="module-column">
                            <h6 class="column-header">
                                <i class="fas fa-shield-alt"></i>
                                ADMINISTRACIÓN
                            </h6>
                            <div class="column-items">
                                <a href="../gestusuarios/usuarios.php" class="module-item <?= ($current_page === 'usuarios') ? 'active' : '' ?>">
                                    <i class="fas fa-users"></i>
                                    <span>Usuarios</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($user_role === 1 || $user_role === 4): ?>
                        <!-- Columna 3: Supervisión -->
                        <div class="module-column">
                            <h6 class="column-header">
                                <i class="fas fa-eye"></i>
                                SUPERVISIÓN
                            </h6>
                            <div class="column-items">
                                <a href="../defensoria/supervision.php" class="module-item <?= ($current_page === 'supervision') ? 'active' : '' ?>">
                                    <i class="fas fa-shield"></i>
                                    <span>Supervisión</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Configuración -->
            <a href="../reportes/reporte.php" class="nav-link <?= ($current_page === 'reporte') ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>Reportes</span>
            </a>
        </div>

        <script src="../../backend/js/notificaciones.js"></script>

        <!-- Usuario y Logout -->
        <div class="navbar-user">
            <!-- Sistema de Notificaciones  -->
            <div class="notificaciones-modern">
                <div id="notificaciones" class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span id="contador" class="notification-badge"></span>
                    <span class="bell-ping"></span>
                </div>

                <!-- Lista de Notificaciones Mejorada -->
                <div id="listaNotificaciones" class="notification-panel">
                    <div class="notification-header">
                        <h4><i class="fas fa-bell"></i> Notificaciones</h4>
<!--                         <button onclick="marcarTodasVistas()" class="btn-mark-all">
                            <i class="fas fa-check-double"></i> Marcar todas
                        </button> -->
                    </div>
                    <div class="notification-body">
                        <ul id="contenedorNotificaciones" class="notification-list"></ul>
                    </div>
                    <!-- <div class="notification-footer">
                        <a href="#" class="view-all-link">Ver todas las notificaciones</a>
                    </div> -->
                </div>
            </div>
            <!-- Información del Usuario -->
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= $user_name ?></span>
                </div>
            </div>

            <!-- Configuración -->
            <a href="../configuracion/perfil.php" class="nav-link <?= ($current_page === 'configuracion' || $current_page === 'perfil') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
            </a>

            <!-- Botón de Salir -->
            <a href="../logout.php" class="nav-link logout-btn" onclick="return confirm('¿Está seguro que desea cerrar sesión?')">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

        <!-- Toggle para Mobile -->
        <button class="mobile-toggle" onclick="toggleMobileNav()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Navbar JavaScript cargado');

        // Toggle Mobile Nav
        const navbarNav = document.querySelector('.navbar-nav');
        const mobileToggle = document.querySelector('.mobile-toggle');

        window.toggleMobileNav = function() {
            navbarNav.classList.toggle('active');
            mobileToggle.classList.toggle('active');
        };

        // Dropdown Menús Mejorado - ESPECÍFICO PARA DROPDOWN GRID
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                // Cerrar todos los dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
                document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });

        // Manejar clicks en dropdown toggles
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const parentDropdown = this.closest('.nav-dropdown');
                const menu = parentDropdown.querySelector('.dropdown-menu');
                const isCurrentlyOpen = menu.style.display === 'block';

                // Cerrar todos los dropdowns primero
                document.querySelectorAll('.dropdown-menu').forEach(otherMenu => {
                    otherMenu.style.display = 'none';
                });
                document.querySelectorAll('.nav-dropdown').forEach(otherDropdown => {
                    otherDropdown.classList.remove('active');
                });

                // Si no estaba abierto, abrirlo
                if (!isCurrentlyOpen) {
                    menu.style.display = 'block';
                    parentDropdown.classList.add('active');

                    // Log para debug
                    console.log('Dropdown abierto:', menu.classList.contains('dropdown-grid') ? 'Grid Mode' : 'Normal Mode');
                }
            });
        });

        // Prevenir que clicks dentro del dropdown lo cierren
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Validar sesión cada 5 min
        setInterval(validarSesion, 300000);

        function validarSesion() {
            fetch('../backend/php/validar_sesion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.sesion_valida) {
                        alert('Su sesión ha expirado. Será redirigido al login.');
                        window.location.href = '../login.php';
                    }
                })
                .catch(error => {
                    console.error('Error al validar sesión:', error);
                });
        }

        // Sistema de Notificaciones
        const btnCampana = document.getElementById('notificaciones');
        const lista = document.getElementById('listaNotificaciones');
        const contenedor = document.getElementById('contenedorNotificaciones');
        const contador = document.getElementById('contador');

        function cargarNotificaciones() {
            fetch('../../backend/php/notificaciones/cargar_notificaciones.php')
                .then(res => res.json())
                .then(data => {
                    contenedor.innerHTML = '';

                    if (data.length === 0) {
                        contenedor.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No tienes notificaciones nuevas</p>
                    </div>
                `;
                        contador.textContent = '';
                        return;
                    }

                    data.forEach(n => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                    <div class="notification-content">
                        <div class="notification-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="notification-text">
                            <p>${n.Mensaje}</p>
                            <span class="notification-time">
                                <i class="far fa-clock"></i>
                                ${n.FechaVisto}
                            </span>
                            <div class="notification-actions">
                                <button class="btn-mark-read" onclick="actualizarNotificacion(${n.IdNotificacion}, 'vista')">
                                    <i class="fas fa-check"></i> Marcar como leída
                                </button>
                                /* <button class="btn-delete" onclick="actualizarNotificacion(${n.IdNotificacion}, 'eliminar')">
                                    <i class="fas fa-trash"></i>
                                </button> */
                            </div>
                        </div>
                    </div>
                `;
                        contenedor.appendChild(li);
                    });

                    contador.textContent = data.length;
                })
                .catch(error => {
                    console.error('Error cargando notificaciones:', error);
                });
        }

        // Función global para botones de notificaciones
        window.actualizarNotificacion = function(id, accion) {
            fetch('../../backend/php/notificaciones/actualizar_notificacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${id}&accion=${accion}`
            }).then(() => cargarNotificaciones());
        }

        // Manejar notificaciones
        if (btnCampana) {
            btnCampana.addEventListener('click', function(e) {
                e.stopPropagation();
                const visible = lista.style.display === 'flex';

                // Cerrar dropdowns si están abiertos
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });

                lista.style.display = visible ? 'none' : 'flex';
                if (!visible) {
                    cargarNotificaciones();
                }
            });

            // Cargar al inicio y cada 30s
            cargarNotificaciones();
            setInterval(cargarNotificaciones, 30000);
        }

        // Cerrar al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notificaciones-modern')) {
                if (lista) lista.style.display = 'none';
            }
        });

        console.log('Navbar inicializado correctamente');
    });
</script>