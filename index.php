<?php

declare(strict_types=1);
// Evita que el navegador intente adivinar tipos de archivo
header('X-Content-Type-Options: nosniff');

// Bloquea que embeban la página en iframes (clickjacking)
header('X-Frame-Options: DENY');

// Protección XSS legacy (para navegadores viejos)
header('X-XSS-Protection: 1; mode=block');

// Controla qué se envía en el encabezado Referer
header('Referrer-Policy: no-referrer-when-downgrade');

// Evita cachear en navegadores/proxys (para información sensible)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ---------- CONTENT SECURITY POLICY (CSP) ----------
$csp  = "default-src 'self'; ";
$csp .= "script-src 'self' https://cdnjs.cloudflare.com; ";
$csp .= "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; ";
$csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; ";
$csp .= "img-src 'self' data:; ";
$csp .= "connect-src 'self';";

header("Content-Security-Policy: $csp");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGI - MPP</title>

    <!-- Fuentes e iconos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS principal -->
    <link rel="stylesheet" href="backend/css/index.css">
    <link rel="icon" type="image/png" href="backend/img/logoPisco.png">
</head>

<body>

    <!-- Logo Municipalidad Flotante -->
    <div class="muni-logo">
        <img src="backend/img/munipisco.png" alt="Municipalidad Provincial de Pisco">
    </div>

    <!-- Header con Acceso Administrativo -->
    <div class="header">
        <a href="frontend/login.php" class="login-btn">
            <i class="fas fa-user-shield"></i>
            <span>Acceso Administrativo</span>
        </a>
    </div>

    <!-- Contenedor Principal -->
    <div class="main-container">
        <div class="content-area">

            <!-- Hero Section -->
            <div class="hero-section">
                <div class="logo-container">
                    <div class="logo-circle">
                        <i class="fas fa-stamp"></i>
                    </div>
                </div>

                <h1 class="main-title">
                    <span class="title-letter">D</span>
                    <span class="title-letter">I</span>
                    <span class="title-letter">G</span>
                    <span class="title-letter">I</span>
                    <span class="title-separator">-</span>
                    <span class="title-letter">M</span>
                    <span class="title-letter">P</span>
                    <span class="title-letter">P</span>
                </h1>

                <p class="main-subtitle">Documentación Integrada de Gestión Interna</p>

                <p class="main-description">
                    Sistema de seguimiento y gestión documental de la Municipalidad Provincial de Pisco.
                    Consulta el estado de tus trámites de forma rápida y segura.
                </p>
            </div>

            <!-- Botones de Acción principales -->
            <div class="action-section">
                <a href="frontend/consultas/seguimiento_tramite.php"
                    class="action-btn action-btn-main">
                    <i class="fas fa-route"></i>
                    <span>Seguimiento de Trámite</span>
                </a>
            </div>

            <!-- ¿Cómo funciona? -->
            <div class="how-it-works">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    ¿Cómo funciona?
                </h2>

                <div class="steps-container">
                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="step-number">1</div>
                        <h3 class="step-title">Ingresa tu Código</h3>
                        <p class="step-description">
                            Utiliza el número de expediente o código de documento proporcionado al momento de tu registro.
                        </p>
                    </div>

                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-search"></i></div>
                        <div class="step-number">2</div>
                        <h3 class="step-title">Consulta el Estado</h3>
                        <p class="step-description">
                            Visualiza en tiempo real el estado actual de tu trámite y su ubicación.
                        </p>
                    </div>

                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="step-number">3</div>
                        <h3 class="step-title">Seguimiento completo</h3>
                        <p class="step-description">
                            Revisa el historial completo desde su registro hasta su finalización.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="info-cards">
                <div class="info-card">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <h3 class="card-title">Disponibilidad 24/7</h3>
                    <p>Consulta tus documentos en cualquier momento.</p>
                </div>

                <div class="info-card">
                    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="card-title">Seguro y Confiable</h3>
                    <p>Tu información está protegida con altos estándares.</p>
                </div>

                <div class="info-card">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <h3 class="card-title">Atención Ciudadana</h3>
                    <p>Te ayudamos si tienes dudas o consultas.</p>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="help-section">
                <div class="help-box">
                    <i class="fas fa-question-circle"></i>
                    <h3>¿Necesitas Ayuda?</h3>
                    <p>Si no encuentras tu documento o tienes dudas contáctanos:</p>

                    <div class="contact-info">
                        <a href="tel:056655069" class="contact-link">
                            <i class="fas fa-phone"></i> (056) 65-5069
                        </a>
                        <a href="mailto:munipisco@munipisco.gob.pe" class="contact-link">
                            <i class="fas fa-envelope"></i> munipisco@munipisco.gob.pe
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Municipalidad Provincial de Pisco</h4>
                <p>Unidad de Sistemas</p>
            </div>

            <div class="footer-section">
                <p><i class="fas fa-map-marker-alt"></i> Calle Ramón Aspíllaga N° 901 - Pisco</p>
                <p><i class="fas fa-phone"></i> (056) 65-5069</p>
            </div>

            <div class="footer-section">
                <p>&copy; 2025 DIGI - Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <!-- Fondo partículas -->
    <div class="particles-bg"></div>

</body>

</html>