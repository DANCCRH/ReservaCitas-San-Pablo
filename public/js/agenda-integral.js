"use strict";
// El navegador prepara el cambio; disponibilidad, rol y conflictos se revisan en PHP.
(() => {
  const form = document.querySelector('[data-mover-form]');
  if (form) {
    const cita = form.querySelector('[data-mover-id]');
    const destino = form.querySelector('[data-mover-destino]');
    const estado = document.querySelector('[data-mover-estado]');
    const dialog = document.querySelector('[data-mover-dialog]');
    let origen = null;
    const elegir = (id) => {
      cita.value = id;
      cita.dispatchEvent(new Event('change', {bubbles: true}));
      estado.textContent = 'Cita seleccionada. Elige el nuevo horario libre y escribe el motivo.';
    };
    document.querySelectorAll('[data-elegir-cita]').forEach(b => b.addEventListener('click', () => elegir(b.dataset.elegirCita)));
    document.querySelectorAll('[data-mover-cita]').forEach(card => {
      card.addEventListener('dragstart', ev => { origen = card.dataset.moverCita; ev.dataTransfer.setData('text/plain', origen); ev.dataTransfer.effectAllowed = 'move'; });
      card.addEventListener('dragend', () => { origen = null; document.querySelectorAll('.destino-activo').forEach(x => x.classList.remove('destino-activo')); });
    });
    const colocar = b => {
      if (!cita.value) { estado.textContent = 'Primero selecciona la cita que deseas mover.'; (cita.nextElementSibling?.querySelector('[role="combobox"]') || cita).focus(); return; }
      destino.value = `${b.dataset.destinoFecha}|${b.dataset.destinoHora}`;
      estado.textContent = `Nuevo horario: ${b.dataset.destinoFecha}, ${b.dataset.destinoHora}. Escribe el motivo para revisar el cambio.`;
      form.querySelector('[name="motivo"]').focus();
    };
    document.querySelectorAll('[data-destino-fecha]').forEach(b => {
      b.addEventListener('dragover', ev => { if (origen) { ev.preventDefault(); b.classList.add('destino-activo'); } });
      b.addEventListener('dragleave', () => b.classList.remove('destino-activo'));
      b.addEventListener('drop', ev => { ev.preventDefault(); b.classList.remove('destino-activo'); if (origen) { elegir(origen); colocar(b); } });
      b.addEventListener('click', () => colocar(b));
    });
    form.addEventListener('submit', ev => {
      ev.preventDefault();
      if (!form.reportValidity()) return;
      const [fecha, hora] = destino.value.split('|');
      form.querySelector('[data-mover-fecha]').value = fecha;
      form.querySelector('[data-mover-hora]').value = hora;
      document.querySelector('[data-mover-resumen]').textContent = `${cita.selectedOptions[0].textContent}. Nuevo horario: ${fecha}, ${hora}. Motivo: ${form.querySelector('[name="motivo"]').value}`;
      dialog.showModal();
    });
    document.querySelector('[data-mover-cancelar]').addEventListener('click', () => dialog.close());
    document.querySelector('[data-mover-confirmar]').addEventListener('click', () => { dialog.close(); HTMLFormElement.prototype.submit.call(form); });
  }
  const turnero = document.querySelector('[data-turnero]');
  if (turnero) {
    document.querySelector('[data-pantalla]').addEventListener('click', async () => {
      try { if (document.fullscreenElement) await document.exitFullscreen(); else await turnero.requestFullscreen(); } catch { /* La vista normal sigue disponible. */ }
    });
    let cargando = false;
    setInterval(async () => {
      if (document.hidden || cargando) return;
      cargando = true;
      try {
        const r = await fetch('turnero.php?datos=1', { headers: { Accept: 'application/json' } });
        if (!r.ok) throw new Error('Sesión no disponible');
        const data = await r.json(); const lista = document.querySelector('[data-turnero-lista]'); lista.replaceChildren();
        for (const t of data.turnos) {
          const card = document.createElement('article'); card.className = 'turno-publico';
          for (const [tag, valor] of [['span',t.estado],['strong',t.codigo],['p',t.consultorio]]) { const el = document.createElement(tag); el.textContent = valor; card.append(el); }
          lista.append(card);
        }
        if (!data.turnos.length) lista.textContent = 'Los turnos aparecerán cuando recepción registre una llegada.';
        document.querySelector('[data-turnero-estado]').textContent = `Actualizado a las ${data.hora} · Se actualiza cada 20 segundos.`;
      } catch { document.querySelector('[data-turnero-estado]').textContent = 'No se pudo actualizar. Revisa la conexión o vuelve a iniciar sesión.'; }
      finally { cargando = false; }
    }, 20000);
  }
})();
