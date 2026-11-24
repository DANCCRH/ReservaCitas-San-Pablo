"use strict";

// La habilitación de las acciones sigue a cargo del servidor.
(() => {
  const filas = Array.from(document.querySelectorAll(".controles-atencion-medica"));
  const esperando = filas.filter(fila => fila.dataset.espera);
  const plazos = filas.filter(fila => Number(fila.dataset.cambio) > 0)
    .map(fila => Number(fila.dataset.cambio) - Number(fila.dataset.ahora));
  if (!esperando.length && !plazos.length) return;

  const vence = plazos.length ? Date.now() + (Math.min(...plazos) + 1) * 1000 : Infinity;
  const aviso = document.querySelector("[data-actualizar-atencion]");
  let editado = false;
  let cambio = false;
  let consultando = false;
  let temporizador;
  document.addEventListener("input", () => { editado = true; });

  function actualizar() {
    if (editado || document.querySelector("details[open], dialog[open], [aria-modal=true]:not([hidden])")
        || document.activeElement?.closest("form, .gestion-cita-celda")) {
      aviso.hidden = false;
    } else {
      location.reload();
    }
  }

  async function comprobar() {
    clearTimeout(temporizador);
    if (document.hidden || consultando) return;
    if (cambio || Date.now() >= vence) {
      actualizar();
      return;
    }
    consultando = true;
    try {
      for (let i = 0; i < esperando.length; i += 250) {
        const grupo = esperando.slice(i, i + 250);
        const ids = grupo.map(fila => fila.dataset.cita).join(",");
        const respuesta = await fetch("estado-atencion?ids=" + ids, {cache: "no-store"});
        if (!respuesta.ok) break;
        const datos = await respuesta.json();
        if (grupo.some(fila => datos.esperando[fila.dataset.cita] !== fila.dataset.espera)) {
          cambio = true;
          break;
        }
      }
    } catch {
      // Una conexión interrumpida se reintenta en el siguiente ciclo.
    } finally {
      consultando = false;
    }
    if (document.hidden) return;
    if (cambio || Date.now() >= vence) {
      actualizar();
      return;
    }
    const intervalo = esperando.length ? 30000 : 2147483647;
    temporizador = setTimeout(comprobar, Math.min(vence - Date.now(), intervalo));
  }

  // Al reabrir la pestaña, comprueba las llegadas registradas mientras estaba oculta.
  document.addEventListener("visibilitychange", comprobar);
  window.addEventListener("pageshow", comprobar);
})();
