<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIGI - Sistema de Gestión Documental | Municipalidad Provincial de Pisco</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="backend/css/index.css">
    <link rel="icon" type="image/png" href="backend/img/logoPisco.png" />
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
            Acceso Administrativo
        </a>
    </div>

    <!-- Contenedor Principal -->
    <div class="main-container">
        <div class="content-area">

            <!-- Hero Section -->
            <div class="hero-section">
                <div class="logo-container">
                    <div class="logo-circle">
                        <i class="fas fa-file-contract"></i>
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

            <!-- Botones de Acción Principales -->
            <div class="action-section">
                
                <a href="frontend/consultas/seguimiento_tramite.php" class="action-btn btn-outline-action">
                    <i class="fas fa-route"></i>
                    <span>Seguimiento de Trámite</span>
                </a>
            </div>

            <!-- Sección: Cómo Funciona -->
            <div class="how-it-works">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    ¿Cómo funciona?
                </h2>

                <div class="steps-container">
                    <div class="step-item">
                        <div class="step-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="step-number">1</div>
                        <h3 class="step-title">Ingresa tu Código</h3>
                        <p class="step-description">
                            Utiliza el número de expediente o código de documento proporcionado al momento de tu registro.
                        </p>
                    </div>

                    <div class="step-item">
                        <div class="step-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="step-number">2</div>
                        <h3 class="step-title">Consulta el Estado</h3>
                        <p class="step-description">
                            Visualiza en tiempo real el estado actual de tu trámite y su ubicación en el sistema.
                        </p>
                    </div>

                    <div class="step-item">
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="step-number">3</div>
                        <h3 class="step-title">Seguimiento Completo</h3>
                        <p class="step-description">
                            Revisa el historial completo de tu documento desde su registro hasta su finalización.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="info-cards">
                <div class="info-card">
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="card-title">Disponibilidad 24/7</h3>
                    <p class="card-text">
                        Consulta tus documentos en cualquier momento, desde cualquier dispositivo con acceso a internet.
                    </p>
                </div>

                <div class="info-card">
                    <div class="card-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="card-title">Seguro y Confiable</h3>
                    <p class="card-text">
                        Tu información está protegida con los más altos estándares de seguridad y confidencialidad.
                    </p>
                </div>

                <div class="info-card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Atención Ciudadana</h3>
                    <p class="card-text">
                        ¿Necesitas ayuda? Nuestro equipo está listo para asistirte en tus consultas y trámites.
                    </p>
                </div>
            </div>

            <!-- Ayuda y Contacto -->
            <div class="help-section">
                <div class="help-box">
                    <i class="fas fa-question-circle"></i>
                    <h3>¿Necesitas Ayuda?</h3>
                    <p>Si no encuentras tu código de documento o tienes alguna duda, comunícate con nosotros:</p>
                    <div class="contact-info">
                        <a href="tel:056532000" class="contact-link">
                            <i class="fas fa-phone"></i>
                            (056) 53-2000
                        </a>
                        <a href="mailto:mesapartes@munipisco.gob.pe" class="contact-link">
                            <i class="fas fa-envelope"></i>
                            mesapartes@munipisco.gob.pe
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
                <p>Unidad de Gestión Documental</p>
            </div>
            <div class="footer-section">
                <p>
                    <i class="fas fa-map-marker-alt"></i>
                    Calle Ramón Aspíllaga N° 901 - Pisco, Ica, Perú
                </p>
                <p>
                    <i class="fas fa-phone"></i>
                    (056) 53-2000
                </p>
            </div>
            <div class="footer-section">
                <p>&copy; 2025 DIGI - Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <!-- Partículas de fondo (opcional) -->
    <div class="particles-bg"></div>

</body>

</html>