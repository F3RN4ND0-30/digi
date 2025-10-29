document.addEventListener('DOMContentLoaded', function () {
    const btnCampana = document.getElementById('notificaciones');
    const lista = document.getElementById('listaNotificaciones');
    const contenedor = document.getElementById('contenedorNotificaciones');
    const contador = document.getElementById('contador');

    // IDs de notificaciones ya mostradas (persistente y compartido entre pestañas)
    let notificacionesMostradas = JSON.parse(localStorage.getItem('notificacionesMostradas')) || [];

    // 🔔 Mostrar notificación del navegador
    function mostrarNotificacion(mensaje, url, id) {
        if (!("Notification" in window)) return;

        // Evitar duplicados globales
        notificacionesMostradas = JSON.parse(localStorage.getItem('notificacionesMostradas')) || [];
        if (notificacionesMostradas.includes(id)) return;

        const mostrar = () => {
            const noti = new Notification(mensaje, {
                icon: 'https://cdn-icons-png.flaticon.com/512/4315/4315609.png'
            });
            noti.onclick = () => window.open(url, '_blank');

            // Guardar ID inmediatamente después de mostrar
            notificacionesMostradas.push(id);
            localStorage.setItem('notificacionesMostradas', JSON.stringify(notificacionesMostradas));
        };

        if (Notification.permission === "granted") {
            mostrar();
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") mostrar();
            });
        }
    }

    // 📥 Cargar notificaciones desde el backend
    function cargarNotificaciones(mostrar = true) {
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
                        <button onclick="actualizarNotificacion(${n.IdNotificacion}, 'eliminar')" style="margin:4px; background: transparent; border: none; cursor: pointer; margin-left: 80px">X</button>
                    `;
                    contenedor.appendChild(li);
                });

                contador.textContent = data.length;

                // 🔔 Controlar notificaciones nuevas
                data.forEach(n => {
                    const id = n.IdNotificacion;
                    let url = 'https://digi.munipisco.gob.pe/frontend/sisvis/escritorio.php';
                    const msg = n.Mensaje.toLowerCase();

                    if (msg.includes("ha sido recepcionado")) {
                        url = 'https://digi.munipisco.gob.pe/frontend/seguimiento/busqueda.php';
                    } else if (msg.includes("has recibido un documento")) {
                        url = 'https://digi.munipisco.gob.pe/frontend/archivos/recepcion.php';
                    }

                    if (!notificacionesMostradas.includes(id)) {
                        if (mostrar) {
                            // Mostrar solo nuevas
                            mostrarNotificacion(n.Mensaje, url, id);
                        } else {
                            // Primera carga: marcar como mostradas sin notificar
                            notificacionesMostradas.push(id);
                            localStorage.setItem('notificacionesMostradas', JSON.stringify(notificacionesMostradas));
                        }
                    }
                });
            })
            .catch(err => console.error("Error cargando notificaciones:", err));
    }

    // ⚙️ Actualizar estado (vista o eliminar)
    window.actualizarNotificacion = function (id, accion) {
        fetch('../../backend/php/notificaciones/actualizar_notificacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&accion=${accion}`
        }).then(() => cargarNotificaciones(false));
    };

    // 🛎️ Mostrar/ocultar lista
    btnCampana.addEventListener('click', () => {
        const visible = lista.style.display === 'block';
        lista.style.display = visible ? 'none' : 'block';
        if (!visible) contador.textContent = '';
    });

    // 🚀 Inicial: no mostrar notificaciones antiguas
    cargarNotificaciones(false);

    // 🔁 Luego cada 30s, mostrar solo nuevas
    setInterval(() => cargarNotificaciones(true), 30000);

    // 🔄 Sincronizar entre pestañas
    window.addEventListener('storage', (event) => {
        if (event.key === 'notificacionesMostradas') {
            notificacionesMostradas = JSON.parse(event.newValue) || [];
        }
    });
});
