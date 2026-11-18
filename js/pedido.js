'use strict';
// Utiliza los IDs existentes en vistas/pedido.html. No requiere cambios de estructura ni CSS.
document.addEventListener('DOMContentLoaded', async () => {
    const API = '../php/controllers/PedidoMultipleController.php';
    const $ = id => document.getElementById(id);
    const pesos = n => new Intl.NumberFormat('es-MX', {style:'currency', currency:'MXN'}).format(n);
    const modelosOrden = ['MINI', 'MAX', 'SPORT', 'PRO'];
    const estado = {productos: new Map(), filas: [], csrf: ''};
    const modal = (id, abrir) => $(id)?.classList.toggle('activo', abrir);
    const abrir = id => modal(id, true);
    const cerrar = id => modal(id, false);
    const msj = mensaje => window.alert(mensaje);
    const escapeHtml = x => String(x).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // El catálogo viene de la BD, incluidos los precios y las unidades por caja.
    try {
        const respuesta = await fetch(API, {credentials:'same-origin', cache:'no-store'});
        const json = await respuesta.json();
        if (!respuesta.ok || !json.success) throw new Error(json.error || 'No se pudo cargar el catálogo');
        estado.csrf = json.csrf;
        json.productos.forEach(p => estado.productos.set(p.modelo, p));
        if (!estado.productos.size) msj('No hay productos MINI/MAX/SPORT/PRO en MySQL.');
    } catch (e) {
        console.error('Pedido:', e);
        msj('No se pudo conectar con el backend: ' + e.message);
    }

    function resumen() {
        let cajas = 0, unidades = 0, costo = 0;
        for (const fila of estado.filas) {
            const producto = estado.productos.get(fila.modelo);
            if (!producto) continue;
            cajas += fila.cajas;
            unidades += fila.cajas * producto.unidades_por_caja;
            costo += fila.cajas * producto.unidades_por_caja * producto.precio;
        }
        $('resumen-total-cajas').textContent = cajas;
        $('resumen-total-unidades').textContent = unidades;
        $('resumen-costo-total').textContent = pesos(costo);
        return {cajas, unidades, costo};
    }

    function pintar() {
        const tbody = $('tabla-pedido-body');
        tbody.replaceChildren();
        for (const [indice, fila] of estado.filas.entries()) {
            const producto = estado.productos.get(fila.modelo);
            if (!producto) continue;
            const tr = document.createElement('tr');
            const opciones = producto.colores.map(c => `<option value="${escapeHtml(c)}" ${c === fila.color ? 'selected' : ''}>${escapeHtml(c)}</option>`).join('');
            tr.innerHTML = `
                <td>${escapeHtml(fila.modelo)}</td>
                <td><select aria-label="Color de ${escapeHtml(fila.modelo)}" data-indice="${indice}" data-campo="color">${opciones}</select></td>
                <td>Caja c/${producto.unidades_por_caja} unidades</td>
                <td><input aria-label="Cajas de ${escapeHtml(fila.modelo)}" type="number" min="1" max="10000" step="1" value="${fila.cajas}" data-indice="${indice}" data-campo="cajas" style="width:80px"></td>
                <td>${fila.cajas * producto.unidades_por_caja}</td>
                <td><button type="button" class="btn btn-rojo" data-quitar="${indice}">Quitar</button></td>`;
            tbody.appendChild(tr);
        }
        resumen();
    }

    $('tabla-pedido-body').addEventListener('change', ev => {
        const el = ev.target;
        const i = Number(el.dataset.indice);
        if (!Number.isInteger(i) || !estado.filas[i]) return;
        if (el.dataset.campo === 'color') {
            const color = el.value;
            const repetido = estado.filas.some((f, n) => n !== i && f.modelo === estado.filas[i].modelo && f.color === color);
            if (repetido) {msj('Ese modelo y color ya están agregados.'); pintar(); return;}
            estado.filas[i].color = color;
        } else if (el.dataset.campo === 'cajas') {
            const n = Number(el.value);
            if (!Number.isInteger(n) || n < 1 || n > 10000) {msj('Ingresa entre 1 y 10000 cajas.'); pintar(); return;}
            estado.filas[i].cajas = n;
        }
        pintar();
    });
    $('tabla-pedido-body').addEventListener('click', ev => {
        const boton = ev.target.closest('[data-quitar]');
        if (!boton) return;
        estado.filas.splice(Number(boton.dataset.quitar), 1);
        pintar();
    });

    $('btn-abrir-agregar-modelo')?.addEventListener('click', () => abrir('modal-agregar-modelo'));
    $('btn-confirmar-agregar-modelo')?.addEventListener('click', () => {
        const elegidos = [...document.querySelectorAll('.check-modelo:checked')].map(c => c.value);
        if (!elegidos.length) {msj('Selecciona al menos un modelo.'); return;}
        for (const modelo of modelosOrden.filter(m => elegidos.includes(m))) {
            const p = estado.productos.get(modelo);
            if (!p) {msj('El modelo ' + modelo + ' no está registrado en productos.'); continue;}
            if (!estado.filas.some(f => f.modelo === modelo)) {
                estado.filas.push({modelo, color:p.colores[0], cajas:1});
            } else {
                // Para elegir otro color del mismo modelo se puede cambiar el color de la fila.
                console.info('Modelo ya agregado:', modelo);
            }
        }
        document.querySelectorAll('.check-modelo').forEach(c => c.checked = false);
        cerrar('modal-agregar-modelo'); pintar();
    });
    const validar = () => {
        const cliente = $('input-cliente-pedido').value.trim();
        const direccion = $('input-direccion-pedido').value.trim();
        const fecha = $('fecha-compromiso-pedido').value;
        const hoy = new Date().toLocaleDateString('en-CA');
        if (!cliente || !direccion || !fecha) {msj('Completa cliente, dirección y fecha compromiso.'); return false;}
        if (fecha < hoy) {msj('La fecha compromiso no puede ser anterior a hoy.'); return false;}
        if (!estado.filas.length) {msj('Agrega por lo menos un modelo.'); return false;}
        return {cliente, direccion, fecha_compromiso: fecha,
            detalles: estado.filas.map(f => ({modelo:f.modelo, color:f.color, cajas:f.cajas}))};
    };
    $('btn-abrir-confirmar-pedido')?.addEventListener('click', () => {
        if (!validar()) return;
        const total = resumen();
        const tr = document.createElement('tr');
        const modelos = [...new Set(estado.filas.map(f=>f.modelo))].join(', ');
        [modelos, String(total.cajas), String(total.unidades), pesos(total.costo)].forEach(valor=>{
            const td = document.createElement('td'); td.textContent = valor; td.style.padding = '8px'; tr.appendChild(td);
        });
        $('tabla-confirmacion-body').replaceChildren(tr);
        abrir('modal-confirmar-pedido');
    });

    let enviando = false;
    $('btn-confirmar-pedido-final')?.addEventListener('click', async () => {
        const datos = validar();
        if (!datos || enviando) return;
        enviando = true;
        const boton = $('btn-confirmar-pedido-final'); boton.disabled = true;
        try {
            const respuesta = await fetch(API, {method:'POST', credentials:'same-origin',
                headers:{'Content-Type':'application/json'}, body:JSON.stringify({...datos,csrf:estado.csrf})});
            const json = await respuesta.json();
            if (!respuesta.ok || !json.success) throw new Error(json.error || 'No se pudo guardar el pedido');
            estado.csrf = json.csrf;
            cerrar('modal-confirmar-pedido');
            const aviso = $('modal-pedido-exitoso').querySelector('.modal-body p');
            aviso.textContent = 'Solicitud ' + json.pedido.folio + ' registrada con éxito. Estado: PENDIENTE de revisión.';
            abrir('modal-pedido-exitoso');
            estado.filas = [];
            pintar();
        } catch(e) {console.error(e);msj('No se pudo registrar el pedido: ' + e.message);}
        finally {enviando = false; boton.disabled = false;}
    });
    const vinculos = [
        ['cerrar-agregar-modelo','modal-agregar-modelo'],
        ['btn-cancelar-agregar-modelo','modal-agregar-modelo'],
        ['cerrar-confirmar-pedido','modal-confirmar-pedido'],
        ['btn-regresar-confirmar-pedido','modal-confirmar-pedido'],
        ['cerrar-pedido-exitoso','modal-pedido-exitoso'],
    ];
    vinculos.forEach(([boton,id]) => $(boton)?.addEventListener('click', () => cerrar(id)));
    document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => {if(e.target === m) cerrar(m.id);}));
    document.addEventListener('keydown', e => {if(e.key === 'Escape') document.querySelectorAll('.modal.activo').forEach(m=>cerrar(m.id));});
});
