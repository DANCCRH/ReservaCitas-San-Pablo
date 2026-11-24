/** Validación del contacto, contador de caracteres y envío del formulario. */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const formulario = document.getElementById("form-contacto");
  if (!formulario) return;
  const mensaje = document.getElementById("mensaje");
  const contador = document.getElementById("contador-mensaje");

  contador.textContent = String(mensaje.value.length);
  formulario.addEventListener("input", (evento) => {
    if (evento.target.getAttribute("aria-invalid") === "true")
      marcar(evento.target, "");
  });

  mensaje.addEventListener("input", () => {
    contador.textContent = String(mensaje.value.length);
  });

  /** Pone o quita el texto de error de un campo. */
  function marcar(campo, texto) {
    document.getElementById(`error-${campo.id}`).textContent = texto;
    campo.setAttribute("aria-invalid", texto ? "true" : "false");
    const descripcion = new Set(
      (campo.getAttribute("aria-describedby") || "")
        .split(/\s+/)
        .filter(Boolean),
    );
    descripcion.add(`error-${campo.id}`);
    campo.setAttribute("aria-describedby", [...descripcion].join(" "));
  }

  formulario.addEventListener("submit", (evento) => {
    const nombre = document.getElementById("nombre");
    const correo = document.getElementById("correo");
    const asunto = document.getElementById("asunto");
    const reglas = [
      [
        nombre,
        nombre.value.trim().length >= 3,
        "Ingresa tu nombre (mínimo 3 caracteres).",
      ],
      [
        correo,
        correo.validity.valid && correo.value.trim() !== "",
        "Ingresa un correo electrónico válido.",
      ],
      [
        asunto,
        asunto.value.trim().length >= 3,
        "Ingresa un asunto (mínimo 3 caracteres).",
      ],
      [
        mensaje,
        mensaje.value.trim().length >= 10,
        "El mensaje debe tener al menos 10 caracteres.",
      ],
    ];
    let primerError = null;
    reglas.forEach(([campo, correcto, texto]) => {
      marcar(campo, correcto ? "" : texto);
      if (!correcto && !primerError) primerError = campo;
    });
    if (primerError) {
      evento.preventDefault();
      primerError.focus();
    }
  });
});
