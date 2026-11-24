"use strict";

// El select conserva el ID enviado por el formulario; el texto sólo sirve para encontrarlo.
function activarBuscador(select, numero) {
  const local = select.hasAttribute("data-buscador-local");
  const multiple = select.multiple;
  const etiquetas = Array.from(select.labels || []);
  const nombre = select.dataset.etiqueta || select.getAttribute("aria-label") ||
    etiquetas[0]?.firstChild?.textContent.trim() || "Buscar";
  const vacio = !multiple ? Array.from(select.options).find(o => !o.value || o.value === "0") : null;
  const predeterminado = vacio?.value || "";
  const placeholder = select.dataset.placeholder || vacio?.text || "Escribe para buscar";
  const requerido = select.required;
  const base = "opciones-buscador-" + numero;
  const padre = select.dataset.especialidad && document.getElementById(select.dataset.especialidad);
  const control = document.createElement("span");
  control.className = "buscador-control";
  const entrada = document.createElement("input");
  entrada.id = base + "-campo";
  entrada.type = "text";
  entrada.autocomplete = "off";
  entrada.spellcheck = false;
  entrada.placeholder = placeholder;
  entrada.maxLength = 100;
  entrada.setAttribute("role", "combobox");
  entrada.setAttribute("aria-label", nombre);
  entrada.setAttribute("aria-controls", base);
  entrada.setAttribute("aria-expanded", "false");
  entrada.setAttribute("aria-autocomplete", "list");
  const caja = document.createElement("span");
  caja.className = "buscador-entrada";
  const limpiar = document.createElement("button");
  limpiar.type = "button";
  limpiar.className = "buscador-limpiar";
  limpiar.setAttribute("aria-label", "Limpiar " + nombre);
  limpiar.textContent = "×";
  const opciones = document.createElement("span");
  opciones.id = base;
  opciones.className = "buscador-lista";
  opciones.setAttribute("role", "listbox");
  opciones.setAttribute("aria-label", nombre);
  opciones.hidden = true;
  const estado = document.createElement("span");
  estado.className = "buscador-lector";
  estado.setAttribute("role", "status");
  const elegidos = document.createElement("span");
  elegidos.className = "buscador-elegidos";
  caja.append(entrada, limpiar);
  control.append(caja, opciones, estado);
  if (multiple) control.append(elegidos);
  select.after(control);
  select.hidden = true;
  select.required = false;
  etiquetas.forEach(label => { label.htmlFor = entrada.id; });

  let consulta = "";
  let pagina = 0;
  let mas = false;
  let peticion;
  let temporizador;
  let candidatos = [];
  let indice = -1;
  let editando = false;
  const elegidas = () => Array.from(select.selectedOptions).filter(o => o.value && o.value !== "0");
  const avisar = () => select.dispatchEvent(new Event("change", { bubbles: true }));

  function sincronizar() {
    const seleccionadas = elegidas();
    entrada.disabled = select.disabled;
    limpiar.disabled = select.disabled;
    if (!editando) entrada.value = multiple ? "" : seleccionadas[0]?.text || "";
    limpiar.hidden = !entrada.value || select.disabled;
    entrada.required = requerido && seleccionadas.length === 0;
    entrada.setCustomValidity(editando && entrada.value.trim() ? "Selecciona una coincidencia de la lista." : "");
    if (multiple) {
      elegidos.replaceChildren();
      for (const o of seleccionadas) {
        const quitar = document.createElement("button");
        quitar.type = "button";
        quitar.className = "buscador-elegido";
        quitar.textContent = o.text + " ×";
        quitar.setAttribute("aria-label", "Quitar " + o.text);
        quitar.disabled = select.disabled;
        quitar.addEventListener("click", () => {
          if (local) o.selected = false; else o.remove();
          avisar();
          entrada.focus();
        });
        elegidos.append(quitar);
      }
    }
    if (select.disabled) cerrar();
  }
  function cancelar() {
    clearTimeout(temporizador);
    peticion?.abort();
    peticion = null;
  }
  function cerrar() {
    cancelar();
    opciones.hidden = true;
    entrada.setAttribute("aria-expanded", "false");
    entrada.removeAttribute("aria-activedescendant");
    indice = -1;
  }
  function mensaje(texto) {
    const nota = document.createElement("span");
    nota.className = "buscador-nota";
    nota.textContent = texto;
    opciones.replaceChildren(nota);
    estado.textContent = texto;
  }
  function marcar(i) {
    const antes = opciones.querySelector('[aria-selected="true"]');
    if (antes) antes.setAttribute("aria-selected", "false");
    indice = i;
    const actual = opciones.children[i];
    if (!actual) return;
    actual.setAttribute("aria-selected", "true");
    entrada.setAttribute("aria-activedescendant", actual.id);
    if (actual.offsetTop < opciones.scrollTop) opciones.scrollTop = actual.offsetTop;
    if (actual.offsetTop + actual.offsetHeight > opciones.scrollTop + opciones.clientHeight) {
      opciones.scrollTop = actual.offsetTop + actual.offsetHeight - opciones.clientHeight;
    }
  }
  function elegir(fila) {
    const id = String(fila.id);
    let opcion = Array.from(select.options).find(o => o.value === id);
    if (!opcion) {
      opcion = new Option(fila.nombre + (fila.detalle ? " · " + fila.detalle : ""), id);
      opcion.dataset.nombre = fila.nombre;
      opcion.dataset.detalle = fila.detalle || "";
      select.add(opcion);
    }
    if (!multiple && !local) {
      for (const o of Array.from(select.options)) if (o !== opcion && o !== vacio) o.remove();
    }
    opcion.selected = true;
    editando = false;
    cerrar();
    avisar();
    entrada.focus();
    cerrar();
  }
  function agregar(filas) {
    // En selección múltiple se puede seguir avanzando aunque ya se hayan elegido los primeros resultados.
    const existentes = new Set(multiple ? elegidas().map(o => o.value) : []);
    for (const fila of filas) {
      if (existentes.has(String(fila.id))) continue;
      const item = document.createElement("span");
      item.className = "buscador-opcion";
      item.id = base + "-" + candidatos.length;
      item.setAttribute("role", "option");
      item.setAttribute("aria-selected", "false");
      item.textContent = fila.nombre;
      if (fila.detalle) {
        const detalle = document.createElement("small");
        detalle.textContent = fila.detalle;
        item.append(detalle);
      }
      // Mantiene el foco en el combobox al seleccionar con el ratón.
      item.addEventListener("mousedown", e => e.preventDefault());
      item.addEventListener("click", () => elegir(fila));
      opciones.append(item);
      candidatos.push(fila);
    }
  }
  const normalizar = t => t.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  async function cargar() {
    if (opciones.hidden || peticion) return;
    const solicitud = new AbortController();
    peticion = solicitud;
    const siguiente = pagina + 1;
    try {
      let filas;
      if (local) {
        const palabras = normalizar(consulta).split(/\s+/).filter(Boolean);
        const lista = Array.from(select.options).filter(o => o !== vacio && !o.disabled && palabras.every(p => normalizar(o.text).includes(p)));
        filas = lista.slice(pagina * 30, siguiente * 30).map(o => ({ id:o.value, nombre:o.text }));
        mas = lista.length > siguiente * 30;
      } else {
        const params = new URLSearchParams({ tipo:select.dataset.buscador, q:consulta, pagina:siguiente,
          alcance:select.dataset.alcance || "", usuario:select.dataset.titular || "0" });
        if (padre?.value) params.set("especialidad", padre.value);
        const r = await fetch("buscar_opciones.php?" + params, {signal:solicitud.signal, cache:"no-store"});
        const datos = await r.json();
        if (solicitud.signal.aborted) return;
        if (!r.ok) throw new Error(datos.error || "No se pudo buscar.");
        filas = datos.resultados;
        mas = datos.mas;
      }
      if (!pagina) opciones.replaceChildren();
      pagina = siguiente;
      agregar(filas);
      if (!candidatos.length && !mas) mensaje("Sin coincidencias");
      else estado.textContent = candidatos.length + " resultados disponibles.";
    } catch (error) {
      if (solicitud.signal.aborted) return;
      mas = false;
      if (!pagina) mensaje("No se pudo cargar la lista. Vuelve a escribir para reintentar.");
      else estado.textContent = "No se pudo cargar la página siguiente.";
    } finally {
      if (peticion === solicitud) {
        peticion = null;
        if (mas && !opciones.hidden && opciones.scrollHeight <= opciones.clientHeight) cargar();
      }
    }
  }
  function buscar(demora = 0) {
    cancelar();
    opciones.hidden = false;
    entrada.setAttribute("aria-expanded", "true");
    entrada.removeAttribute("aria-activedescendant");
    consulta = editando ? entrada.value.trim() : "";
    pagina = 0;
    indice = -1;
    mas = false;
    candidatos = [];
    mensaje("Buscando…");
    temporizador = setTimeout(cargar, demora);
  }
  function borrar() {
    if (!multiple && select.value !== predeterminado) {
      select.value = predeterminado;
      avisar();
    }
  }
  entrada.addEventListener("focus", () => {
    if (!editando && entrada.value) entrada.select();
    buscar();
  });
  entrada.addEventListener("click", () => { if (opciones.hidden) buscar(); });
  entrada.addEventListener("input", () => {
    editando = true;
    borrar();
    sincronizar();
    buscar(180);
  });
  limpiar.addEventListener("click", () => {
    editando = false;
    entrada.value = "";
    borrar();
    sincronizar();
    entrada.focus();
    buscar();
  });
  entrada.addEventListener("keydown", e => {
    if (e.key === "Escape") { cerrar(); return; }
    if (e.key === "Tab") { cerrar(); return; }
    if (e.key === "Enter" && !opciones.hidden) {
      e.preventDefault();
      const item = candidatos[indice] || (editando && candidatos.length === 1 && !mas ? candidatos[0] : null);
      if (item) elegir(item);
      return;
    }
    if (!["ArrowDown", "ArrowUp"].includes(e.key)) return;
    e.preventDefault();
    if (opciones.hidden) { buscar(); return; }
    if (!candidatos.length) return;
    marcar(Math.min(candidatos.length - 1, Math.max(0, indice + (e.key === "ArrowDown" ? 1 : -1))));
    if (mas && indice === candidatos.length - 1) cargar();
  });
  opciones.addEventListener("scroll", () => {
    if (mas && opciones.scrollTop + opciones.clientHeight >= opciones.scrollHeight - 28) cargar();
  });
  control.addEventListener("focusout", e => {
    if (!control.contains(e.relatedTarget)) cerrar();
  });
  select.addEventListener("change", sincronizar);
  select.form?.addEventListener("reset", () => setTimeout(() => { editando = false; cerrar(); sincronizar(); }));
  if (padre) padre.addEventListener("change", () => {
    editando = false;
    entrada.value = "";
    cerrar();
    borrar();
    sincronizar();
  });
  new MutationObserver(sincronizar).observe(select, {childList:true, attributes:true, attributeFilter:["disabled"]});
  sincronizar();
}
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("select[data-buscador], select[data-buscador-local]").forEach(activarBuscador);
});
