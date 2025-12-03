/* ============================================================
   FORMULARIOS — Reenviar / Finalizar / Observar / Responder Memo
   Maneja correctamente td.dataset.informe y previene id null
   ============================================================ */

function getInformeFromRow(tr) {
    const td = tr.querySelector('.informe-input');
    return td?.dataset.informe || "";
}

// Habilita o deshabilita botones según exista idInforme
function actualizarBotonesInforme(tr) {
    const idInforme = getInformeFromRow(tr);
    const botones = tr.querySelectorAll(".btn-reenviar, .btn-finalizar, .btn-observar, .btn-responder-memo");

    botones.forEach(btn => {
        if (!idInforme && btn.classList.contains("btn-responder-memo")) {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    });
}

// Actualizar td.dataset.informe y todos los hidden inputs
function actualizarTDInforme(td, idInforme) {
    if (!td) return;
    td.dataset.informe = idInforme;

    // Buscar el TR
    const tr = td.closest("tr");

    // Actualizar todos los inputs hidden en el TR
    tr.querySelectorAll('input[name="id_informe"]').forEach(input => {
        input.value = idInforme;
    });

    // Badge
    let badge = td.querySelector(".badge");
    if (badge) {
        badge.textContent = `INFORME N°. ${idInforme}`;
    } else {
        const div = document.createElement("div");
        div.className = "mb-1";
        div.innerHTML = `<span class="badge bg-success" style="padding:.5rem 1rem;">INFORME N°. ${idInforme}</span>`;
        td.prepend(div);
    }

    actualizarBotonesInforme(tr);
}

// Enviar formularios
$(document).on("submit", ".form-reenviar, .finalizar-form, .observacion-form, .form-responder-memo", function (e) {
    e.preventDefault();

    const form = this;
    const tr = form.closest("tr");
    const $tr = $(tr);

    const fol = ($tr.find(".inp-folios, .memo-folios").val() || "").trim();
    const obs = ($tr.find(".inp-obs, .memo-obs").val() || "").trim();
    const idInforme = getInformeFromRow(tr);

    if (!fol || Number(fol) < 1) return toast("Ingrese número de folios válido");

    if ($(form).hasClass("form-responder-memo") && !idInforme)
        return toast("Debe crear un Informe antes de responder el memo.");

    form.querySelector('input[name="numero_folios"]').value = fol;
    form.querySelector('input[name="observacion"]').value = obs;
    form.querySelector('input[name="id_informe"]').value = idInforme;

    if ($(form).hasClass("form-reenviar")) {
        const area = ($tr.find('.sel-area').val() || "").trim();
        if (!area) return toast("Seleccione un área destino");
        form.querySelector('input[name="nueva_area"]').value = area;
    } else {
        form.querySelector('input[name="nueva_area"]').value = "";
    }

    formularioActual = form;
    document.getElementById("modalPassword").style.display = "flex";
});

// Detectar click en botón crear informe y actualizar td.dataset.informe y hidden inputs
$(document).on("click", ".btn-crear-informe", function () {
    const btn = this;
    const td = btn.closest(".informe-input");
    const tr = td.closest("tr");

    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Creando...`;

    const idDocumento = td.dataset.idDocumento || "";
    const body = `id_documento=${idDocumento}`;

    fetch('../../backend/php/archivos/crear_informe.php', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-plus"></i> Crear Informe`;

            if (data.status !== "success") {
                return Toast.fire({ icon: "error", title: data.message || "Error al crear informe" });
            }

            // Actualizar td con id_informe y todos los hidden inputs
            actualizarTDInforme(td, data.id_informe);
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-plus"></i> Crear Informe`;
            Toast.fire({ icon: "error", title: "Error al crear informe" });
        });
});
