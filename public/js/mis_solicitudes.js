/** Presentación de citas del paciente; los valores se insertan como texto. */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const citas = window.DATOS_CITAS || [];
  const contenedor = document.getElementById("lista-solicitudes");

  /** Crea un elemento con clase y texto opcionales. */
  function crear(etiqueta, clase, texto) {
    const el = document.createElement(etiqueta);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
  }

  /** Da formato dd/mm/aaaa a una fecha "aaaa-mm-dd". */
  function formatearFecha(iso) {
    const [a, m, d] = String(iso).split("-");
    return d && m && a ? `${d}/${m}/${a}` : "—";
  }

  function capitalizar(texto) {
    return texto.charAt(0).toUpperCase() + texto.slice(1);
  }

  /** Actualiza los contadores del panel lateral. */
  function actualizarEstadisticas() {
    const contar = (estado) => citas.filter((c) => c.estado === estado).length;
    document.getElementById("stat-total").textContent = citas.length;
    document.getElementById("stat-pendientes").textContent =
      contar("pendiente");
    document.getElementById("stat-atendidas").textContent = contar("atendido");
    document.getElementById("stat-canceladas").textContent =
      contar("cancelado");
  }

  /** Crea una columna "etiqueta / valor" de la tarjeta. */
  function columna(etiqueta, valor) {
    const col = crear("div", "dato-columna");
    col.append(
      crear("div", "dato-etiqueta", etiqueta),
      crear("div", "dato-valor", valor || "—"),
    );
    return col;
  }

  /** Pide al servidor cancelar la cita y vuelve a dibujar la lista. */
  async function cancelarCita(cita, boton) {
    const acepta = await window.confirmarAccion({
      titulo: "¿Cancelar tu cita?",
      mensaje:
        "Tu solicitud quedará cancelada. Podrás elegir otro horario cuando lo necesites.",
      confirmar: "Sí, cancelar cita",
    });
    if (!acepta) return;
    boton.disabled = true;
    try {
      const datos = new URLSearchParams({
        id: cita.id,
        csrf: window.TOKEN_CSRF,
      });
      const respuesta = await fetch("cancelar_cita.php", {
        method: "POST",
        body: datos,
      });
      const resultado = await respuesta.json();
      if (resultado.ok) {
        cita.estado = "cancelado";
        renderizar();
        window.notificar("La cita se canceló correctamente.");
      } else {
        window.notificar(
          resultado.error || "No se pudo cancelar la cita.",
          true,
        );
        boton.disabled = false;
      }
    } catch (error) {
      window.notificar("No se pudo conectar. Inténtalo de nuevo.", true);
      boton.disabled = false;
    }
  }

  /** Dibuja la lista de tarjetas. */
  function renderizar() {
    actualizarEstadisticas();
    contenedor.replaceChildren();

    if (citas.length === 0) {
      const vacia = crear("div", "vacia");
      vacia.append(
        crear("i", "fas fa-clipboard-list"),
        crear("p", "", "No tienes solicitudes de cita registradas aún."),
        crear("p", "", 'Pulsa "Solicitar nueva cita" para empezar.'),
      );
      contenedor.appendChild(vacia);
      return;
    }

    citas.forEach((cita, indice) => {
      const tarjeta = crear("div", `solicitud-tarjeta ${cita.estado}`);

      const cabecera = crear("div", "tarjeta-cabecera");
      cabecera.append(
        crear("span", "tarjeta-numero", `Solicitud #${cita.id}`),
        crear(
          "span",
          `etiqueta-estado estado-${cita.estado}`,
          capitalizar(cita.estado),
        ),
      );

      const tabla = crear("div", "tabla-datos");
      tabla.append(
        columna("Especialidad", cita.especialidad),
        columna("Fecha", formatearFecha(cita.fecha_cita)),
        columna("Hora", String(cita.hora_cita).slice(0, 5)),
        columna("Paciente", cita.nombres),
        columna("Teléfono", cita.telefono),
      );

      const registro = crear("div", "fila-registro");
      registro.append(
        crear("div", "registro-etiqueta", "Registrado"),
        crear(
          "div",
          "registro-valor",
          formatearFecha(String(cita.fecha_solicitud).split(" ")[0]),
        ),
      );

      tarjeta.append(cabecera, tabla, registro);

      if (cita.estado === "pendiente") {
        const grupo = crear("div", "grupo-botones");
        const boton = crear(
          "button",
          "boton-estado btn-cancelado",
          "Cancelar cita",
        );
        boton.type = "button";
        boton.addEventListener("click", () => cancelarCita(cita, boton));
        grupo.appendChild(boton);
        tarjeta.appendChild(grupo);
      }
      contenedor.appendChild(tarjeta);
    });
  }

  renderizar();
});
