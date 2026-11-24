/** Agrega el token de sesión a los formularios y permite renovar una carga fallida. */
"use strict";
document.addEventListener("DOMContentLoaded", () => {
  const formularios = [...document.querySelectorAll("form[data-csrf]")];
  if (!formularios.length) return;
  let token = formularios[0].querySelector('input[name="csrf"]')?.value || "";
  const campos = formularios.map((formulario) => {
    let campo = formulario.querySelector('input[name="csrf"]');
    if (!campo) {
      campo = document.createElement("input");
      campo.type = "hidden";
      campo.name = "csrf";
      formulario.append(campo);
    }
    const aviso = document.createElement("p");
    aviso.setAttribute("role", "alert");
    aviso.className = "error-campo";
    aviso.hidden = true;
    formulario.prepend(aviso);
    formulario.addEventListener("submit", (evento) => {
      if (token) return;
      evento.preventDefault();
      aviso.hidden = false;
      aviso.textContent =
        "Se está renovando la protección del formulario. Vuelve a enviarlo en unos segundos.";
      cargar();
    });
    return { campo, aviso };
  });
  let pendiente = false;
  async function cargar() {
    if (pendiente) return;
    pendiente = true;
    try {
      const respuesta = await fetch("token.php", {
        credentials: "same-origin",
        cache: "no-store",
      });
      if (!respuesta.ok) throw new Error();
      const datos = await respuesta.json();
      if (typeof datos.csrf !== "string" || !datos.csrf) throw new Error();
      token = datos.csrf;
      campos.forEach(({ campo, aviso }) => {
        campo.value = token;
        aviso.hidden = true;
      });
    } catch {
      token = "";
      campos.forEach(({ aviso }) => {
        aviso.hidden = false;
        aviso.textContent =
          "No se pudo cargar la protección del formulario. Comprueba la conexión y vuelve a enviarlo para reintentar.";
      });
    } finally {
      pendiente = false;
    }
  }
  cargar();
  window.addEventListener("pageshow", (evento) => {
    if (evento.persisted) cargar();
  });
});
