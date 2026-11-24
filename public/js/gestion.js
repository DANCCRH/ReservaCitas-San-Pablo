/** Eventos comunes de administración, perfil, cancelación e impresión. */
"use strict";
document.addEventListener("DOMContentLoaded", () => {
  const roles = document.querySelector("form[data-roles]");
  if (roles) {
    const actualizar = () =>
      roles.querySelectorAll("[data-rol-campo]").forEach((c) => {
        c.hidden = c.dataset.rolCampo !== roles.elements.rol.value;
      });
    roles.elements.rol.addEventListener("change", actualizar);
    actualizar();
  }
  document
    .querySelectorAll("[data-imprimir]")
    .forEach((b) => b.addEventListener("click", () => window.print()));
  document.querySelectorAll("form[data-claves]").forEach((f) => {
    const nueva = f.elements.nueva,
      confirmar = f.elements.confirmar;
    function validar() {
      nueva.setCustomValidity(
        nueva.value &&
          (!/[A-Za-z]/.test(nueva.value) || !/[0-9]/.test(nueva.value))
          ? "Incluye letras y números."
          : "",
      );
      confirmar.setCustomValidity(
        confirmar.value && confirmar.value !== nueva.value
          ? "Las contraseñas no coinciden."
          : "",
      );
    }
    nueva.addEventListener("input", validar);
    confirmar.addEventListener("input", validar);
  });
  document
    .querySelectorAll("form[data-confirmar],form[data-accion-cita]")
    .forEach((f) => {
      let aceptado = false;
      function motivo() {
        if (f.elements.motivo)
          f.elements.motivo.required = ["cancelado", "rechazado"].includes(
            f.elements.accion.value,
          );
      }
      f.elements.accion?.addEventListener("change", motivo);
      motivo();
      f.addEventListener("submit", async (e) => {
        if (aceptado) return;
        e.preventDefault();
        if (!f.reportValidity()) return;
        const mensaje =
          f.dataset.confirmar ||
          `Se aplicará la acción seleccionada a esta cita${f.elements.motivo?.value ? " y se registrará su motivo" : ""}.`;
        if (
          await window.confirmarAccion({
            titulo: "Confirmar cambio",
            mensaje,
            confirmar: "Aplicar cambio",
          })
        ) {
          aceptado = true;
          f.requestSubmit();
        }
      });
    });
  document.querySelectorAll("form[data-cancelar]").forEach((f) => {
    f.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!f.reportValidity()) return;
      if (
        !(await window.confirmarAccion({
          titulo: "¿Cancelar tu cita?",
          mensaje:
            "El turno se liberará y el motivo quedará en el historial. Esta acción no se puede deshacer.",
          confirmar: "Sí, cancelar cita",
        }))
      )
        return;
      const b = f.querySelector("button");
      b.disabled = true;
      try {
        const r = await fetch(f.action, {
          method: "POST",
          body: new FormData(f),
          credentials: "same-origin",
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || "No se pudo cancelar la cita.");
        location.reload();
      } catch (error) {
        window.notificar(error.message, true);
        b.disabled = false;
      }
    });
  });
  const galeria = document.getElementById("galeria-modal");
  galeria?.addEventListener("show.bs.modal", (e) => {
    const origen = e.relatedTarget;
    const img = galeria.querySelector("img");
    img.src = origen.dataset.imagen;
    img.alt = origen.dataset.descripcion;
    galeria.querySelector(".modal-title").textContent = origen.dataset.titulo;
  });
});
