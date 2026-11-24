/** Validación del registro y control de visibilidad de las contraseñas. */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const formulario = document.getElementById("form-registro");
  if (!formulario) return;
  const caja = document.getElementById("mensaje-feedback");
  caja.setAttribute("role", "alert");
  formulario.noValidate = true;
  formulario.addEventListener("input", () => {
    caja.textContent = "";
    caja.style.display = "none";
  });

  /** Asocia un botón con ojo a un campo de contraseña. */
  function configurarOcultarClave(botonId, inputId) {
    const boton = document.getElementById(botonId);
    const input = document.getElementById(inputId);
    boton.setAttribute("aria-controls", inputId);
    boton.setAttribute("aria-pressed", "false");
    boton.addEventListener("click", () => {
      const visible = input.type === "password";
      input.type = visible ? "text" : "password";
      boton.setAttribute(
        "aria-label",
        visible ? "Ocultar contraseña" : "Mostrar contraseña",
      );
      const icono = boton.querySelector("i");
      if (icono) {
        icono.classList.toggle("fa-eye-slash", visible);
        icono.classList.toggle("fa-eye", !visible);
      }
      boton.setAttribute("aria-pressed", String(visible));
    });
  }
  configurarOcultarClave("ver-clave", "password");
  configurarOcultarClave("ver-clave2", "confirmPassword");

  /** Muestra un mensaje de error en la caja de avisos. */
  function mostrarError(texto, campo) {
    caja.style.display = "block";
    caja.className = "mensaje-feedback mensaje-error";
    caja.textContent = texto;
    if (campo) campo.focus();
  }

  formulario.addEventListener("submit", (evento) => {
    const nombre = document.getElementById("fullName");
    const dni = document.getElementById("document");
    const correo = document.getElementById("email");
    const clave = document.getElementById("password");
    const confirmar = document.getElementById("confirmPassword");
    const terminos = document.getElementById("terminos");

    let error = null;
    if (nombre.value.trim().length < 5 || nombre.value.trim().length > 150)
      error = ["Ingresa tu nombre completo (mínimo 5 caracteres).", nombre];
    else if (
      !{
        DNI: /^\d{8}$/,
        CE: /^[A-Z0-9]{9,12}$/,
        PASAPORTE: /^[A-Z0-9]{6,20}$/,
      }[document.getElementById("tipo_documento")?.value || "DNI"].test(
        dni.value.trim().toUpperCase(),
      )
    )
      error = [
        "Revisa el número según el tipo de documento seleccionado.",
        dni,
      ];
    else if (!correo.validity.valid || correo.value.trim() === "")
      error = ["Ingresa un correo electrónico válido.", correo];
    else if (
      [...clave.value].length < 8 ||
      !/[A-Za-z]/.test(clave.value) ||
      !/[0-9]/.test(clave.value) ||
      new TextEncoder().encode(clave.value).length > 72
    )
      error = [
        "Usa al menos 8 caracteres con letras y números y no superes 72 bytes.",
        clave,
      ];
    else if (clave.value !== confirmar.value)
      error = ["Las contraseñas no coinciden.", confirmar];
    else if (!terminos.checked)
      error = ["Debes aceptar los términos de uso.", terminos];

    if (error) {
      evento.preventDefault();
      mostrarError(error[0], error[1]);
    }
  });
});
