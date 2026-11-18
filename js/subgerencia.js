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

    const endpoint = '../php/controllers/SubgerenciaController.php';
    const $ = (selector, padre = document) => padre.querySelector(selector);
    const bienvenida = $('.bienvenida p:first-child');
    const grafica = $('.grafica-barras-horizontal');
    const solicitudesContenedor = $('.subgerencia-solicitudes');
    const modalSolicitud = $('#modal-solicitud-subgerencia');
    const modalConfirmacion = $('#modal-confirmacion-subgerencia');

    if (!bienvenida || !grafica || !solicitudesContenedor || !modalSolicitud || !modalConfirmacion) {
        console.error('Subgerencia: faltan elementos esperados en el HTML.');
        return;
    }

    let csrf = '';
    let solicitudes = [];
    let solicitudSeleccionada = null;
    let autorizado = false;

    function avisar(mensaje) {
        $('#titulo-confirmacion-subgerencia').textContent = 'Aviso';
        $('#mensaje-confirmacion-subgerencia').textContent = mensaje;
        modalConfirmacion.classList.add('activo');
    }

    function abrir(modal) { modal.classList.add('activo'); }
    function cerrar(modal) { modal.classList.remove('activo'); }

    async function leerJson(respuesta) {
        let datos;
        try { datos = await respuesta.json(); }
        catch { throw new Error('El servidor no envió JSON válido. Revisa la terminal de PHP.'); }
        if (!respuesta.ok || !datos.success) {
            throw new Error(datos.error || `Error HTTP ${respuesta.status}`);
        }
        return datos;
    }

    function pintarResumen(datos) {
        const fecha = $('.subgerencia-grafica > p');
        if (fecha) fecha.textContent = `Consulta: ${new Date().toLocaleDateString('es-MX')} · Datos acumulados`;
        const subtitulo = $('.subgerencia-grafica > h4');
        if (subtitulo) subtitulo.textContent = 'Unidades registradas por línea (acumulado)';

        const resumen = grafica.firstElementChild;
        if (resumen) {
            const bloques = resumen.children;
            const etiquetas = ['Órdenes registradas', 'Unidades ensambladas', 'Líneas registradas'];
            const valores = [datos.total_ordenes, datos.total_unidades, datos.total_lineas];
            for (let i = 0; i < Math.min(3, bloques.length); i++) {
                const parrafos = bloques[i].querySelectorAll('p');
                if (parrafos[0]) parrafos[0].textContent = etiquetas[i];
                if (parrafos[1]) parrafos[1].textContent = String(valores[i] ?? 0);
            }
        }

        grafica.querySelectorAll('.barra-item').forEach(elemento => elemento.remove());
        const maximo = Math.max(1, ...datos.lineas.map(l => Number(l.UNIDADES) || 0));
        datos.lineas.forEach(linea => {
            const fila = document.createElement('div');
            fila.className = 'barra-item';
            const nombre = document.createElement('span');
            nombre.className = 'barra-label';
            nombre.textContent = linea.LINEA;
            const barra = document.createElement('div');
            barra.className = 'barra-progreso';
            barra.style.width = `${Math.round((Number(linea.UNIDADES) || 0) / maximo * 100)}%`;
            barra.textContent = String(linea.UNIDADES);
            fila.append(nombre, barra);
            grafica.appendChild(fila);
        });
        const escalaFicticia = grafica.nextElementSibling;
        if (escalaFicticia && escalaFicticia.querySelectorAll('span').length === 3) {
            escalaFicticia.remove();
        }
        if (!datos.lineas.length) {
            const texto = document.createElement('p');
            texto.textContent = 'No hay líneas registradas en MySQL.';
            grafica.appendChild(texto);
        }
    }

    function pintarSolicitudes() {
        solicitudesContenedor.querySelectorAll('.solicitud-item').forEach(item => item.remove());
        const titulo = solicitudesContenedor.querySelector('h3');
        const estado = document.createElement('p');
        estado.style.margin = '10px 0';
        estado.className = 'subgerencia-vacio';
        solicitudesContenedor.querySelectorAll('.subgerencia-vacio').forEach(item => item.remove());
        if (!solicitudes.length) {
            estado.textContent = 'No hay solicitudes de cambio.';
            titulo?.insertAdjacentElement('afterend', estado);
            return;
        }
        solicitudes.forEach(solicitud => {
            const item = document.createElement('div');
            item.className = 'solicitud-item';
            const texto = document.createElement('span');
            texto.textContent = `#${solicitud.ID_SOLICITUD} · ${solicitud.OBJETO} (${solicitud.ESTATUS})`;
            const boton = document.createElement('button');
            boton.className = 'btn btn-morado btn-abrir-solicitud';
            boton.textContent = 'Opciones';
            boton.addEventListener('click', () => mostrarSolicitud(solicitud));
            item.append(texto, boton);
            solicitudesContenedor.appendChild(item);
        });
    }

    function mostrarSolicitud(solicitud) {
        solicitudSeleccionada = solicitud;
        const cuerpo = $('#tabla-solicitud-body');
        cuerpo.replaceChildren();
        const fila = cuerpo.insertRow();
        [solicitud.OBJETO, solicitud.USUARIO, solicitud.FECHA_SOLICITUD, solicitud.MOTIVO]
            .forEach(valor => { fila.insertCell().textContent = String(valor ?? ''); });
        const pendiente = ['PENDIENTE', 'EN_REVISION', 'EN REVISION', 'EN REVISIÓN']
            .includes(String(solicitud.ESTATUS).toUpperCase());
        $('#btn-aprobar-solicitud').disabled = !pendiente;
        $('#btn-rechazar-solicitud').disabled = !pendiente;
        abrir(modalSolicitud);
    }

    async function cargarTablero() {
        const respuesta = await fetch(`${endpoint}?accion=tablero`, {
            credentials: 'same-origin', cache: 'no-store'
        });
        const datos = await leerJson(respuesta);
        csrf = datos.csrf;
        if (!csrf) throw new Error('No se recibió token de seguridad.');
        autorizado = true;
        bienvenida.textContent = `Bienvenid@, ${datos.usuario}`;
        pintarResumen(datos.datos);
        solicitudes = datos.datos.solicitudes || [];
        pintarSolicitudes();
    }

    async function decidir(accion) {
        if (!autorizado || !solicitudSeleccionada) return;
        const boton = accion === 'aprobar' ? $('#btn-aprobar-solicitud') : $('#btn-rechazar-solicitud');
        boton.disabled = true;
        try {
            const respuesta = await fetch(endpoint, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
                body: JSON.stringify({accion, id_solicitud: Number(solicitudSeleccionada.ID_SOLICITUD)})
            });
            const datos = await leerJson(respuesta);
            cerrar(modalSolicitud);
            await cargarTablero();
            avisar(`Solicitud #${datos.id_solicitud}: ${datos.estatus}.`);
        } catch (error) {
            console.error(error);
            avisar(error.message);
        } finally {
            boton.disabled = false;
        }
    }

    async function descargar(tipo) {
        if (!autorizado) return avisar('Primero inicia sesión como usuario autorizado.');
        const accion = tipo === 'lineas' ? 'reporte_lineas' : 'reporte_ordenes';
        try {
            const respuesta = await fetch(`${endpoint}?accion=${accion}`, {
                credentials: 'same-origin', cache: 'no-store'
            });
            if (!respuesta.ok) {
                const datos = await respuesta.json().catch(() => ({}));
                throw new Error(datos.error || 'No se pudo generar el reporte.');
            }
            const archivo = await respuesta.blob();
            const url = URL.createObjectURL(archivo);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = `NovaSound_${tipo}_${new Date().toISOString().slice(0,10)}.csv`;
            document.body.appendChild(enlace);
            enlace.click();
            enlace.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            avisar('Se ha preparado la descarga del reporte CSV.');
        } catch (error) {
            console.error(error);
            avisar(error.message);
        }
    }

    $('#btn-descargar-linea')?.addEventListener('click', () => descargar('lineas'));
    $('#btn-descargar-ordenes')?.addEventListener('click', () => descargar('ordenes'));
    $('#btn-aprobar-solicitud')?.addEventListener('click', () => decidir('aprobar'));
    $('#btn-rechazar-solicitud')?.addEventListener('click', () => decidir('rechazar'));
    $('#btn-volver-solicitud')?.addEventListener('click', () => cerrar(modalSolicitud));

    document.querySelectorAll('.modal').forEach(modal => {
        modal.querySelectorAll('.modal-close, .modal-footer .btn-azul').forEach(boton => {
            boton.addEventListener('click', () => cerrar(modal));
        });
        modal.addEventListener('click', evento => {
            if (evento.target === modal) cerrar(modal);
        });
    });
    $('#btn-volver-confirmacion-subgerencia')?.addEventListener('click', () => cerrar(modalConfirmacion));
    document.addEventListener('keydown', evento => {
        if (evento.key === 'Escape') document.querySelectorAll('.modal.activo').forEach(cerrar);
    });

    bienvenida.textContent = 'Bienvenid@, cargando...';
    cargarTablero().catch(error => {
        console.error('Subgerencia:', error);
        bienvenida.textContent = 'No fue posible cargar Subgerencia';
        avisar(`${error.message}\nComprueba que tu sesión esté iniciada y los archivos PHP estén completos.`);
    });
});
