<?php

/**
 * Navbar modular para Sistema DIGI
 * Archivo: navbar/navbar.php
 * 
 * IMPORTANTE: Este archivo solo contiene HTML
 * El CSS y JavaScript deben incluirse en cada página
 * 
 * SEGURIDAD:
 * - Verificación de sesión activa obligatoria
 * - Sanitización de datos de usuario con htmlspecialchars
 * - Detección automática de página actual
 */

// Verificar sesión - Seguridad básica
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

// Detectar página actual para navegación activa
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = htmlspecialchars($_SESSION['dg_nombre'] ?? 'Usuario');
$user_role = $_SESSION['dg_rol'] ?? 999; // Obtener rol para permisos
?>

<!-- Navbar Horizontal Moderna -->
<nav class="navbar-horizontal">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="navbar-brand">
            <span class="logo-icon">⚡</span>
            <span class="logo-text">DIGI - MPP</span>
        </div>

        <!-- Navegación Principal -->
        <div class="navbar-nav">
            <!-- Inicio -->
            <a href="../sisvis/escritorio.php" class="nav-link <?= ($current_page === 'escritorio') ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>

            <!-- Dropdown Archivos -->
            <div class="nav-dropdown">
                <a href="#" class="nav-link dropdown-toggle">
                    <i class="fas fa-folder-open"></i>
                    <span>Archivos</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="../archivos/recepcion.php" class="dropdown-item">
                        <i class="fas fa-inbox"></i>
                        <span>Recepción</span>
                    </a>
                    <a href="../archivos/enviados.php" class="dropdown-item">
                        <i class="fas fa-paper-plane"></i>
                        <span>Enviados</span>
                    </a>
                    <a href="../archivos/reenviar.php" class="dropdown-item">
                        <i class="fas fa-share"></i>
                        <span>Reenviar</span>
                    </a>
                    <a href="../seguimiento/busqueda.php" class="dropdown-item">
                        <i class="fas fa-route"></i>
                        <span>Seguimiento</span>
                    </a>
                </div>
            </div>

            <!-- Gestión de Usuarios (Solo para administradores) -->
            <?php if ($user_role == 1): ?>
                <a href="../gestusuarios/usuarios.php" class="nav-link <?= ($current_page === 'usuarios') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            <?php endif; ?>

            <!-- Configuración -->
            <a href="../configuracion/index.php" class="nav-link <?= ($current_page === 'configuracion' || $current_page === 'index') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </div>

        <!-- Usuario y Logout -->
        <div class="navbar-user">
            <!-- Sistema de Notificaciones -->
            <div class="notificaciones">
                <div id="notificaciones" style="position: relative; cursor: pointer;">
                    <i class="fas fa-bell"></i>
                    <span id="contador" style="color: red; font-weight: bold;"></span>
                </div>

                <!-- Lista de Notificaciones -->
                <div id="listaNotificaciones" style="display: none; position: absolute; border-radius:7px; top:73px; right: none; background: #bb99f1ff; color:white; border: 1px solid #ccc; max-height: 300px; overflow-y: auto; padding: 10px; width: 300px; z-index: 100;">
                    <strong>Notificaciones:</strong>
                    <ul id="contenedorNotificaciones" style="list-style: none; padding-left: 0;"></ul>
                </div>
            </div>

            <!-- Información del Usuario -->
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= $user_name ?></span>
                    <span class="user-role"><?= ucfirst($user_role) ?></span>
                </div>
            </div>

            <!-- Botón de Salir con confirmación -->
            <a href="../logout.php" class="nav-link logout-btn" onclick="return confirm('¿Está seguro que desea cerrar sesión?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Salir</span>
            </a>
        </div>

        <!-- Toggle para Mobile -->
        <button class="mobile-toggle" onclick="toggleMobileNav()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<script>
    /**
     * Script de seguridad y funcionalidad del navbar
     * Incluye validaciones y controles de sesión
     */

    // Función para toggle del menú móvil
    function toggleMobileNav() {
        const navbarNav = document.querySelector('.navbar-nav');
        const mobileToggle = document.querySelector('.mobile-toggle');

        navbarNav.classList.toggle('mobile-active');
        mobileToggle.classList.toggle('active');
    }

    // Sistema de notificaciones con seguridad
    document.addEventListener('DOMContentLoaded', function() {
        const notificaciones = document.getElementById('notificaciones');
        const lista = document.getElementById('listaNotificaciones');

        if (notificaciones) {
            notificaciones.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Toggle de la lista con animación suave
                if (lista.style.display === 'none' || lista.style.display === '') {
                    lista.style.display = 'block';
                    cargarNotificaciones(); // Función para cargar notificaciones via AJAX
                } else {
                    lista.style.display = 'none';
                }
            });
        }

        // Cerrar notificaciones al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!notificaciones.contains(e.target)) {
                lista.style.display = 'none';
            }
        });

        // Validación de sesión periódica (cada 5 minutos)
        setInterval(validarSesion, 300000);
    });

    // Función para validar sesión activa
    function validarSesion() {
        fetch('../backend/php/validar_sesion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
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

    // Función para cargar notificaciones
    function cargarNotificaciones() {
        fetch('../backend/php/obtener_notificaciones.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                const contenedor = document.getElementById('contenedorNotificaciones');
                const contador = document.getElementById('contador');

                if (data.notificaciones && data.notificaciones.length > 0) {
                    contenedor.innerHTML = '';
                    contador.textContent = data.notificaciones.length;

                    data.notificaciones.forEach(notif => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                    <div style="padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                        <strong>${notif.titulo}</strong><br>
                        <small>${notif.mensaje}</small><br>
                        <em style="font-size: 0.8em;">${notif.fecha}</em>
                    </div>
                `;
                        contenedor.appendChild(li);
                    });
                } else {
                    contenedor.innerHTML = '<li>No hay notificaciones nuevas</li>';
                    contador.textContent = '';
                }
            })
            .catch(error => {
                console.error('Error al cargar notificaciones:', error);
                document.getElementById('contenedorNotificaciones').innerHTML = '<li>Error al cargar notificaciones</li>';
            });
    }

    // Auto-ocultar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // Activar/desactivar dropdowns
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;

            // Cerrar otros dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(otherMenu => {
                if (otherMenu !== menu) {
                    otherMenu.style.display = 'none';
                }
            });

            // Toggle del dropdown actual
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
    });
</script>