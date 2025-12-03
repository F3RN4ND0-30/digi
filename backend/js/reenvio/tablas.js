/* ============================================================
   DATATABLES + SELECTIZE ÁREAS
   ============================================================ */

const LANG_ES = {
    decimal: ",",
    thousands: ".",
    processing: "Procesando…",
    search: "Buscar:",
    lengthMenu: "Mostrar _MENU_ registros",
    info: "Mostrando _START_ a _END_ de _TOTAL_",
    infoEmpty: "Mostrando 0 a 0 de 0",
    infoFiltered: "(filtrado de _MAX_ total)",
    loadingRecords: "Cargando…",
    zeroRecords: "No se encontraron resultados",
    emptyTable: "Sin datos",
    paginate: { first: "Primero", previous: "Anterior", next: "Siguiente", last: "Último" }
};

let dtDocs, dtMemos;

$(function () {
    const baseCfg = {
        language: LANG_ES,
        responsive: false,
        autoWidth: true,
        scrollX: true,
        scrollCollapse: true,
        pageLength: 25,
        order: [[0, "desc"]]
    };

    dtDocs = $("#tablaDocs").DataTable(baseCfg);
    dtMemos = $("#tablaMemos").DataTable(baseCfg);

    $(".tab-btn").on("click", function () {
        $(".tab-btn").removeClass("active");
        $(this).addClass("active");

        const t = $(this).data("target");
        $(".tab-content").removeClass("active");
        $(t).addClass("active");

        setTimeout(() => {
            (t === "#tab-docs" ? dtDocs : dtMemos).columns.adjust();
        }, 80);
    });

    $("#tab-docs .sel-area").each(function () {
        const $s = $(this);
        const btn = $s.closest("tr").find(".btn-reenviar");

        $s.selectize({
            allowEmptyOption: true,
            placeholder: "Seleccione área",
            sortField: "text",
            dropdownParent: "body",
            onFocus: function() {
                this.clear(); // borra automáticamente al enfocar
                this.refreshOptions(false);
            },
            onChange(v) {
                if (v) btn.prop("disabled", false).removeClass("btn-secondary").addClass("btn-success");
                else btn.prop("disabled", true).removeClass("btn-success").addClass("btn-secondary");
            }
        });

        btn.addClass("btn-secondary").prop("disabled", true);
    });
});
