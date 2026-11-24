/** Validación de la reserva, consulta de horarios y revisión de la solicitud. */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const formulario = document.getElementById("formulario-cita");
  if (!formulario) return;
  const comentario = document.getElementById("comentario");
  const contador = document.getElementById("contador");
  const modal = document.getElementById("modal-resumen");
  const contenidoResumen = document.getElementById("contenido-resumen");
  const fechaCita = document.getElementById("fecha_cita");

  const confirmar = document.getElementById("cerrar-modal");
  const dialogo = modal.querySelector(".modal-contenido");
  let focoAnterior = null;
  let envioConfirmado = false;
  let enviando = false;
  dialogo.setAttribute("role", "dialog");
  dialogo.setAttribute("aria-modal", "true");
  const titulo = modal.querySelector(".modal-titulo");
  titulo.id = titulo.id || "titulo-resumen";
  titulo.tabIndex = -1;
  dialogo.setAttribute("aria-labelledby", titulo.id);
  modal.setAttribute("aria-hidden", "true");

  // El calendario usa la misma zona horaria que PHP y MySQL.
  const hoy = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Lima",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());
  fechaCita.min = hoy;
  const horaCita = document.getElementById("hora_cita");
  const estadoHorarios = document.getElementById("estado-horarios");
  const revisar = document.getElementById("btn-revisar");
  const guardar = document.getElementById("btn-guardar");
  const estadoCuenta = document.getElementById("estado-cuenta");
  const campos = document.getElementById("campos-cita");
  let cuentaLista = false;
  let horariosListos = false;
  let consultaHorarios = 0;
  function habilitarHorarios(disponibles) {
    horariosListos = disponibles;
    horaCita.disabled = !disponibles;
    revisar.disabled = !disponibles;
    guardar.disabled = !disponibles;
  }

  function reiniciarHorarios() {
    habilitarHorarios(false);
    horaCita.replaceChildren(new Option("Selecciona una hora", ""));
    fechaCita.setCustomValidity("");
    fechaCita.removeAttribute("aria-invalid");
    estadoHorarios.className = "ayuda-horarios";
    estadoHorarios.replaceChildren();
  }

  function mostrarCitaDelDia(cita) {
    const fecha = fechaCita.value.split("-").reverse().join("/");
    const mensaje = `Ya hay una cita de ${cita.especialidad} el ${fecha} a las ${cita.hora} para este paciente. Elige otra fecha u otra especialidad.`;
    fechaCita.setCustomValidity(mensaje);
    fechaCita.setAttribute("aria-invalid", "true");
    estadoHorarios.className = "alert alert-warning";
    estadoHorarios.textContent = mensaje;
    const plantilla = document.getElementById("enlaces-cita-del-dia");
    const acciones = plantilla.content.cloneNode(true);
    if (!cita.reprogramable) acciones.querySelector("[data-reprogramar]")?.remove();
    for (const enlace of acciones.querySelectorAll("a")) {
      const destino = new URL(enlace.getAttribute("href"), document.baseURI);
      destino.searchParams.set(enlace.dataset.parametro, cita.id);
      enlace.href = destino.href;
    }
    estadoHorarios.append(acciones);
  }

  const semilla = window.RESERVA_INICIAL;
  const medico = document.getElementById("medico_id");
  const especialidad = document.getElementById("especialidad");
  if (semilla) {
    fechaCita.value = semilla.fecha_cita;
    comentario.value = semilla.comentario || "";
    contador.textContent = `${comentario.value.length}/250`;
  }
  fechaCita.max = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Lima",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date(Date.now() + Number(fechaCita.dataset.diasMaximos || 180) * 86400000));
  const seleccionAgenda = new URLSearchParams(
    window.RESERVA_CONTEXTO || location.search,
  );
  if (!semilla) {
    const fechaElegida = seleccionAgenda.get("fecha") || "";
    if (
      /^\d{4}-\d{2}-\d{2}$/.test(fechaElegida) &&
      fechaElegida >= hoy &&
      fechaElegida <= fechaCita.max
    )
      fechaCita.value = fechaElegida;
  }
  especialidad.addEventListener("change", () => {
    medico.disabled = !especialidad.value;
    actualizarHorarios();
  });
  medico.addEventListener("change", actualizarHorarios);
  /** Comprueba el día de la especialidad incluso antes de elegir al profesional. */
  async function actualizarHorarios() {
    const consulta = ++consultaHorarios;
    const anterior =
      horaCita.value ||
      (semilla
        ? String(semilla.hora_cita).slice(0, 5)
        : seleccionAgenda.get("hora") || "");
    reiniciarHorarios();
    if (!cuentaLista || !fechaCita.value || !especialidad.value) {
      estadoHorarios.textContent =
        "Selecciona una especialidad y una fecha para consultar la disponibilidad.";
      return;
    }
    const parametros = new URLSearchParams({
      fecha: fechaCita.value,
      especialidad: especialidad.value,
      paciente: window.RESERVA_PACIENTE?.id || 0,
      excluir: semilla?.id || 0,
    });
    if (medico.value) parametros.set("medico", medico.value);
    estadoHorarios.textContent = "Consultando horarios…";
    try {
      const respuesta = await fetch(
        `horarios.php?${parametros}`,
        { credentials: "same-origin", cache: "no-store" },
      );
      const datos = await respuesta.json();
      if (consulta !== consultaHorarios) return;
      if (!respuesta.ok)
        throw new Error(
          datos.error || "No se pudieron consultar los horarios.",
        );
      if (datos.cita_del_dia) {
        mostrarCitaDelDia(datos.cita_del_dia);
        return;
      }
      if (!medico.value) {
        estadoHorarios.textContent = "Busca un médico de la especialidad para ver sus horarios.";
        return;
      }
      datos.horas.forEach(({ hora, disponible }) => {
        const opcion = new Option(
          disponible ? hora : `${hora} · no disponible`,
          hora,
        );
        opcion.disabled = !disponible;
        horaCita.add(opcion);
        if (disponible && hora === anterior) opcion.selected = true;
      });
      const disponibles = datos.horas.filter((h) => h.disponible).length;
      habilitarHorarios(disponibles > 0);
      estadoHorarios.textContent = disponibles
        ? `${disponibles} horarios disponibles. Se comprueban médico, consultorio y tus otras citas.`
        : "No quedan horarios disponibles con este médico en esta fecha. Elige otro día o profesional.";
    } catch (error) {
      if (consulta !== consultaHorarios) return;
      estadoHorarios.textContent = `${error.message} Cambia o vuelve a seleccionar la fecha para reintentar.`;
    }
  }
  fechaCita.addEventListener("change", actualizarHorarios);

  /** La identidad de la solicitud pertenece siempre a la cuenta iniciada. */
  async function precargarPerfil() {
    campos.disabled = true;
    cuentaLista = false;
    try {
      const respuesta = await fetch("perfil.php", {
        credentials: "same-origin",
        cache: "no-store",
      });
      if (!respuesta.ok) throw new Error("No se pudo comprobar tu cuenta.");
      const sesion = await respuesta.json();
      const perfil =
        window.RESERVA_PACIENTE &&
        sesion.logueado &&
        ["admin", "recepcion", "paciente"].includes(sesion.rol)
          ? { ...window.RESERVA_PACIENTE, logueado: true }
          : sesion;
      if (!perfil.logueado) {
        estadoCuenta.replaceChildren();
        const texto = document.createElement("p");
        texto.textContent =
          "Inicia sesión para solicitar una cita y consultar su estado.";
        const acceso = document.createElement("a");
        acceso.href = "login.php";
        acceso.textContent = "Ingresar";
        const registro = document.createElement("a");
        registro.href = "registro.php";
        registro.textContent = "Crear cuenta";
        estadoCuenta.append(texto, acceso, registro);
        return;
      }
      ["nombres", "dni", "correo"].forEach(
        (id) => (document.getElementById(id).value = perfil[id] || ""),
      );
      if (!document.getElementById("telefono").value)
        document.getElementById("telefono").value = perfil.telefono || "";
      estadoCuenta.textContent = window.RESERVA_PACIENTE
        ? `Reserva a nombre de ${perfil.nombres}. Tu intervención quedará registrada en el historial.`
        : "Tu solicitud se guardará en esta cuenta. Puedes actualizar tu teléfono en este formulario.";
      cuentaLista = true;
      campos.disabled = false;
      await actualizarHorarios();
    } catch {
      estadoCuenta.replaceChildren(
        document.createTextNode("No se pudo comprobar tu cuenta. "),
      );
      const reintentar = document.createElement("button");
      reintentar.type = "button";
      reintentar.textContent = "Reintentar";
      reintentar.addEventListener("click", precargarPerfil);
      estadoCuenta.append(reintentar);
    }
  }
  precargarPerfil();
  window.addEventListener("pageshow", (evento) => {
    if (evento.persisted) precargarPerfil();
  });

  comentario.addEventListener("input", () => {
    contador.textContent = `${comentario.value.length}/250`;
  });

  /** Muestra (o quita, con texto vacío) el mensaje de error bajo un campo. */
  function mostrarError(campo, texto) {
    const idError = `error-${campo.id}`;
    let aviso = document.getElementById(idError);
    if (!aviso) {
      aviso = document.createElement("small");
      aviso.id = idError;
      aviso.className = "error-campo";
      aviso.setAttribute("role", "alert");
      campo.closest(".form-grupo, .checkbox-grupo").appendChild(aviso);
    }
    aviso.textContent = texto;
    campo.style.borderColor = texto ? "#dc3545" : "";
    campo.setAttribute("aria-invalid", texto ? "true" : "false");
    const descripcion = new Set(
      (campo.getAttribute("aria-describedby") || "")
        .split(/\s+/)
        .filter(Boolean),
    );
    descripcion.add(`error-${campo.id}`);
    campo.setAttribute("aria-describedby", [...descripcion].join(" "));
  }

  /** Revisa todos los campos. Devuelve true si el formulario es válido. */
  function validar() {
    const valor = (id) => document.getElementById(id).value.trim();
    if (!cuentaLista) return false;
    if (fechaCita.validity.customError) {
      fechaCita.focus();
      return false;
    }
    const reglas = [
      [
        "nombres",
        valor("nombres").length >= 3,
        "Ingresa tus nombres y apellidos (mínimo 3 caracteres).",
      ],
      [
        "dni",
        /^[A-Z0-9]{6,20}$/.test(valor("dni")),
        "Revisa el documento de identidad registrado.",
      ],
      [
        "correo",
        document.getElementById("correo").validity.valid,
        "Ingresa un correo electrónico válido.",
      ],
      [
        "telefono",
        /^\+?[0-9]{7,15}$/.test(valor("telefono").replace(/[\s()-]/g, "")) &&
          valor("telefono").replace(/[\s()-]/g, "").length <= 16,
        "Ingresa un teléfono válido (7 a 15 dígitos).",
      ],
      [
        "especialidad",
        valor("especialidad") !== "",
        "Selecciona una especialidad.",
      ],
      [
        "medico_id",
        medico.value !== "" && !medico.disabled,
        "Selecciona un médico.",
      ],
      [
        "fecha_cita",
        valor("fecha_cita") !== "" &&
          valor("fecha_cita") >= hoy &&
          valor("fecha_cita") <= fechaCita.max,
        "Selecciona una fecha desde hoy en adelante.",
      ],
      [
        "hora_cita",
        horariosListos &&
          valor("hora_cita") !== "" &&
          !horaCita.selectedOptions[0]?.disabled,
        "Selecciona una hora disponible.",
      ],
      [
        "confirmacion",
        document.getElementById("confirmacion").checked,
        "Confirma las condiciones de tu solicitud.",
      ],
    ];
    if (semilla)
      reglas.push([
        "motivo",
        valor("motivo").length >= 5,
        "Escribe el motivo de reprogramación (mínimo 5 caracteres).",
      ]);
    let valido = true;
    let primerError = null;
    reglas.forEach(([id, correcto, texto]) => {
      const campo = document.getElementById(id);
      mostrarError(campo, correcto ? "" : texto);
      if (!correcto) {
        valido = false;
        primerError = primerError || campo;
      }
    });
    if (primerError) primerError.focus();
    return valido;
  }

  /** Agrega una fila "Etiqueta: valor" al resumen usando textContent (evita XSS). */
  function agregarFila(etiqueta, valor) {
    const fila = document.createElement("div");
    fila.className = "modal-fila";
    const nombre = document.createElement("span");
    nombre.textContent = `${etiqueta}:`;
    const dato = document.createElement("strong");
    dato.textContent = valor;
    fila.append(nombre, dato);
    contenidoResumen.appendChild(fila);
  }

  /** Abre la ventana flotante con los datos escritos. */
  function abrirResumen() {
    contenidoResumen.replaceChildren();
    const valor = (id) => document.getElementById(id).value.trim();
    agregarFila("Nombres", valor("nombres"));
    agregarFila("DNI", valor("dni"));
    agregarFila("Correo", valor("correo"));
    agregarFila("Teléfono", valor("telefono"));
    agregarFila("Especialidad", especialidad.selectedOptions[0].textContent);
    agregarFila("Médico", medico.selectedOptions[0].textContent);
    agregarFila("Fecha", valor("fecha_cita").split("-").reverse().join("/"));
    agregarFila("Hora", valor("hora_cita"));
    if (semilla) agregarFila("Motivo de reprogramación", valor("motivo"));
    if (valor("comentario")) agregarFila("Comentario", valor("comentario"));
    focoAnterior = document.activeElement;
    document.body.style.overflow = "hidden";
    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("modal-visible");
    // En pantallas pequeñas se empieza por el título, sin saltar al botón del final.
    titulo.focus({ preventScroll: true });
    dialogo.scrollTop = 0;
  }

  function cerrarResumen() {
    if (enviando) return;
    document.body.style.overflow = "";
    modal.classList.remove("modal-visible");
    modal.setAttribute("aria-hidden", "true");
    focoAnterior?.focus();
  }

  formulario.addEventListener("input", (evento) => {
    const campo = evento.target;
    if (campo.getAttribute("aria-invalid") === "true") mostrarError(campo, "");
  });

  // "Revisar datos" valida y muestra el resumen.
  revisar.addEventListener("click", () => {
    if (validar()) abrirResumen();
  });

  // requestSubmit conserva la validación y los listeners de protección del formulario.
  formulario.addEventListener("submit", (evento) => {
    if (enviando) {
      evento.preventDefault();
      return;
    }
    if (!envioConfirmado) {
      evento.preventDefault();
      if (validar()) abrirResumen();
      return;
    }
    if (!validar()) {
      evento.preventDefault();
      cerrarResumen();
      return;
    }
    // csrf.js administra el token; aquí solo comprobamos que ya esté disponible.
    if (!formulario.querySelector('input[name="csrf"]')?.value) {
      evento.preventDefault();
      confirmar.textContent =
        "Espera la protección del formulario y vuelve a intentar";
      return;
    }
    queueMicrotask(() => {
      if (evento.defaultPrevented) return;
      enviando = true;
      confirmar.disabled = true;
      confirmar.textContent = "Guardando…";
      formulario.setAttribute("aria-busy", "true");
    });
  });
  confirmar.addEventListener("click", () => {
    if (enviando) return;
    envioConfirmado = true;
    try {
      formulario.requestSubmit();
    } finally {
      envioConfirmado = false;
    }
  });
  // Al regresar con el navegador se recuperan los controles de envío.
  window.addEventListener("pageshow", () => {
    enviando = false;
    document.body.style.overflow = "";
    confirmar.disabled = false;
    confirmar.textContent = "Confirmar y guardar";
    formulario.removeAttribute("aria-busy");
  });
  document
    .getElementById("editar-datos")
    .addEventListener("click", cerrarResumen);
  modal.addEventListener("click", (e) => {
    if (e.target === modal) cerrarResumen();
  });
  document.addEventListener("keydown", (e) => {
    if (!modal.classList.contains("modal-visible")) return;
    if (e.key === "Escape") cerrarResumen();
    if (e.key === "Tab") {
      const botones = [
        ...dialogo.querySelectorAll(
          'button:not([disabled]), a[href], input:not([disabled]), [tabindex="0"]',
        ),
      ];
      const primero = botones[0];
      const ultimo = botones[botones.length - 1];
      if (
        e.shiftKey &&
        (document.activeElement === primero ||
          document.activeElement === titulo)
      ) {
        e.preventDefault();
        ultimo.focus();
      } else if (!e.shiftKey && document.activeElement === ultimo) {
        e.preventDefault();
        primero.focus();
      }
    }
  });
});
