/** Confirmaciones y avisos comunes. Todo texto variable se inserta con textContent. */
"use strict";
window.confirmarAccion = ({ titulo, mensaje, confirmar = "Confirmar" }) =>
  new Promise((resolve) => {
    if (!window.HTMLDialogElement) {
      resolve(window.confirm(mensaje));
      return;
    }
    const anterior = document.activeElement;
    const dialogo = document.createElement("dialog");
    dialogo.className = "confirmacion-dialogo";
    dialogo.setAttribute("aria-labelledby", "titulo-confirmacion");
    dialogo.setAttribute("aria-describedby", "mensaje-confirmacion");
    const icono = document.createElement("span");
    icono.className = "confirmacion-icono";
    icono.innerHTML =
      '<i class="fas fa-calendar-xmark" aria-hidden="true"></i>';
    const encabezado = document.createElement("h2");
    encabezado.id = "titulo-confirmacion";
    encabezado.textContent = titulo;
    const parrafo = document.createElement("p");
    parrafo.id = "mensaje-confirmacion";
    parrafo.textContent = mensaje;
    const grupo = document.createElement("div");
    grupo.className = "confirmacion-botones";
    const volver = document.createElement("button");
    volver.type = "button";
    volver.className = "boton boton-secundario";
    volver.textContent = "Volver";
    const aceptar = document.createElement("button");
    aceptar.type = "button";
    aceptar.className = "boton boton-peligro";
    aceptar.textContent = confirmar;
    grupo.append(volver, aceptar);
    dialogo.append(icono, encabezado, parrafo, grupo);
    document.body.append(dialogo);
    const overflowAnterior = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    let terminado = false;
    function terminar(valor) {
      if (terminado) return;
      terminado = true;
      dialogo.close();
      dialogo.remove();
      document.body.style.overflow = overflowAnterior;
      anterior?.focus({ preventScroll: true });
      resolve(valor);
    }
    volver.addEventListener("click", () => terminar(false));
    aceptar.addEventListener("click", () => terminar(true));
    dialogo.addEventListener("cancel", (evento) => {
      evento.preventDefault();
      terminar(false);
    });
    dialogo.addEventListener("click", (evento) => {
      const rect = dialogo.getBoundingClientRect();
      if (
        evento.target === dialogo &&
        (evento.clientX < rect.left ||
          evento.clientX > rect.right ||
          evento.clientY < rect.top ||
          evento.clientY > rect.bottom)
      )
        terminar(false);
    });
    dialogo.showModal();
    volver.focus();
  });

window.notificar = (texto, error = false) => {
  document.querySelector(".notificacion")?.remove();
  const aviso = document.createElement("div");
  aviso.className = `notificacion${error ? " error" : ""}`;
  aviso.setAttribute("role", error ? "alert" : "status");
  const icono = document.createElement("i");
  icono.className = `fas ${error ? "fa-circle-exclamation" : "fa-circle-check"}`;
  icono.setAttribute("aria-hidden", "true");
  const mensaje = document.createElement("span");
  mensaje.textContent = texto;
  const cerrar = document.createElement("button");
  cerrar.type = "button";
  cerrar.textContent = "×";
  cerrar.setAttribute("aria-label", "Cerrar aviso");
  cerrar.addEventListener("click", () => aviso.remove());
  aviso.append(icono, mensaje, cerrar);
  document.body.append(aviso);
  window.setTimeout(() => aviso.remove(), error ? 12000 : 7000);
};
