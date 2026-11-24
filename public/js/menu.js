/** Menú lateral móvil: foco contenido, Escape y regreso al botón de apertura. */
"use strict";
document.addEventListener("DOMContentLoaded", () => {
  const panel = document.querySelector(".menu-panel"),
    toggle = document.querySelector(".menu-toggle"),
    backdrop = document.querySelector(".menu-fondo"),
    close = document.querySelector(".menu-cerrar");
  if (!panel || !toggle || !backdrop || !close) return;
  const mobile = matchMedia("(max-width: 1099px)");
  let previous = null;
  function menu(open, restore = true) {
    if (open && !mobile.matches) return;
    if (open) previous = document.activeElement;
    document.body.classList.toggle("nav-abierto", open);
    toggle.setAttribute("aria-expanded", String(open));
    backdrop.hidden = !open;
    if (open) {
      panel.setAttribute("role", "dialog");
      panel.setAttribute("aria-modal", "true");
      panel.setAttribute("aria-label", "Navegación de ReservaCitas");
      close.focus({ preventScroll: true });
    } else {
      panel.removeAttribute("role");
      panel.removeAttribute("aria-modal");
      panel.removeAttribute("aria-label");
      if (restore) previous?.focus({ preventScroll: true });
    }
  }
  function account(open) {
    const item = panel.querySelector(".cuenta-menu");
    item?.classList.toggle("cuenta-abierta", open);
    item
      ?.querySelector("[data-cuenta-toggle]")
      ?.setAttribute("aria-expanded", String(open));
  }
  toggle.addEventListener("click", () => menu(true));
  close.addEventListener("click", () => menu(false));
  backdrop.addEventListener("click", () => menu(false));
  panel
    .querySelector("[data-cuenta-toggle]")
    ?.addEventListener("click", (e) =>
      account(e.currentTarget.getAttribute("aria-expanded") !== "true"),
    );
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".cuenta-menu")) account(false);
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const opened = panel.querySelector(".cuenta-abierta");
      if (opened) {
        account(false);
        opened.querySelector("button")?.focus();
      } else if (document.body.classList.contains("nav-abierto")) menu(false);
    }
    if (e.key !== "Tab" || !document.body.classList.contains("nav-abierto"))
      return;
    const focusable = [
      ...panel.querySelectorAll("a[href],button:not([disabled])"),
    ].filter((el) => el.getClientRects().length);
    const first = focusable[0],
      last = focusable.at(-1);
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });
  panel.addEventListener("click", (e) => {
    if (e.target.closest("a")) menu(false, false);
  });
  mobile.addEventListener("change", () => {
    menu(false, false);
    account(false);
  });
  window.addEventListener("pageshow", (e) => {
    if (e.persisted) location.reload();
  });
});
