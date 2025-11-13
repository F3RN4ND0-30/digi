<?php
session_start();

if (!isset($_SESSION['dg_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../backend/db/conexion.php';

// Detectar si es móvil para cargar navbar y css correspondientes
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/Mobile|Android|iP(hone|od|ad)/i', $_SERVER['HTTP_USER_AGENT']);
}

// Definir qué archivo de navbar y CSS se va a usar
$navbarFile = $isMobile ? 'navbar_mobil.php' : 'navbar.php';
$navbarCss  = $isMobile ? 'navbar_mobil.css' : 'navbar.css';

$area_id = $_SESSION['dg_area_id'] ?? null;

if (!$area_id) {
    die("❌ No se pudo determinar el área del usuario.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enviados - DIGI MPP</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS del Navbar (dinámico) -->
    <link rel="stylesheet" href="../../backend/css/navbar/<?= $navbarCss ?>" />

    <!-- CSS de enviados -->
    <link rel="stylesheet" href="../../backend/css/archivos/enviados.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />

    <style>
        .tabs-enviados {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab-btn {
            border: none;
            background: #f1f5f9;
            padding: 0.7rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            color: #475569;
            border-radius: 6px 6px 0 0; /* cuadradito arriba */
            border: 1px solid transparent;
            border-bottom: none;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            background: #e2e8f0;
        }

        .tab-btn.active {
            background: #6c5ce7;
            color: #fff;
            border-color: #6c5ce7;
            box-shadow: 0 2px 8px rgba(108, 92, 231, 0.35);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Badge para el número/código */
        .badge-numero-enviado {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: #fff;
            box-shadow: 0 2px 6px rgba(108, 92, 231, 0.35);
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="layout-escritorio">

        <!-- Incluir el navbar correcto -->
        <?php include "../navbar/$navbarFile"; ?>

        <main class="contenido-principal">
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <h2><i class="fas fa-paper-plane"></i> Enviados</h2>
                </div>

                <div class="tarjeta-body">
                    <!-- Pestañas -->
                    <div class="tabs-enviados">
                        <button class="tab-btn active" data-target="#tab-docs">
                            <i class="fas fa-file-alt"></i> Documentos
                        </button>
                        <button class="tab-btn" data-target="#tab-memos">
                            <i class="fas fa-file-signature"></i> Memorándums
                        </button>
                    </div>

                    <!-- TAB DOCUMENTOS -->
                    <div id="tab-docs" class="tab-content active">
                        <div class="table-responsive">
                            <table id="tablaEnviadosDocs" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Número/Nombre</th>
                                        <th>Asunto</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Área Destino</th>
                                        <th>Observación</th>
                                        <th>Recibido</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB MEMORÁNDUMS -->
                    <div id="tab-memos" class="tab-content">
                        <div class="table-responsive">
                            <table id="tablaEnviadosMemos" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Código Memo</th>
                                        <th>Asunto</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Áreas Destino</th>
                                        <th>Observación</th>
                                        <th>Recibido</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Notificaciones -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {

            // Tabs
            $('.tab-btn').on('click', function() {
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');

                const target = $(this).data('target');
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });

            // DataTable Documentos
            const tablaDocs = $('#tablaEnviadosDocs').DataTable({
                ajax: {
                    url: '../../backend/php/ajax/cargar_enviados_ajax.php?tipo=DOC',
                    dataSrc: ''
                },
                columns: [
                    { data: 'IdMovimientoDocumento' },
                    {
                        data: 'NumeroDocumento',
                        render: function(data) {
                            return '<span class="badge-numero-enviado">' + data + '</span>';
                        }
                    },
                    { data: 'Asunto' },
                    { data: 'Estado' },
                    { data: 'FechaMovimiento' },
                    { data: 'AreaDestino' },
                    { data: 'Observacion' },
                    {
                        data: 'Recibido',
                        render: function(data) {
                            return data == 1
                                ? '<span class="badge bg-success"><i class="fas fa-check"></i> Recibido</span>'
                                : '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pendiente</span>';
                        }
                    }
                ],
                columnDefs: [
                    { targets: [0, 1], className: 'text-center' }
                ],
                order: [[0, 'desc']],
                responsive: true,
                pageLength: 25
            });

            // DataTable Memorándums
            const tablaMemos = $('#tablaEnviadosMemos').DataTable({
                ajax: {
                    url: '../../backend/php/ajax/cargar_enviados_ajax.php?tipo=MEMO',
                    dataSrc: ''
                },
                columns: [
                    { data: 'IdMovimientoDocumento' },
                    {
                        data: 'NumeroDocumento',
                        render: function(data) {
                            return '<span class="badge-numero-enviado">' + data + '</span>';
                        }
                    },
                    { data: 'Asunto' },
                    { data: 'Estado' },
                    { data: 'FechaMovimiento' },
                    { data: 'AreaDestino' },
                    { data: 'Observacion' },
                    {
                        data: 'Recibido',
                        render: function(data) {
                            return data == 1
                                ? '<span class="badge bg-success"><i class="fas fa-check"></i> Recibido</span>'
                                : '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pendiente</span>';
                        }
                    }
                ],
                columnDefs: [
                    { targets: [0, 1], className: 'text-center' }
                ],
                order: [[0, 'desc']],
                responsive: true,
                pageLength: 25
            });

            // Auto-refresh ambas tablas
            setInterval(() => {
                tablaDocs.ajax.reload(null, false);
                tablaMemos.ajax.reload(null, false);
            }, 30000);
        });
    </script>

    <script>
        $(document).ready(function() {
            // Mobile toggle
            window.toggleMobileNav = function() {
                $('.navbar-nav').toggleClass('active');
            };

            // Dropdown functionality
            $('.nav-dropdown .dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                $('.nav-dropdown').not($(this).parent()).removeClass('active');
                $(this).parent().toggleClass('active');
            });

            // Cerrar dropdown al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nav-dropdown').length) {
                    $('.nav-dropdown').removeClass('active');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // 1. Mostrar/Ocultar el menú móvil completo
            window.toggleMobileMenu = function() {
                $('#mobileMenu').slideToggle(200);
            };

            // 2. Controlar los dropdowns internos del menú móvil
            $('#mobileMenu .dropdown-toggle').on('click', function(e) {
                e.preventDefault();

                const parentDropdown = $(this).closest('.nav-dropdown');
                const dropdownMenu = parentDropdown.find('.dropdown-menu');

                const isOpen = parentDropdown.hasClass('active');

                // Cerrar todos los demás
                $('#mobileMenu .nav-dropdown').not(parentDropdown).removeClass('active')
                    .find('.dropdown-menu').css('max-height', '0');

                // Toggle el actual
                if (isOpen) {
                    parentDropdown.removeClass('active');
                    dropdownMenu.css('max-height', '0');
                } else {
                    parentDropdown.addClass('active');
                    dropdownMenu[0].style.maxHeight = dropdownMenu[0].scrollHeight + 'px';
                }
            });

            // 3. Cerrar dropdowns si se hace clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#mobileMenu .nav-dropdown').length &&
                    !$(e.target).closest('.fas.fa-bars').length) {
                    $('#mobileMenu .nav-dropdown').removeClass('active')
                        .find('.dropdown-menu').css('max-height', '0');
                }
            });
        });
    </script>
</body>

</html>
