
document.addEventListener('DOMContentLoaded', function () {
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
                        <button onclick="actualizarNotificacion(${n.IdNotificacion}, 'vista')" style="margin:4px;">👁️ Marcar como vista</button>
                        <button onclick="actualizarNotificacion(${n.IdNotificacion}, 'eliminar')" style="margin:4px;">🗑️ Eliminar</button>
                    `;
                    contenedor.appendChild(li);
                });

                contador.textContent = data.length;
            });
    }

    // Función para actualizar estado
    window.actualizarNotificacion = function (id, accion) {
        fetch('../../backend/php/notificaciones/actualizar_notificacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}&accion=${accion}`
        }).then(() => cargarNotificaciones());
    }

    // Mostrar/ocultar lista
    btnCampana.addEventListener('click', () => {
        const visible = lista.style.display === 'block';
        lista.style.display = visible ? 'none' : 'block';
        if (!visible) {
            contador.textContent = ''; // borra contador al abrir
        }
    });

    cargarNotificaciones();
    setInterval(cargarNotificaciones, 30000); // cada 30s
});
