<?php
// Verificar sesión
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = htmlspecialchars($_SESSION['dg_nombre'] ?? 'Usuario');
$user_role = $_SESSION['dg_rol'] ?? 999;
?>

<script src="../../backend/js/notificaciones.js"></script>

<nav class="navbar-movil">
    <div class="navbar-movil-top">
        <div class="navbar-title">DIGI - MPP</div>

        <div class="navbar-icons">
            <!-- Notificaciones -->
            <div id="notificaciones" style="position: relative;">
                <i class="fas fa-bell"></i>
                <span id="contador" style="color: yellow; font-weight: bold; font-size: 14px;"></span>
            </div>

            <!-- Botón hamburguesa -->
            <i class="fas fa-bars" onclick="toggleMobileMenu()"></i>
        </div>
    </div>

    <!-- Menú desplegable -->
    <div id="mobileMenu">
        <div style="text-align: center; margin-bottom: 15px;">
            <strong><?= $user_name ?></strong>
        </div>
        <a href="../sisvis/escritorio"><i class="fas fa-home"></i> Inicio</a>
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
                        <a href="../archivos/registrar" class="module-item <?= ($current_page === 'registrar') ? 'active' : '' ?>">
                            <i class="fas fa-plus-circle"></i>
                            <span>Registrar</span>
                        </a>
                        <a href="../archivos/recepcion" class="module-item <?= ($current_page === 'recepcion') ? 'active' : '' ?>">
                            <i class="fas fa-inbox"></i>
                            <span>Recepción</span>
                        </a>
                        <a href="../archivos/enviados" class="module-item <?= ($current_page === 'enviados') ? 'active' : '' ?>">
                            <i class="fas fa-paper-plane"></i>
                            <span>Enviados</span>
                        </a>
                        <a href="../archivos/reenviar" class="module-item <?= ($current_page === 'reenviar') ? 'active' : '' ?>">
                            <i class="fas fa-share"></i>
                            <span>Reenviar/Finalizar</span>
                        </a>
                        <a href="../seguimiento/busqueda" class="module-item <?= ($current_page === 'busqueda') ? 'active' : '' ?>">
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
                            <a href="../gestusuarios/usuarios" class="module-item <?= ($current_page === 'usuarios') ? 'active' : '' ?>">
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
                            <a href="../defensoria/supervision" class="module-item <?= ($current_page === 'supervision') ? 'active' : '' ?>">
                                <i class="fas fa-shield"></i>
                                <span>Supervisión</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        <a href="../configuracion/perfil"><i class="fas fa-cog"></i> Configuración</a>
        <a href="../logout" onclick="return confirm('¿Está seguro que desea cerrar sesión?')"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>

    <!-- Lista Notificaciones -->
    <div id="listaNotificaciones" style="display:none; background: #bb99f1; padding:10px; border-radius:5px; margin-top:10px; color: white;">
        <strong>Notificaciones:</strong>
        <ul id="contenedorNotificaciones" style="list-style:none; padding-left:0;"></ul>
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
                const isCurrentlyOpen = parentDropdown.classList.contains('active');

                // Cerrar todos los dropdowns primero
                document.querySelectorAll('.dropdown-menu').forEach(otherMenu => {
                    otherMenu.style.maxHeight = "0";
                });
                document.querySelectorAll('.nav-dropdown').forEach(otherDropdown => {
                    otherDropdown.classList.remove('active');
                });

                // Si no estaba abierto, abrirlo
                if (!isCurrentlyOpen) {
                    menu.style.maxHeight = menu.scrollHeight + "px";
                    parentDropdown.classList.add('active');
                } else {
                    menu.style.maxHeight = "0";
                    parentDropdown.classList.remove('active');
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
                        contenedor.innerHTML = '<li style="padding: 5px;">No hay notificaciones.</li>';
                        contador.textContent = '';
                        return;
                    }

                    data.forEach(n => {
                        const li = document.createElement('li');
                        li.style.padding = '10px';
                        li.style.borderBottom = '1px solid #eee';
                        li.innerHTML = `
                            <div>${n.Mensaje}<br><small style="color: gray;">${n.FechaVisto}</small></div>
                            <button onclick="actualizarNotificacion(${n.IdNotificacion}, 'vista')" style="margin:4px; background: transparent; border: none; cursor: pointer;">Marcar como vista</button>
                            <button onclick="actualizarNotificacion(${n.IdNotificacion}, 'eliminar')" style="margin:4px; background: transparent; border: none; cursor: pointer; float: right;">X</button>
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
                const visible = lista.style.display === 'block';

                // Cerrar dropdowns si están abiertos
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });

                lista.style.display = visible ? 'none' : 'block';
                if (!visible) contador.textContent = '';
            });

            // Cargar notificaciones al inicio y cada 30s
            cargarNotificaciones();
            setInterval(cargarNotificaciones, 30000);
        }

        // Cerrar notificaciones al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notificaciones')) {
                if (lista) lista.style.display = 'none';
            }
        });

        console.log('Navbar inicializado correctamente');
    });
</script>
<script>
    function toggleMobileMenu() {
        const menu = document.getElementById("mobileMenu");
        menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
    }
</script>