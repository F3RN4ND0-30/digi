/* ==========================================
   VARIABLES
========================================== */

let expedienteOrigen  = null;
let expedienteDestino = null;

/* ==========================================
   INICIALIZACIÓN
========================================== */

document.addEventListener('DOMContentLoaded', function () {

    const btnAbrir = document.querySelector('.btn-axenar-documentos');
    if (btnAbrir) btnAbrir.addEventListener('click', abrirModalAnexar);

    document.getElementById('buscarOrigen')
        .addEventListener('keyup', () => buscarExpedientes('origen'));

    document.getElementById('buscarDestino')
        .addEventListener('keyup', () => buscarExpedientes('destino'));

    document.getElementById('btnConfirmarAnexo')
        .addEventListener('click', confirmarAnexo);
});

/* ==========================================
   MODAL
========================================== */

function abrirModalAnexar() {

    expedienteOrigen  = null;
    expedienteDestino = null;

    document.getElementById('buscarOrigen').value = '';
    document.getElementById('buscarDestino').value = '';

    document.getElementById('listaOrigen').innerHTML = '';
    document.getElementById('listaDestino').innerHTML = '';

    document.getElementById('origenSeleccionado').innerHTML  = '';
    document.getElementById('destinoSeleccionado').innerHTML = '';

    document.getElementById('btnConfirmarAnexo').disabled = true;
    document.getElementById('modalAnexarDocumento').style.display = 'flex';
}

function cerrarModalAnexar() {
    document.getElementById('modalAnexarDocumento').style.display = 'none';
}

/* ==========================================
   BUSCAR EXPEDIENTES
========================================== */

function buscarExpedientes(tipo) {

    const input = tipo === 'origen'
        ? document.getElementById('buscarOrigen')
        : document.getElementById('buscarDestino');

    const lista = tipo === 'origen'
        ? document.getElementById('listaOrigen')
        : document.getElementById('listaDestino');

    const q = input.value.trim();

    if (q.length < 2) {
        lista.innerHTML = '';
        return;
    }

    fetch('../../backend/php/archivos/buscar_expedientes.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {

            if (!data || data.length === 0) {
                lista.innerHTML = '<div class="item-vacio">Sin resultados</div>';
                return;
            }

            let html = '';

            data.forEach(d => {

                // ❌ No permitir seleccionar el mismo expediente
                if (
                    (tipo === 'origen'  && expedienteDestino == d.IdDocumentos) ||
                    (tipo === 'destino' && expedienteOrigen  == d.IdDocumentos)
                ) return;

                html += `
                    <div class="item-expediente"
                         data-tipo="${tipo}"
                         data-id="${d.IdDocumentos}"
                         data-numero="${d.NumeroDocumento}">
                        <strong>${d.NumeroDocumento}</strong>
                        <small>${d.Asunto}</small>
                    </div>
                `;
            });

            lista.innerHTML = html;
        });
}

/* ==========================================
   SELECCIONAR EXPEDIENTE
========================================== */

document.addEventListener('click', function (e) {

    const item = e.target.closest('.item-expediente');
    if (!item) return;

    const tipo = item.dataset.tipo;
    const id   = item.dataset.id;
    const num  = item.dataset.numero;

    if (tipo === 'origen') {
        expedienteOrigen = id;
        document.getElementById('origenSeleccionado')
            .innerHTML = `Origen: <strong>${num}</strong>`;
        document.getElementById('listaOrigen').innerHTML = '';
        document.getElementById('buscarOrigen').value = num;
    }

    if (tipo === 'destino') {
        expedienteDestino = id;
        document.getElementById('destinoSeleccionado')
            .innerHTML = `Destino: <strong>${num}</strong>`;
        document.getElementById('listaDestino').innerHTML = '';
        document.getElementById('buscarDestino').value = num;
    }

    validarConfirmacion();
});

/* ==========================================
   VALIDAR BOTÓN
========================================== */

function validarConfirmacion() {
    document.getElementById('btnConfirmarAnexo').disabled =
        !(expedienteOrigen && expedienteDestino);
}

/* ==========================================
   CONFIRMAR ANEXO
========================================== */

function confirmarAnexo() {

    if (!expedienteOrigen || !expedienteDestino) {
        alert('Debe seleccionar ambos expedientes');
        return;
    }

    if (expedienteOrigen === expedienteDestino) {
        alert('Los expedientes no pueden ser iguales');
        return;
    }

    if (!confirm('¿Desea anexar estos expedientes?')) return;

    const formData = new FormData();
    formData.append('id_documento_origen', expedienteOrigen);
    formData.append('id_documento_destino', expedienteDestino);
    formData.append('tipo_relacion',
        document.getElementById('tipoRelacion').value);

    fetch('../../backend/php/archivos/anexar_documento.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(resp => {

        if (resp.ok) {
            alert(resp.mensaje);
            cerrarModalAnexar();
        } else {
            alert(resp.mensaje || 'Error al anexar');
        }
    });
}
