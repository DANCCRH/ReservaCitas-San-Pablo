"use strict";
document.addEventListener("DOMContentLoaded", () => {
  const recuperados = document.getElementById("datos-recuperados");
  if (recuperados) {
    const f = JSON.parse(recuperados.textContent);
    const aviso = document.createElement("p");
    aviso.className = "alert alert-danger";
    aviso.role = "alert";
    aviso.tabIndex = -1;
    aviso.textContent = f.mensaje;
    document.querySelector("main")?.prepend(aviso);
    let intentos = 0;
    const restaurar = () => {
      for (const [nombre, valor] of Object.entries(f.datos)) {
        for (const campo of document.getElementsByName(nombre)) {
          if (
            campo.type === "password" ||
            campo.type === "hidden" ||
            campo.readOnly
          )
            continue;
          if (campo.type === "checkbox")
            campo.checked = valor === "on" || valor === campo.value;
          else if (
            campo.tagName !== "SELECT" ||
            [...campo.options].some((o) => o.value === valor)
          )
            campo.value = valor;
        }
      }
      if (++intentos < 6) setTimeout(restaurar, 400);
    };
    restaurar();
    aviso.focus();
  }
  document.querySelectorAll('input[type="tel"]').forEach((c) => {
    c.maxLength = 30;
    c.pattern = "\\+?[0-9 ()-]{7,30}";
    c.addEventListener("blur", () => {
      c.value = c.value.replace(/[\s()-]/g, "");
    });
  });
  document.querySelectorAll("table.tabla-gestion").forEach((t) => {
    const titulos = [...t.querySelectorAll("thead th")].map((c) =>
      c.textContent.trim(),
    );
    t.querySelectorAll("tbody tr").forEach((f) =>
      [...f.children].forEach(
        (c, i) => (c.dataset.etiqueta = titulos[i] || ""),
      ),
    );
  });
  const alerta = document.getElementById("aviso-sesion");
  let csrf = "";
  const comprobar = async () => {
    try {
      const r = await fetch("sesion.php", { cache: "no-store" });
      const d = await r.json();
      if (!r.ok || !d.activa) {
        location.href = "login.php?expirada=1";
        return;
      }
      csrf = d.csrf;
      if (alerta) alerta.hidden = d.segundos > 120;
    } catch {
      /* El siguiente ciclo vuelve a comprobar la conexión. */
    }
  };
  if (alerta) {
    setInterval(comprobar, 60000);
    document
      .getElementById("continuar-sesion")
      ?.addEventListener("click", async () => {
        await fetch("sesion.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({ csrf }),
        });
        await comprobar();
      });
  }
  const tipo = document.getElementById("tipo_documento");
  tipo?.addEventListener("change", () => {
    const c = document.getElementById("document");
    if (c) {
      c.value = "";
      c.inputMode = tipo.value === "DNI" ? "numeric" : "text";
    }
  });
});
