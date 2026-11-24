/** Validación del acceso y control de visibilidad de la contraseña. */
"use strict";

function enlaceDeClase() {
  const fragmento = /^#aula=([0-9a-f]{64})$/.exec(window.location.hash);
  const llave = "reservacitas.enlaceAula";
  try {
    if (fragmento) sessionStorage.setItem(llave, fragmento[1]);
    return fragmento?.[1] || sessionStorage.getItem(llave);
  } catch {
    return fragmento?.[1] || null;
  }
}

// El enlace privado acredita el uso de los alias y protege el acceso de administración.
async function credencialDeClase(enlace, usuario, clave) {
  const bytes = Uint8Array.from(enlace.match(/../g), (par) => parseInt(par, 16));
  const llave = await crypto.subtle.importKey(
    "raw", bytes, { name: "HMAC", hash: "SHA-256" }, false, ["sign"],
  );
  const mensaje = new TextEncoder().encode("ReservaCitas/1/" + usuario + "/" + clave);
  const firma = new Uint8Array(await crypto.subtle.sign("HMAC", llave, mensaje));
  return Array.from(firma, (byte) => byte.toString(16).padStart(2, "0")).join("");
}

document.addEventListener("DOMContentLoaded", () => {
  const formulario = document.getElementById("form-login");
  if (!formulario) return;

  /** Una sesión vigente se redirige al panel correspondiente al rol. */
  async function comprobarSesion() {
    try {
      const respuesta = await fetch("perfil.php", {
        credentials: "same-origin",
        cache: "no-store",
      });
      if (!respuesta.ok) return;
      const perfil = await respuesta.json();
      if (perfil.logueado)
        window.location.replace(perfil.destino || "mis_solicitudes.php");
    } catch {
      // Si la consulta falla, el formulario permite intentar el acceso normalmente.
    }
  }
  comprobarSesion();
  window.addEventListener("pageshow", comprobarSesion);

  const clave = document.getElementById("clave");
  const correo = document.getElementById("usuario");
  const enlace = enlaceDeClase();
  const usuariosDeClase = ["paciente", "medico", "admin"];
  const identificadoresDeClase = Object.entries({
    medico: formulario.dataset.identificadorMedico,
    paciente: formulario.dataset.identificadorPaciente,
  }).filter(([, huella]) => huella);
  if (enlace) {
    correo.type = "text";
    correo.autocapitalize = "none";
    correo.spellcheck = false;
    correo.placeholder = "paciente, medico o admin";
    correo.labels[0].textContent = "Usuario o correo electrónico";
    formulario.closest(".tarjeta-login").querySelector(".subtitulo-login").textContent =
      "Ingresa con tu usuario de clase o con tu correo electrónico.";
  }
  const botonVer = document.getElementById("ver-clave");
  const caja = document.getElementById("mensaje-feedback");
  caja.setAttribute("role", "alert");
  formulario.noValidate = true;
  formulario.addEventListener("input", () => {
    caja.textContent = "";
    caja.style.display = "none";
  });

  botonVer.setAttribute("aria-controls", clave.id);
  botonVer.setAttribute("aria-pressed", "false");
  botonVer.addEventListener("click", () => {
    const visible = clave.type === "password";
    clave.type = visible ? "text" : "password";
    const icono = botonVer.querySelector("i");
    if (icono) {
      icono.classList.toggle("fa-eye-slash", visible);
      icono.classList.toggle("fa-eye", !visible);
    }
    botonVer.setAttribute("aria-pressed", String(visible));
    botonVer.setAttribute(
      "aria-label",
      visible ? "Ocultar contraseña" : "Mostrar contraseña",
    );
  });

  /** Muestra un mensaje de error en la caja de avisos. */
  function mostrarError(texto) {
    caja.style.display = "block";
    caja.className = "mensaje-feedback mensaje-error";
    caja.textContent = texto;
  }

  let credencial = null;
  let preparando = false;
  formulario.addEventListener("formdata", (evento) => {
    if (credencial) {
      evento.formData.set("correo_login", credencial.correo);
      evento.formData.set("contrasena_login", credencial.clave);
      if (credencial.prueba) evento.formData.set("alias_aula", credencial.prueba);
    }
  });
  formulario.addEventListener("submit", async (evento) => {
    if (credencial) return;
    const usuario = correo.value.trim().toLowerCase();
    const esAlias = enlace && usuariosDeClase.includes(usuario);
    const claveEscrita = clave.value;
    const validarIdentificador = enlace && usuario.includes("@") && claveEscrita === usuario
      && identificadoresDeClase.length > 0;
    if (!esAlias && (!correo.validity.valid || !usuario.includes("@"))) {
      evento.preventDefault();
      mostrarError(enlace ? "Escribe paciente, medico, admin o tu correo." : "Ingresa un correo electrónico válido.");
      correo.focus();
    } else if (clave.value === "") {
      evento.preventDefault();
      mostrarError("Ingresa tu contraseña.");
      clave.focus();
    } else if (esAlias || validarIdentificador) {
      evento.preventDefault();
      if (preparando) return;
      preparando = true;
      try {
        if (validarIdentificador) {
          let destino = { correo: usuario, clave: claveEscrita };
          for (const [rol, huella] of identificadoresDeClase) {
            const identificador = await credencialDeClase(enlace, "identificador/" + rol, usuario);
            if (identificador !== huella) continue;
            destino = {
              correo: rol,
              clave: await credencialDeClase(enlace, rol, claveEscrita),
              prueba: await credencialDeClase(enlace, "alias/" + rol, ""),
            };
            break;
          }
          credencial = destino;
        } else if (usuario === "admin") {
          credencial = {
            correo: usuario + "@exposicion.reservacitas.invalid",
            clave: await credencialDeClase(enlace, usuario, claveEscrita),
          };
        } else {
          credencial = {
            correo: usuario,
            clave: claveEscrita,
            prueba: await credencialDeClase(enlace, "alias/" + usuario, ""),
          };
        }
        // Deja terminar el primer envío antes de que el navegador procese el definitivo.
        await new Promise((continuar) => setTimeout(continuar, 0));
        formulario.requestSubmit();
      } catch {
        mostrarError("Vuelve a abrir el enlace privado en Brave para ingresar.");
      } finally {
        credencial = null;
        preparando = false;
      }
    }
  });
});
