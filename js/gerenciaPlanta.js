document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // Adaptado a la estructura ORIGINAL de gerencia.html (sin agregar paneles).
    const api = '../php/controllers/GerenciaPlantaController.php';
    const porId = id => document.getElementById(id);

    function abrir(id) {
        const modal = porId(id);
        if (modal) modal.classList.add('activo');
    }

    function cerrar(id) {
        const modal = porId(id);
        if (modal) modal.classList.remove('activo');
    }

    function numero(valor) {
        return Number(valor || 0).toLocaleString('es-MX');
    }

    async function obtenerJSON() {
        const respuesta = await fetch(api, {
            credentials: 'same-origin',
            cache: 'no-store'
        });
        let datos;
        try {
            datos = await respuesta.json();
        } catch (_) {
            throw new Error('El controlador no devolvió JSON válido. Revisa PHP.');
        }
        if (!respuesta.ok || !datos.success) {
            throw new Error(datos.error || `Error HTTP ${respuesta.status}`);
        }
        return datos;
    }

    function escribirTarjeta(id, titulo, pares) {
        const tarjeta = porId(id);
        if (!tarjeta) return;
        tarjeta.replaceChildren();
        const h3 = document.createElement('h3');
        h3.textContent = titulo;
        tarjeta.appendChild(h3);
        pares.forEach(([etiqueta, valor]) => {
            const p = document.createElement('p');
            p.style.marginTop = '12px';
            p.style.color = '#333';
            const fuerte = document.createElement('strong');
            fuerte.textContent = etiqueta + ': ';
            p.append(fuerte, document.createTextNode(String(valor)));
            tarjeta.appendChild(p);
        });
    }

    function escribirTabla(id, registros, columnas) {
        const tbody = porId(id);
        if (!tbody) return;
        tbody.replaceChildren();
        if (!registros.length) {
            const fila = tbody.insertRow();
            const celda = fila.insertCell();
            celda.colSpan = columnas.length;
            celda.textContent = 'No hay registros disponibles.';
            return;
        }
        registros.forEach(registro => {
            const fila = tbody.insertRow();
            columnas.forEach(columna => {
                fila.insertCell().textContent = String(registro[columna] ?? '');
            });
        });
    }

    function escribirResumen(resumen, fecha) {
        const lista = porId('resumen-general');
        if (!lista) return;
        lista.replaceChildren();
        const items = [
            ['Órdenes de producción', numero(resumen.produccion.ordenes)],
            ['Unidades ensambladas', numero(resumen.produccion.unidades)],
            ['Unidades aprobadas por Calidad', numero(resumen.calidad.aprobadas)],
            ['Incidencias registradas', numero(resumen.almacen.incidencias)],
            ['Embarques registrados', numero(resumen.almacen.embarques)],
            ['Cumplimiento de metas', 'Pendiente: no hay metas configuradas'],
            ['Fecha de consulta', fecha]
        ];
        items.forEach(([etiqueta, valor]) => {
            const li = document.createElement('li');
            li.textContent = `${etiqueta}: ${valor}`;
            lista.appendChild(li);
        });
    }

    function mostrarPanel(respuesta) {
        const datos = respuesta.datos;
        const r = datos.resumen;
        porId('nombre-gerencia').textContent = 'Bienvenid@, ' + respuesta.usuario;

        escribirTarjeta('tarjeta-produccion', 'Metas Producción', [
            ['Órdenes registradas', numero(r.produccion.ordenes)],
            ['Unidades ensambladas', numero(r.produccion.unidades)],
            ['Líneas registradas', numero(r.produccion.lineas)],
            ['Meta', 'No configurada']
        ]);
        escribirTarjeta('tarjeta-calidad', 'Metas Calidad', [
            ['Unidades registradas', numero(r.calidad.total)],
            ['Aprobadas', numero(r.calidad.aprobadas)],
            ['Rechazadas', numero(r.calidad.rechazadas)],
            ['Otros estados', numero(r.calidad.otras)],
            ['Meta', 'No configurada']
        ]);
        escribirTarjeta('tarjeta-almacen', 'Metas Almacén y embarques', [
            ['Cajas registradas', numero(r.almacen.cajas)],
            ['Cajas cerradas', numero(r.almacen.cajas_cerradas)],
            ['Embarques', numero(r.almacen.embarques)],
            ['Meta', 'No configurada']
        ]);

        escribirTabla('tabla-incidencias-body', datos.incidencias || [], [
            'ID_INCIDENCIA', 'ID_ORDEN', 'ID_USUARIO',
            'ID_PRODUCTO', 'FECHA', 'DESCRIPCION'
        ]);
        escribirTabla('tabla-embarques-gerencia-body', datos.embarques || [], [
            'ID_EMBARQUE', 'CLIENTE', 'FECHA_SALIDA',
            'FECHA_PROMESA', 'DESTINO', 'ESTATUS'
        ]);
        escribirResumen(r, datos.fecha_consulta);
    }

    function filtrar(idInput, idTabla) {
        const texto = (porId(idInput)?.value || '').trim().toLowerCase();
        const tbody = porId(idTabla);
        if (!tbody) return;
        tbody.querySelectorAll('tr').forEach(fila => {
            fila.hidden = !fila.textContent.toLowerCase().includes(texto);
        });
    }

    async function descargar(tipo) {
        try {
            const respuesta = await fetch(
                `${api}?accion=descargar&tipo=${encodeURIComponent(tipo)}`,
                { credentials: 'same-origin', cache: 'no-store' }
            );
            if (!respuesta.ok) {
                const error = await respuesta.json().catch(() => ({}));
                throw new Error(error.error || `Error HTTP ${respuesta.status}`);
            }
            const archivo = await respuesta.blob();
            const url = URL.createObjectURL(archivo);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = `NovaSound_${tipo}.csv`;
            document.body.appendChild(enlace);
            enlace.click();
            enlace.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            porId('mensaje-reporte-descargado').textContent =
                `Se generó el reporte CSV de ${tipo}.`;
            abrir('modal-reporte-descargado');
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    }

    const alClick = (id, accion) => {
        const elemento = porId(id);
        if (elemento) elemento.addEventListener('click', accion);
    };

    alClick('btn-abrir-enviar-reporte', () => abrir('modal-enviar-reporte'));
    alClick('btn-abrir-reporte-incidencias', () => abrir('modal-reporte-incidencias'));
    alClick('btn-abrir-reporte-embarques', () => abrir('modal-reporte-embarques'));
    alClick('btn-abrir-info-general', () => abrir('modal-info-general'));
    alClick('btn-descargar-reporte-incidencias', () => descargar('incidencias'));
    alClick('btn-descargar-reporte-embarques', () => descargar('embarques'));
    alClick('btn-confirmar-enviar-reporte', () => {
        alert('El envío por correo no está configurado. No se envió ningún mensaje.');
    });
    alClick('buscar-incidencias-gerencia', () => {});
    porId('buscar-incidencias-gerencia')?.addEventListener('input', () => {
        filtrar('buscar-incidencias-gerencia', 'tabla-incidencias-body');
    });
    porId('buscar-embarques-gerencia')?.addEventListener('input', () => {
        filtrar('buscar-embarques-gerencia', 'tabla-embarques-gerencia-body');
    });

    [
        'reporte-incidencias', 'reporte-embarques', 'info-general',
        'reporte-descargado', 'enviar-reporte'
    ].forEach(nombre => {
        const modal = porId(`modal-${nombre}`);
        if (!modal) return;
        modal.querySelectorAll('.modal-close, .btn-gris, .modal-footer .btn-verde')
            .forEach(boton => {
                if (boton.id.startsWith('btn-descargar-') ||
                    boton.id === 'btn-confirmar-enviar-reporte') return;
                boton.addEventListener('click', () => cerrar(modal.id));
            });
        modal.addEventListener('click', evento => {
            if (evento.target === modal) cerrar(modal.id);
        });
    });
    document.addEventListener('keydown', evento => {
        if (evento.key === 'Escape') {
            document.querySelectorAll('.modal.activo')
                .forEach(modal => modal.classList.remove('activo'));
        }
    });

    obtenerJSON().then(mostrarPanel).catch(error => {
        console.error('Gerencia de Planta:', error);
        porId('nombre-gerencia').textContent =
            'No fue posible cargar la información de Gerencia';
        alert(error.message);
    });
});
