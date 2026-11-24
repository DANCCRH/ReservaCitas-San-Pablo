/** Animaciones de entrada, sombra de la cabecera y retorno al inicio. */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const cabecera = document.querySelector("header");
  const reducirMovimiento =
    typeof window.matchMedia === "function" &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Animación de entrada de tarjetas y secciones.
  const bloques = document.querySelectorAll(
    ".tarjeta-especialidad, .tarjeta-mv, .tarjeta-datos, .tarjeta-contacto, .paso-item, .bloque-formulario, .solicitud-tarjeta",
  );
  if ("IntersectionObserver" in window && !reducirMovimiento) {
    document.documentElement.classList.add("js-activo");
    const observador = new IntersectionObserver(
      (entradas) =>
        entradas.forEach((entrada) => {
          if (entrada.isIntersecting) {
            entrada.target.classList.add("visible");
            observador.unobserve(entrada.target);
          }
        }),
      { threshold: 0.15 },
    );
    bloques.forEach((el) => {
      el.classList.add("revelar");
      observador.observe(el);
    });
  }

  const alDesplazar = () =>
    cabecera?.classList.toggle("con-sombra", scrollY > 120);
  addEventListener("scroll", alDesplazar, { passive: true });
  alDesplazar();
});
