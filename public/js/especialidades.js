/** Búsqueda de especialidades sin recargar la página. */
"use strict";
document.addEventListener("DOMContentLoaded", () => {
  const campo = document.getElementById("buscar-especialidad");
  if (!campo) return;
  const tarjetas = [...document.querySelectorAll(".tarjeta-especialidad")];
  const resultado = document.getElementById("resultado-busqueda");
  const limpiar = document.getElementById("limpiar-busqueda");
  const vacio = document.getElementById("catalogo-vacio");
  const normalizar = (texto) =>
    texto
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  function filtrar() {
    const busqueda = normalizar(campo.value);
    let visibles = 0;
    tarjetas.forEach((tarjeta) => {
      const coincide = normalizar(
        tarjeta.querySelector("h2").textContent,
      ).includes(busqueda);
      tarjeta.hidden = !coincide;
      if (coincide) visibles++;
    });
    resultado.textContent = `${visibles} ${visibles === 1 ? "especialidad disponible" : "especialidades disponibles"}`;
    vacio.hidden = visibles !== 0;
    limpiar.hidden = !campo.value;
  }
  campo.addEventListener("input", filtrar);
  limpiar.addEventListener("click", () => {
    campo.value = "";
    filtrar();
    campo.focus();
  });
});
