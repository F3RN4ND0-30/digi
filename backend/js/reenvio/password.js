/* ============================================================
   MODAL PASSWORD
   ============================================================ */

let formularioActual = null;

function cerrarModalPassword() {
    document.getElementById("modalPassword").style.display = "none";
    document.getElementById("passwordInput").value = "";
    document.getElementById("passwordError").style.display = "none";
}

function togglePassword() {
    const i = document.getElementById("passwordInput");
    const ic = document.querySelector(".btn-toggle-pass i");
    if (i.type === "password") {
        i.type = "text";
        ic.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        i.type = "password";
        ic.classList.replace("fa-eye-slash", "fa-eye");
    }
}

function enviarFormularioConPassword() {
    if (formularioActual) formularioActual.submit();
}

function confirmarPassword() {
    const password = document.getElementById("passwordInput").value.trim();
    const errorDiv = document.getElementById("passwordError");

    if (!password) {
        errorDiv.innerText = "Ingrese su contraseña";
        errorDiv.style.display = "block";
        return;
    }

    fetch('../../backend/php/verificar_password.php', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password })
    })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cerrarModalPassword();
                enviarFormularioConPassword();
            } else {
                errorDiv.innerText = "Contraseña incorrecta";
                errorDiv.style.display = "block";
            }
        });
}

document.getElementById('passwordInput')
    ?.addEventListener("keypress", e => {
        if (e.key === "Enter") confirmarPassword();
    });
