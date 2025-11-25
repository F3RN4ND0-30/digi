document.addEventListener("DOMContentLoaded", function () {
  // 🔔 Elementos base
  const btnCampana = document.getElementById("notificaciones");
  const panel = document.getElementById("listaNotificaciones"); // .notification-panel
  const lista = document.getElementById("contenedorNotificaciones"); // <ul>
  const badge = document.getElementById("contador");

  if (!btnCampana || !panel || !lista || !badge) {
    console.warn(
      "[notificaciones] Faltan elementos del DOM (campana/panel/lista/badge)."
    );
    return;
  }

  // Evitar dobles inicializaciones si el archivo se carga dos veces por error
  if (window.__NOTI_INIT__) return;
  window.__NOTI_INIT__ = true;

  // 🧠 Persistencia de IDs mostrados (entre pestañas)
  let notificacionesMostradas =
    JSON.parse(localStorage.getItem("notificacionesMostradas")) || [];

  // ===== Utilidades =====
  const setPanelVisible = (visible) => {
    // Tu CSS del panel está pensado para flex
    panel.style.display = visible ? "flex" : "none";
    panel.setAttribute("aria-hidden", visible ? "false" : "true");
    // desactiva campanita ping cuando abres
    if (visible) btnCampana.classList.remove("has-new");
  };

  const isPanelVisible = () => panel.style.display === "flex";

  const safeHTML = (s) => {
    const div = document.createElement("div");
    div.textContent = s ?? "";
    return div.innerHTML;
  };

  // ===== Notificación nativa del navegador (opcional) =====
  function mostrarNotificacion(mensaje, url, id) {
    if (!("Notification" in window)) return;

    // Evitar duplicados globales (entre pestañas)
    notificacionesMostradas =
      JSON.parse(localStorage.getItem("notificacionesMostradas")) || [];
    if (notificacionesMostradas.includes(id)) return;

    const lanzar = () => {
      const noti = new Notification(mensaje, {
        icon: "https://cdn-icons-png.flaticon.com/512/4315/4315609.png",
      });
      noti.onclick = () => window.open(url, "_blank");

      // Persistir ID
      notificacionesMostradas.push(id);
      localStorage.setItem(
        "notificacionesMostradas",
        JSON.stringify(notificacionesMostradas)
      );
    };

    if (Notification.permission === "granted") {
      lanzar();
    } else if (Notification.permission !== "denied") {
      Notification.requestPermission().then((p) => {
        if (p === "granted") lanzar();
      });
    }
  }

  // ===== Render de ítem bonito (match con tu HTML del navbar) =====
  function renderItem(n) {
    const li = document.createElement("li");
    li.innerHTML = `
      <div class="notification-content">
        <div class="notification-icon"><i class="fas fa-file-alt"></i></div>
        <div class="notification-text">
          <p>${safeHTML(n.Mensaje)}</p>
          <span class="notification-time"><i class="far fa-clock"></i> ${safeHTML(
            n.FechaVisto || ""
          )}</span>
          <div class="notification-actions">
            <button class="btn-mark-read" data-accion="vista" data-id="${
              n.IdNotificacion
            }">
              <i class="fas fa-check"></i> Marcar como leída
            </button>
            <!--
            <button class="btn-delete" data-accion="eliminar" data-id="${
              n.IdNotificacion
            }">
              <i class="fas fa-trash"></i>
            </button>
            -->
          </div>
        </div>
      </div>
    `;
    return li;
  }

  // ===== Carga desde backend =====
  async function cargarNotificaciones(mostrarNuevas = true) {
    try {
      const res = await fetch(
        "../../backend/php/notificaciones/cargar_notificaciones.php"
      );
      const data = await res.json();

      // Limpia la lista
      lista.innerHTML = "";

      // Vacío
      if (!Array.isArray(data) || data.length === 0) {
        lista.innerHTML = `
          <div class="notification-empty">
            <i class="fas fa-bell-slash"></i>
            <p>No tienes notificaciones nuevas</p>
          </div>
        `;
        badge.textContent = "";
        btnCampana.classList.remove("has-new");
        return;
      }

      // Render items
      data.forEach((n) => lista.appendChild(renderItem(n)));

      // Badge
      badge.textContent = data.length;
      if (data.length > 0) btnCampana.classList.add("has-new");

      // Notificaciones nativas solo para nuevas
      data.forEach((n) => {
        const id = n.IdNotificacion;
        let url =
          "https://digi.munipisco.gob.pe/frontend/sisvis/escritorio.php";
        const msg = (n.Mensaje || "").toLowerCase();

        if (msg.includes("ha sido recepcionado")) {
          url =
            "https://digi.munipisco.gob.pe/frontend/seguimiento/busqueda.php";
        } else if (msg.includes("has recibido un documento")) {
          url = "https://digi.munipisco.gob.pe/frontend/archivos/recepcion.php";
        }

        if (!notificacionesMostradas.includes(id)) {
          if (mostrarNuevas) {
            mostrarNotificacion(n.Mensaje, url, id);
          } else {
            notificacionesMostradas.push(id);
            localStorage.setItem(
              "notificacionesMostradas",
              JSON.stringify(notificacionesMostradas)
            );
          }
        }
      });
    } catch (err) {
      console.error("[notificaciones] Error cargando notificaciones:", err);
    }
  }

  // ===== Acciones (leer / eliminar) con delegación =====
  async function actualizarNotificacion(id, accion) {
    try {
      await fetch(
        "../../backend/php/notificaciones/actualizar_notificacion.php",
        {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `id=${encodeURIComponent(id)}&accion=${encodeURIComponent(
            accion
          )}`,
        }
      );
      // Recargar sin disparar nativas (porque no son “nuevas”)
      await cargarNotificaciones(false);
    } catch (err) {
      console.error("[notificaciones] Error actualizando notificación:", err);
    }
  }

  // Exponer global por compatibilidad (si tienes onClick inline en markup antiguo)
  window.actualizarNotificacion = actualizarNotificacion;

  // Delegación de clicks dentro de la lista para botones
  lista.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-accion][data-id]");
    if (!btn) return;
    const accion = btn.getAttribute("data-accion");
    const id = btn.getAttribute("data-id");
    actualizarNotificacion(id, accion);
  });

  // ===== Toggle del panel (campana) =====
  btnCampana.addEventListener("click", (e) => {
    e.stopPropagation(); // evita que el “click fuera” lo cierre inmediatamente
    const visible = isPanelVisible();
    // Cierra dropdowns del navbar si existieran (no rompe si no hay)
    document
      .querySelectorAll(".dropdown-menu")
      .forEach((m) => (m.style.display = "none"));
    document
      .querySelectorAll(".nav-dropdown")
      .forEach((d) => d.classList.remove("active"));

    setPanelVisible(!visible);
    if (!visible) {
      // Al abrir, refresca la lista
      cargarNotificaciones(false);
      // Limpia el badge (ya “vistas” en la UI)
      badge.textContent = "";
    }
  });

  // Cerrar al hacer click fuera del componente
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".notificaciones-modern")) {
      setPanelVisible(false);
    }
  });

  // ===== Ciclo de vida =====
  // Primera carga: NO mostrar nativas antiguas
  cargarNotificaciones(false);
  // Intervalo: mostrar nativas solo para nuevas
  setInterval(() => cargarNotificaciones(true), 30000);

  // Sync entre pestañas
  window.addEventListener("storage", (event) => {
    if (event.key === "notificacionesMostradas") {
      notificacionesMostradas = JSON.parse(event.newValue) || [];
    }
  });
});
