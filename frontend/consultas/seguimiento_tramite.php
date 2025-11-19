<?php
session_start();
require '../../backend/db/conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buscar Documentos Enviados</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Principal del Escritorio -->
    <link rel="stylesheet" href="../../backend/css/seguimiento/busqueda.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">

    <link rel="icon" type="image/png" href="../../backend/img/logoPisco.png" />
</head>

<body>

    <div class="layout-escritorio">

        <main class="contenido-principal">
            <div class="container">
                <h2>🔎 SEGUIMIENTO DE DOCUMENTOS</h2>

                <table id="tablaResultados" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Número de Expediente</th>
                            <th>Asunto</th>
                            <th>Área Origen</th>
                            <th>Área Destino</th>
                            <th>Fecha Movimiento</th>
                            <th>N° de Folios</th>
                            <th>N° de Informe</th>
                            <th>Estado Recepción</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal de Seguimiento -->
    <div id="modalSeguimiento" class="modal-seguimiento" style="display: none;">
        <div class="modal-content-seguimiento">
            <div class="modal-header-seguimiento">
                <h3 id="tituloModal">Trazabilidad del Documento</h3>
                <button class="close-modal" onclick="cerrarModalSeguimiento()">&times;</button>
            </div>
            <div class="modal-body-seguimiento">
                <div id="contenidoSeguimiento">
                    <div class="loading">Cargando...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE BÚSQUEDA -->
    <div id="modalBusqueda" class="modal-seguimiento" style="display: none;">
        <div class="modal-content-seguimiento">
            <h2>Buscar Documento</h2>

            <label>DNI o RUC:</label>
            <input type="text" id="dniRuc" class="input-modal">

            <label>Número de Expediente:</label>
            <input type="text" id="numeroExpediente" class="input-modal">

            <button id="btnBuscarDocumento" class="btn-modal">Buscar</button>
        </div>
    </div>

    <!-- Ahora sí cargamos el JS de notificaciones normalmente -->
    <script src="../../backend/js/notificaciones.js"></script>

    <!-- jQuery (obligatorio para DataTables y scripts con $) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Responsive JS -->
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        // ==================== MOSTRAR MODAL AUTOMÁTICO ====================
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("modalBusqueda").style.display = "flex";
        });

        // ==================== DATATABLE ====================
        $(document).ready(function() {

            window.tabla = $('#tablaResultados').DataTable({
                responsive: true,
                ajax: {
                    url: '../../backend/php/ajax/buscar_documento_publico.php',
                    dataSrc: function(json) {

                        // ✅ MARCAR ÚLTIMO MOVIMIENTO DE CADA DOCUMENTO
                        let ultimoPorDoc = {};
                        json.forEach(item => {
                            if (!ultimoPorDoc[item.IdDocumentos] || new Date(item.FechaMovimiento) > new Date(ultimoPorDoc[item.IdDocumentos].FechaMovimiento)) {
                                ultimoPorDoc[item.IdDocumentos] = item;
                            }
                        });

                        // Añadir un campo extra
                        json.forEach(item => {
                            item.ultimoMovimiento = (ultimoPorDoc[item.IdDocumentos] === item);
                        });

                        return json;
                    }
                },
                columns: [{
                        data: 'NumeroDocumento',
                        title: 'Expediente'
                    },
                    {
                        data: 'Asunto',
                        title: 'Asunto'
                    },
                    {
                        data: null,
                        title: 'Área Origen',
                        render: r => r.OrigenNombre || `[ID ${r.AreaOrigen}]`
                    },
                    {
                        data: null,
                        title: 'Área Destino',
                        render: r => r.DestinoNombre || `[ID ${r.AreaDestino}]`
                    },
                    {
                        data: 'FechaMovimiento',
                        title: 'Fecha Movimiento'
                    },
                    {
                        data: 'NumeroFolios',
                        title: 'Folios'
                    },
                    {
                        data: 'InformeNombre',
                        title: 'Informe',
                        render: d => d ? d : '-'
                    },
                    { // ESTADO
                        data: null,
                        title: 'Estado',
                        render: function(row) {
                            if (row.ultimoMovimiento) {
                                if (row.IdEstadoDocumento == 8) return "🚫 Bloqueado";
                                if (row.Finalizado == 1) return "🏁 Finalizado";
                                if (row.Recibido == 1) return "✅ Recibido";
                                return "⏳ Pendiente";
                            } else {
                                return row.Recibido == 1 ? "✅ Recibido" : "⏳ Pendiente";
                            }
                        }
                    },
                    {
                        data: 'Observacion',
                        title: 'Observación',
                        render: d => d ? d : '-'
                    }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });
        });

        // ==================== BOTÓN BUSCAR ====================
        document.getElementById("btnBuscarDocumento").addEventListener("click", function() {

            let dniRuc = document.getElementById("dniRuc").value.trim();
            let expediente = document.getElementById("numeroExpediente").value.trim();

            if (dniRuc === "" || expediente === "") {
                alert("Debes ingresar DNI/RUC y Número de Expediente");
                return;
            }

            tabla.ajax.url(
                "../../backend/php/ajax/buscar_documento_publico.php?dni_ruc=" + dniRuc + "&expediente=" + expediente
            ).load();

            document.getElementById("modalBusqueda").style.display = "none";
        });
    </script>

</body>

</html>