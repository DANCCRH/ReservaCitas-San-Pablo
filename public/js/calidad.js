/** Mejoras progresivas: contenido disponible también sin animación. */
"use strict";
document.addEventListener("DOMContentLoaded", () => {
  const reduced = matchMedia("(prefers-reduced-motion: reduce)");
  if (!reduced.matches && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        for (const e of entries)
          if (e.isIntersecting) {
            e.target.classList.add("revelar-visible");
            observer.unobserve(e.target);
          }
      },
      { threshold: 0.08 },
    );
    document.querySelectorAll("[data-revelar]").forEach((el) => {
      el.classList.add("revelar-activo");
      observer.observe(el);
    });
    reduced.addEventListener("change", () => {
      if (reduced.matches)
        document
          .querySelectorAll(".revelar-activo")
          .forEach((el) => el.classList.add("revelar-visible"));
    });
  }
  const top =
    document.querySelector(".volver-arriba") ||
    document.createElement("button");
  top.type = "button";
  top.className = "volver-arriba";
  top.setAttribute("aria-label", "Volver al inicio de la página");
  top.innerHTML = '<i class="fas fa-arrow-up" aria-hidden="true"></i>';
  top.hidden = true;
  top.addEventListener("click", () =>
    window.scrollTo({
      top: 0,
      behavior: reduced.matches ? "instant" : "smooth",
    }),
  );
  document.body.append(top);
  window.addEventListener(
    "scroll",
    () => {
      top.hidden = scrollY < 650;
      top.classList.toggle("visible", !top.hidden);
    },
    { passive: true },
  );
  document.querySelectorAll("[data-galeria-filtro]").forEach((button) =>
    button.addEventListener("click", () => {
      const category = button.dataset.galeriaFiltro;
      document
        .querySelectorAll("[data-galeria-filtro]")
        .forEach((b) => b.setAttribute("aria-pressed", String(b === button)));
      let count = 0;
      document.querySelectorAll("[data-categoria]").forEach((item) => {
        item.hidden =
          category !== "todas" && item.dataset.categoria !== category;
        if (!item.hidden) count++;
      });
      const status = document.getElementById("galeria-conteo");
      if (status) status.textContent = `${count} fotografías`;
    }),
  );
});
