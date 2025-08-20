<?php

/**
 * Navbar modular para Sistema DIGI
 * Archivo: navbar/navbar.php
 * 
 * IMPORTANTE: Este archivo solo contiene HTML
 * El CSS y JavaScript deben incluirse en cada página
 */

// Verificar sesión
if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

// Detectar página actual
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = htmlspecialchars($_SESSION['dg_nombre'] ?? 'Usuario');
?>

<!-- Navbar Horizontal Moderna -->
<nav class="navbar-horizontal">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="navbar-brand">
            <span class="logo-icon">⚡</span>
            <span class="logo-text">DIGI - MPP</span>
        </div>

        <!-- Navegación -->
        <div class="navbar-nav">
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

            <a href="../configuracion/perfil.php" class="nav-link <?= ($current_page === 'configuracion') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </div>
        <!-- Usuario y Logout -->
        <div class="navbar-user">
            <div class="notificaciones">
                <!-- En tu navbar o barra lateral -->
                <div id="notificaciones" style="position: relative; cursor: pointer;">
                    🔔 <span id="contador" style="color: red; font-weight: bold; font-size: 20px; font-family: 'Arial Black', Arial, sans-serif;"></span>
                </div>

                <!-- Contenedor para la lista -->
                <div id="listaNotificaciones" style="display: none; position: absolute; border-radius:7px; top:73px; right: none; background: #bb99f1ff; color:white; border: 1px solid #ccc; max-height: 300px; overflow-y: auto; padding: 10px; width: 300px; z-index: 100;">
                    <strong>Notificaciones:</strong>
                    <ul id="contenedorNotificaciones" style="list-style: none; padding-left: 0;"></ul>
                </div>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span class="user-name"><?= $user_name ?></span>
            </div>
            <a href="../logout.php" class="nav-link logout-btn" onclick="return confirm('¿Cerrar sesión?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Salir</span>
            </a>
        </div>

        <!-- Mobile Toggle -->
        <button class="mobile-toggle" onclick="toggleMobileNav()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>