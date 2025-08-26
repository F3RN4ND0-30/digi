$(document).ready(function () {
    $('#dni_ruc').on('blur', function () {
        const documento = $(this).val().trim();

        if (documento.length !== 8 && documento.length !== 11) {
            return;
        }

        // 1. Buscar en la base de datos local
        $.ajax({
            url: '../../backend/php/archivos/buscar_contribuyente.php',
            method: 'GET',
            data: { documento },
            dataType: 'json',
            success: function (response) {
                if (response.nombre) {
                    $('#nombre_contribuyente').val(response.nombre);
                } else {
                    // 2. Si no se encontró en la BD, buscar en RENIEC o SUNAT
                    buscarEnAPIPublica(documento);
                }
            },
            error: function () {
                $('#nombre_contribuyente').val('');
                alert('Error al consultar contribuyente.');
            }
        });
    });

    function buscarEnAPIPublica(documento) {
        let apiUrl = '';
        if (documento.length === 8) {
            apiUrl = '../../backend/php/api/reniec_dni.php?dni=' + documento;
        } else if (documento.length === 11) {
            apiUrl = '../../backend/php/api/reniec_ruc.php?ruc=' + documento;
        } else {
            return;
        }

        $.ajax({
            url: apiUrl,
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.status === 'success') {
                    if (data.razon_social) {
                        $('#nombre_contribuyente').val(data.razon_social);
                    } else if (data.prenombres && data.apPrimer && data.apSegundo) {
                        const nombreCompleto = `${data.prenombres.trim()} ${data.apPrimer.trim()} ${data.apSegundo.trim()}`;
                        $('#nombre_contribuyente').val(nombreCompleto);
                    } else {
                        $('#nombre_contribuyente').val('');
                        alert('Contribuyente no encontrado en servicios públicos.');
                    }
                } else {
                    $('#nombre_contribuyente').val('');
                    alert(data.message || 'Error al obtener datos del contribuyente.');
                }
            },
            error: function () {
                $('#nombre_contribuyente').val('');
                alert('Error al consultar RENIEC/SUNAT.');
            }
        });
    }
});
