document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../php/login/verificar_sesion.php', {
            method: 'GET',
            credentials: 'include' 
        });
        
        const data = await response.json();
        
        if (!data.autenticado) {
            alert('Por favor, inicia sesión para acceder a este módulo.');
            window.location.href = '../vistas/login.html';
        }
    } catch (error) {
        console.error('Error al verificar la sesión:', error);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const endpoint = '../php/controllers/EmpaqueController.php';
    const form = document.querySelector('.empaque-form');
    const tabla = document.querySelector('.tabla-seccion table');
    const bienvenida = document.querySelector('.bienvenida p:first-child');
    const modeloSelect = document.getElementById('select-modelo-empaque');
    const unidadesPorCaja = document.getElementById('unidades-por-caja');
    const modalCrear = document.getElementById('modal-crear-caja');
    const modalBuscar = document.getElementById('modal-buscar-caja');
    const modalIncidente = document.getElementById('modal-incidente-empaque');
    if (!form || !tabla || !bienvenida || !modeloSelect || !unidadesPorCaja ||
        !modalCrear || !modalBuscar || !modalIncidente) {
        console.error('No se encontró la estructura de empaque.html');
        return;
    }

    // La selección de orden es necesaria para crear cajas ligadas a un cliente y modelo.
    // Reutilizamos el selector si ya se añadió manualmente al HTML.
    let selector = document.getElementById('orden-empaque');
    if (!selector) {
        const grupo = document.createElement('div');
        grupo.className = 'form-group';
        const label = document.createElement('label');
        label.htmlFor = 'orden-empaque';
        label.textContent = 'Orden';
        selector = document.createElement('select');
        selector.id = 'orden-empaque';
        grupo.append(label, selector);
        form.prepend(grupo);
    }

    const inputsFormulario = [...form.querySelectorAll('.form-group')]
        .filter(g => !g.contains(selector))
        .map(g => ({
            nombre: g.querySelector('label')?.textContent.trim(),
            input: g.querySelector('input')
        }));
    const campo = nombre => inputsFormulario.find(i => i.nombre === nombre)?.input;
    ['Código de rastreo', 'Cliente', 'Dirección', 'Fecha Compromiso']
        .forEach(nombre => { if (campo(nombre)) campo(nombre).readOnly = true; });
    modeloSelect.disabled = true;

    let csrf = '';
    let ordenes = [];
    let cajas = [];
    let capacidades = {};
    let cajaSeleccionada = '';
    let sesionActiva = false;
    let tbody = tabla.querySelector('tbody');
    if (!tbody) { tbody = document.createElement('tbody'); tabla.appendChild(tbody); }

    const btnCrear = form.querySelector('.empaque-botones button');
    const btnBuscar = document.querySelector('.btn-buscar');
    const btnIncidente = document.querySelector('.btn-incidente');
    const btnConfirmarCrear = document.getElementById('btn-confirmar-crear-caja');
    const btnConfirmarBuscar = document.getElementById('btn-confirmar-buscar-caja');
    const btnConfirmarIncidente = document.getElementById('btn-enviar-incidente-empaque');
    const botones = [btnCrear, btnBuscar, btnIncidente, btnConfirmarCrear, btnConfirmarBuscar, btnConfirmarIncidente];
    const cambiarBotones = estado => botones.filter(Boolean).forEach(btn => { btn.disabled = !estado; });

    function opciones(mensaje) { selector.replaceChildren(new Option(mensaje, '')); }
    function ordenActual() { return ordenes.find(o => o.ID_ORDEN === selector.value); }
    function cajaActual() { return cajas.find(c => c.ID_CAJA === cajaSeleccionada); }
    function abrir(modal) { modal?.classList.add('activo'); }
    function cerrar(modal) { modal?.classList.remove('activo'); }
    function aviso(mensaje) { window.alert(mensaje); }
    function etiqueta(modal, texto) {
        return [...modal.querySelectorAll('.form-group')].find(g => g.querySelector('label')?.textContent.trim() === texto);
    }
    function setInput(modal, texto, valor) {
        const input = etiqueta(modal, texto)?.querySelector('input');
        if (input) input.value = valor ?? '';
    }
    async function jsonRespuesta(response) {
        let datos;
        try { datos = await response.json(); }
        catch { throw new Error(`El servidor no respondió JSON (HTTP ${response.status}).`); }
        if (!response.ok || !datos.success) throw new Error(datos.error || `HTTP ${response.status}`);
        return datos;
    }
    async function cargar() {
        const datos = await jsonRespuesta(await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' }));
        csrf = datos.csrf;
        ordenes = Array.isArray(datos.ordenes) ? datos.ordenes : [];
        cajas = Array.isArray(datos.cajas) ? datos.cajas : [];
        capacidades = datos.capacidades || {};
        sesionActiva = true;
        bienvenida.textContent = `Bienvenid@, ${datos.usuario || 'Usuario'}`;
        const anterior = selector.value;
        opciones('Seleccione una orden');
        ordenes.forEach(o => selector.add(new Option(`${o.ID_ORDEN} (${o.MODELO})`, o.ID_ORDEN)));
        if (ordenes.some(o => o.ID_ORDEN === anterior)) selector.value = anterior;
        pintarOrden();
        pintarCajas();
    }
    function pintarOrden() {
        const o = ordenActual();
        if (campo('Cliente')) campo('Cliente').value = o?.CLIENTE || '';
        if (campo('Dirección')) campo('Dirección').value = o?.DIRECCION || '';
        if (campo('Fecha Compromiso')) campo('Fecha Compromiso').value = o?.FECHA_COMPROMISO ? String(o.FECHA_COMPROMISO).slice(0,10) : '';
        const modelo = o?.ID_producto ? o.ID_producto.replace(/^NS-/i, '') : '';
        modeloSelect.value = modelo;
        const capacidad = o ? capacidades[o.ID_producto] : null;
        unidadesPorCaja.textContent = capacidad ? `${capacidad} unidades por caja` : 'Seleccione un modelo:';
        if (campo('Código de rastreo')) campo('Código de rastreo').value = cajaActual()?.ID_CAJA || '';
    }
    function pintarCajas() {
        tbody.replaceChildren();
        const filtradas = selector.value ? cajas.filter(c => c.ID_ORDEN === selector.value) : cajas;
        if (!filtradas.length) {
            const fila = tbody.insertRow();
            const celda = fila.insertCell();
            celda.colSpan = 6;
            celda.textContent = 'Todavía no hay cajas para esta selección.';
            return;
        }
        filtradas.forEach(c => {
            const fila = tbody.insertRow();
            [c.ID_CAJA, c.CLIENTE, c.MODELO, `${c.ESTATUS} (${c.CANTIDAD_UNIDADES})`, c.FECHA_CREACION]
                .forEach(valor => { fila.insertCell().textContent = String(valor ?? ''); });
            const acciones = fila.insertCell();
            const capacidad = capacidades[c.ID_PRODUCTO] || 0;
            const agregar = document.createElement('button');
            agregar.className = 'btn btn-morado';
            agregar.textContent = 'Agregar unidades';
            agregar.disabled = c.ESTATUS !== 'ABIERTA';
            agregar.addEventListener('click', async () => {
                cajaSeleccionada = c.ID_CAJA;
                pintarOrden();
                const faltantes = capacidad - Number(c.CANTIDAD_UNIDADES);
                const solicitado = prompt(`Caja ${c.ID_CAJA}: ${c.CANTIDAD_UNIDADES}/${capacidad}. ¿Cuántas unidades APROBADAS agregar? (máx. ${faltantes})`);
                if (solicitado === null) return;
                if (!/^\d+$/.test(solicitado.trim()) || Number(solicitado) < 1 || Number(solicitado) > faltantes) {
                    return aviso('Introduce una cantidad entera válida dentro de la capacidad.');
                }
                await ejecutar(async () => {
                    const r = await enviar('agregar', { id_caja: c.ID_CAJA, cantidad: Number(solicitado) });
                    aviso(`Se agregaron ${r.agregadas} unidades. Total: ${r.total}/${r.capacidad}.`);
                });
            });
            acciones.appendChild(agregar);
            const cerrarBtn = document.createElement('button');
            cerrarBtn.className = 'btn btn-verde';
            cerrarBtn.textContent = 'Cerrar caja';
            cerrarBtn.disabled = c.ESTATUS !== 'ABIERTA' || Number(c.CANTIDAD_UNIDADES) !== capacidad;
            cerrarBtn.addEventListener('click', async () => {
                if (!confirm(`¿Cerrar la caja ${c.ID_CAJA}?`)) return;
                await ejecutar(async () => {
                    await enviar('cerrar', { id_caja: c.ID_CAJA });
                    aviso('Caja cerrada correctamente.');
                });
            });
            acciones.appendChild(cerrarBtn);
            const incidentBtn = document.createElement('button');
            incidentBtn.className = 'btn btn-rojo';
            incidentBtn.textContent = 'Incidente';
            incidentBtn.addEventListener('click', () => abrirIncidente(c.ID_CAJA));
            acciones.appendChild(incidentBtn);
        });
    }
    async function enviar(accion, extra) {
        if (!sesionActiva || !csrf) throw new Error('No hay una sesión válida.');
        const data = await jsonRespuesta(await fetch(endpoint, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ accion, ...extra })
        }));
        return data.resultado;
    }
    async function ejecutar(tarea) {
        cambiarBotones(false);
        try { await tarea(); await cargar(); }
        catch(e) { console.error(e); aviso(e.message); }
        finally { cambiarBotones(true); }
    }
    function abrirIncidente(id) {
        if (!id) return aviso('Selecciona una caja para reportar el incidente.');
        cajaSeleccionada = id;
        setInput(modalIncidente, 'Código de rastreo', id);
        setInput(modalIncidente, 'Fecha y Hora', new Date().toLocaleString('es-MX'));
        abrir(modalIncidente);
    }

    selector.addEventListener('change', () => { cajaSeleccionada = ''; pintarOrden(); pintarCajas(); });
    btnCrear?.addEventListener('click', () => {
        const o = ordenActual();
        if (!o) return aviso('Selecciona primero una orden.');
        setInput(modalCrear, 'Código de rastreo', 'Se generará automáticamente');
        setInput(modalCrear, 'Cliente', o.CLIENTE);
        setInput(modalCrear, 'Dirección', o.DIRECCION);
        setInput(modalCrear, 'Modelo', o.MODELO);
        setInput(modalCrear, 'Fecha Compromiso', String(o.FECHA_COMPROMISO).slice(0, 10));
        modalCrear.querySelectorAll('input').forEach(input => { input.readOnly = true; });
        abrir(modalCrear);
    });
    btnConfirmarCrear?.addEventListener('click', () => ejecutar(async () => {
        const o = ordenActual();
        if (!o) throw new Error('Selecciona primero una orden.');
        const r = await enviar('crear', { orden: o.ID_ORDEN });
        cajaSeleccionada = r.id_caja;
        cerrar(modalCrear);
        aviso(`Caja ${r.id_caja} creada. Ahora puedes asignarle unidades aprobadas.`);
    }));
    btnBuscar?.addEventListener('click', () => abrir(modalBuscar));
    btnConfirmarBuscar?.addEventListener('click', () => {
        const id = document.getElementById('buscar-id-caja').value.trim();
        const c = cajas.find(x => x.ID_CAJA.toUpperCase() === id.toUpperCase());
        if (!c) return aviso('No se encontró esa caja en los registros cargados.');
        cajaSeleccionada = c.ID_CAJA;
        selector.value = c.ID_ORDEN;
        pintarOrden();
        pintarCajas();
        cerrar(modalBuscar);
        aviso(`Caja ${c.ID_CAJA}: ${c.ESTATUS}, ${c.CANTIDAD_UNIDADES} unidades.`);
    });
    btnIncidente?.addEventListener('click', () => abrirIncidente(cajaSeleccionada));
    btnConfirmarIncidente?.addEventListener('click', () => ejecutar(async () => {
        const tipo = modalIncidente.querySelector('select').value;
        const descripcion = modalIncidente.querySelector('textarea').value.trim();
        if (!descripcion) throw new Error('Escribe la descripción del incidente.');
        const r = await enviar('incidente', { id_caja: cajaSeleccionada, tipo, descripcion });
        cerrar(modalIncidente);
        modalIncidente.querySelector('textarea').value = '';
        aviso(`Incidente registrado con folio ${r.id_incidencia}.`);
    }));
    [modalCrear, modalBuscar, modalIncidente].forEach(modal => {
        modal.querySelectorAll('.modal-close, .btn-gris').forEach(b => b.addEventListener('click', () => cerrar(modal)));
        modal.addEventListener('click', ev => { if (ev.target === modal) cerrar(modal); });
    });
    document.addEventListener('keydown', ev => {
        if (ev.key === 'Escape') [modalCrear, modalBuscar, modalIncidente].forEach(cerrar);
    });
    cambiarBotones(false);
    opciones('Cargando órdenes...');
    cargar().then(() => cambiarBotones(true)).catch(e => {
        bienvenida.textContent = e.message.includes('sesión') ? 'Bienvenid@, inicia sesión' : 'No fue posible cargar Empaque';
        opciones('No se pudieron cargar órdenes');
        console.error(e);
        aviso(e.message);
    });
});
