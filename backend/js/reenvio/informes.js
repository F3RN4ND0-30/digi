/* ============================================================
   INFORMES — MÓDULO COMPLETO
   - Crear informe desde tabla para documentos y memorándums
   - Actualizar correlativo automáticamente
   - Mostrar nombre del informe creado
   ============================================================ */

const idArea = parseInt(window.SESSION_AREA_ID || 0, 10);

// Inicializar Toast (SweetAlert2)
document.addEventListener('DOMContentLoaded', () => {
    window.Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
});

/* ---------- CORRELATIVO ---------- */
function actualizarCorrelativo() {
    const correlativoLabel = document.getElementById('correlativoLabel');
    if (!correlativoLabel) return;

    const areaId = parseInt(correlativoLabel.dataset.area || 0, 10);
    correlativoLabel.textContent = 'Siguiente Informe: CARGANDO...';

    fetch(`../../backend/php/archivos/obtener_correlativo_informe.php?area=${areaId}`)
        .then(r => r.json())
        .then(d => {
            correlativoLabel.textContent = `Siguiente Informe: ${d.correlativo}-${d.año}`;
        })
        .catch(() => {
            correlativoLabel.textContent = 'Siguiente Informe: ERROR';
        });
}
document.addEventListener('DOMContentLoaded', actualizarCorrelativo);

/* ---------- CREAR INFORME DESDE TABLA ---------- */
document.addEventListener("click", function (e) {
    if (!e.target.closest(".btn-crear-informe")) return;

    const btn = e.target.closest(".btn-crear-informe");
    const td = btn.closest(".informe-input");

    const idDocumento = td.dataset.idDocumento || "";
    const idMemo = td.dataset.idMemo || "";

    if (!idDocumento && !idMemo) {
        Toast.fire({ icon: "error", title: "No se encontró documento ni memorándum para crear informe." });
        return;
    }

    const body = idDocumento
        ? `id_documento=${idDocumento}`
        : `id_memo=${idMemo}`;

    btn.disabled = true;
    const old = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Creando...`;

    fetch('../../backend/php/archivos/crear_informe.php', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = old;

            if (data.status !== "success") {
                Toast.fire({ icon: "error", title: data.message || "Error al crear informe" });
                return;
            }

            // Guardar id_informe en el td
            td.dataset.informe = data.id_informe;

            // Mostrar el nombre del informe creado
            let container = td.querySelector(".informes-previos");
            if (!container) {
                container = document.createElement("div");
                container.className = "informes-previos";
                td.appendChild(container);
            }
            container.innerHTML = `
                <div><span class="badge bg-success" style="padding:.5rem 1rem;">${data.nombre_final}</span></div>
            `;

            // Ocultar botón después de crear informe
            btn.style.display = 'none';

            // Actualizar correlativo
            actualizarCorrelativo();
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = old;
            Toast.fire({ icon: "error", title: "Error al crear informe" });
        });
});

/* ---------- CARGAR INFORMES PREVIOS ---------- */
function cargarInformesEnTabla() {
    document.querySelectorAll(".informe-input").forEach(td => {
        const idDoc = td.dataset.idDocumento || 0;
        const idMemo = td.dataset.idMemo || 0;

        let url = "";
        if (idDoc > 0) url = `../../backend/php/archivos/obtener_informes.php?id_documento=${idDoc}&ultimo=1`;
        else if (idMemo > 0) url = `../../backend/php/archivos/obtener_informe_memo.php?id_memo=${idMemo}&ultimo=1`;

        if (!url) return;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                const container = td.querySelector(".informes-previos");
                if (!container) return;

                container.innerHTML = "";

                if (Array.isArray(data) && data.length > 0) {
                    const inf = data[0]; // Solo el último
                    td.dataset.informe = inf.IdInforme; // Guardar el id del informe
                    const span = document.createElement("span");
                    span.className = "badge bg-secondary me-1";
                    span.textContent = inf.NombreInforme;
                    container.appendChild(span);

                    // Ocultar botón si ya existe informe
                    //const btn = td.querySelector(".btn-crear-informe");
                    //if (btn) btn.style.display = 'none';
                } else {
                    td.dataset.informe = ""; // No hay informe
                }
            });
    });
}
document.addEventListener('DOMContentLoaded', cargarInformesEnTabla);
