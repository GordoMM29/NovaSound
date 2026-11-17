
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const endpoint = '../php/controllers/EnsambleController.php';
    const endpointOrdenes = '../php/controllers/OrdenController.php';

    let token = '';
    let ordenes = [];
    let sesionActiva = false;

    // Elementos del HTML existente
    const selector = document.getElementById('orden-ensamble');
    const bienvenida = document.getElementById('nombre-usuario');
    const tabla = document.querySelector('.tabla-seccion table');
    const botones = document.querySelectorAll('.ensamble-botones button');

    const campos = {
        cliente: document.getElementById('cliente-ensamble'),
        modelo: document.getElementById('modelo-ensamble'),
        cantidad: document.getElementById('cantidad-ensamble'),
        color: document.getElementById('color-ensamble'),
        fecha: document.getElementById('fecha-ensamble')
    };

    const modalPruebas =
        document.getElementById('modal-pruebas-ensamble');

    const modalRegistro =
        document.getElementById('modal-registrar-ensamble');

    const modalIncidente =
        document.getElementById('modal-incidente-ensamble');

    const modales = [
        modalPruebas,
        modalRegistro,
        modalIncidente
    ];

    if (!selector || !bienvenida || !tabla ||
        botones.length < 3 ||
        Object.values(campos).some(campo => !campo) ||
        modales.some(modal => !modal)) {

        console.error(
            'Faltan elementos necesarios en ensamble.html'
        );
        return;
    }

    // No creamos otro selector ni otra etiqueta Orden.
    // Usamos los elementos que ya existen en HTML.

    let tbody = tabla.querySelector('tbody');

    if (!tbody) {
        tbody = document.createElement('tbody');
        tabla.appendChild(tbody);
    }


    function aviso(mensaje) {
        alert(mensaje);
    }

    function ordenActual() {
        return ordenes.find(
            orden => orden.ID_ORDEN === selector.value
        );
    }

    function ponerOpcion(mensaje) {
        selector.replaceChildren(
            new Option(mensaje, '')
        );
    }

    function cerrarModal(modal) {
        if (modal) {
            modal.classList.remove('activo');
        }
    }

    function limpiarCampos() {
        Object.values(campos).forEach(campo => {
            campo.value = '';
        });
    }

    function establecerBotones(habilitados) {
        botones.forEach(boton => {
            boton.disabled = !habilitados;
        });
    }

    async function jsonRespuesta(respuesta) {
        let datos;

        try {
            datos = await respuesta.json();
        } catch {
            throw new Error(
                'El servidor no devolvió una respuesta JSON válida.'
            );
        }

        if (respuesta.status === 401) {
            throw new Error(
                'SESIÓN: No existe una sesión activa.'
            );
        }

        if (!respuesta.ok || !datos.success) {
            throw new Error(
                datos.error || `Error HTTP ${respuesta.status}`
            );
        }

        return datos;
    }

    async function cargarHistorial() {
        const url = selector.value
            ? `${endpoint}?orden=${encodeURIComponent(selector.value)}`
            : endpoint;

        const respuesta = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        });

        const datos = await jsonRespuesta(respuesta);

        if (!datos.csrf) {
            throw new Error(
                'El servidor no proporcionó el token de seguridad.'
            );
        }

        token = datos.csrf;
        sesionActiva = true;

        bienvenida.dataset.usuario = datos.usuario || '';
        bienvenida.textContent =
            `Bienvenid@, ${datos.usuario || 'Usuario'}`;

        pintarHistorial(datos.registros || []);
    }

    async function cargarOrdenes() {
        ponerOpcion('Cargando órdenes...');

        const respuesta = await fetch(endpointOrdenes, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        });

        const datos = await jsonRespuesta(respuesta);

        ordenes = Array.isArray(datos.ordenes)
            ? datos.ordenes
            : [];

        ponerOpcion('Seleccione una orden');

        ordenes.forEach(orden => {
            selector.add(
                new Option(orden.ID_ORDEN, orden.ID_ORDEN)
            );
        });

        limpiarCampos();

        if (ordenes.length === 0) {
            console.log(
                'No hay órdenes de producción registradas.'
            );
        }
    }

    function pintarOrden() {
        const orden = ordenActual();

        if (!orden) {
            limpiarCampos();
            return;
        }

        campos.cliente.value = orden.CLIENTE || '';
        campos.modelo.value = orden.MODELO || '';
        campos.cantidad.value =
            orden.CANTIDAD_PRODUCIDA ?? '';
        campos.color.value = orden.COLOR || '';

        campos.fecha.value = orden.FECHA_COMPROMISO
            ? String(orden.FECHA_COMPROMISO).slice(0, 10)
            : '';
    }

    function ponerCampoPorEtiqueta(modal, etiqueta, valor) {
        const grupos = modal.querySelectorAll('.form-group');

        grupos.forEach(grupo => {
            const label = grupo.querySelector('label');
            const input = grupo.querySelector('input');

            if (
                label &&
                input &&
                label.textContent.trim() === etiqueta
            ) {
                input.value = valor ?? '';
            }
        });
    }

    function rellenarModal(modal, orden) {
        ponerCampoPorEtiqueta(
            modal, 'Orden', orden.ID_ORDEN
        );

        ponerCampoPorEtiqueta(
            modal, 'Modelo', orden.MODELO
        );

        ponerCampoPorEtiqueta(
            modal, 'Línea', orden.LINEA
        );

        ponerCampoPorEtiqueta(
            modal,
            'Cantidad Producida',
            orden.CANTIDAD_PRODUCIDA
        );

        ponerCampoPorEtiqueta(
            modal,
            'Técnico',
            bienvenida.dataset.usuario || ''
        );

        if (modal === modalIncidente) {
            ponerCampoPorEtiqueta(
                modal,
                'Fecha y Hora',
                new Date().toLocaleString('es-MX')
            );
        }
    }

    function abrirModal(modal) {
        if (!sesionActiva) {
            aviso('Debes iniciar sesión para utilizar Ensamble.');
            return;
        }

        const orden = ordenActual();

        if (!orden) {
            aviso('Selecciona primero una orden de producción.');
            return;
        }

        rellenarModal(modal, orden);
        modal.classList.add('activo');
    }

    function pintarHistorial(registros) {
        tbody.replaceChildren();

        if (registros.length === 0) {
            const fila = tbody.insertRow();
            const celda = fila.insertCell();

            celda.colSpan = 6;
            celda.textContent =
                'Todavía no hay registros de ensamble.';
            return;
        }

        registros.forEach(registro => {
            const fila = tbody.insertRow();

            const valores = [
                registro.MODELO,
                registro.ID_ORDEN,
                registro.USUARIO,
                registro.FECHA_REGISTRO,
                `${registro.CANTIDAD} unid. - ${
                    registro.OBSERVACIONES || 'Sin observaciones'
                }`,
                registro.ESTATUS
            ];

            valores.forEach(valor => {
                fila.insertCell().textContent =
                    String(valor ?? '');
            });
        });
    }

    async function enviar(accion, extra = {}) {
        if (!sesionActiva || !token) {
            throw new Error('No hay una sesión activa.');
        }

        const orden = ordenActual();

        if (!orden) {
            throw new Error('Selecciona una orden.');
        }

        const respuesta = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
            },
            body: JSON.stringify({
                accion: accion,
                orden: orden.ID_ORDEN,
                ...extra
            })
        });

        return jsonRespuesta(respuesta);
    }

    async function ejecutar(boton, tarea) {
        boton.disabled = true;

        try {
            await tarea();
            await cargarHistorial();
        } catch (error) {
            console.error(error);
            aviso(error.message);
        } finally {
            boton.disabled = !sesionActiva;
        }
    }
    selector.addEventListener('change', async () => {
        pintarOrden();

        if (!sesionActiva) return;

        try {
            await cargarHistorial();
        } catch (error) {
            console.error(error);
            aviso(error.message);
        }
    });

    botones[0].addEventListener('click', () => {
        abrirModal(modalPruebas);
    });

    botones[1].addEventListener('click', () => {
        abrirModal(modalRegistro);
    });

    botones[2].addEventListener('click', () => {
        abrirModal(modalIncidente);
    });

    document.getElementById(
        'btn-confirmar-registrar-ensamble'
    ).addEventListener('click', evento => {

        ejecutar(evento.currentTarget, async () => {
            const cantidad = Number(
                modalRegistro.querySelector(
                    'input[type="number"]'
                ).value
            );

            const observaciones = modalRegistro
                .querySelector('textarea')
                .value.trim();

            if (!Number.isInteger(cantidad) || cantidad < 1) {
                throw new Error(
                    'Introduce una cantidad entera positiva.'
                );
            }

            if (observaciones.length > 300) {
                throw new Error(
                    'Máximo 300 caracteres en observaciones.'
                );
            }

            const datos = await enviar('registrar', {
                cantidad,
                observaciones
            });

            cerrarModal(modalRegistro);

            modalRegistro.querySelector(
                'input[type="number"]'
            ).value = '';

            modalRegistro.querySelector('textarea').value = '';

            aviso(
                `Se registraron ${datos.resultado.cantidad} unidades. ` +
                `Folio: ${datos.resultado.id_registro}`
            );
        });
    });

    document.getElementById(
        'btn-confirmar-pruebas'
    ).addEventListener('click', evento => {

        ejecutar(evento.currentTarget, async () => {
            const datos = await enviar('enviar_pruebas');

            cerrarModal(modalPruebas);

            aviso(
                `Enviadas a pruebas: ` +
                `${datos.resultado.unidades} unidades ` +
                `(${datos.resultado.registros} registros).`
            );
        });
    });

    document.getElementById(
        'btn-enviar-incidente-ensamble'
    ).addEventListener('click', evento => {

        ejecutar(evento.currentTarget, async () => {
            const tipo = modalIncidente
                .querySelector('select')
                .value;

            const descripcion = modalIncidente
                .querySelector('textarea')
                .value.trim();

            if (!descripcion || descripcion.length > 200) {
                throw new Error(
                    'Describe el incidente (máximo 200 caracteres).'
                );
            }

            const datos = await enviar('incidente', {
                tipo,
                descripcion
            });

            cerrarModal(modalIncidente);

            modalIncidente.querySelector('textarea').value = '';

            aviso(
                `Incidente registrado con folio ` +
                `${datos.resultado.id_incidencia}.`
            );
        });
    });

    modales.forEach(modal => {
        modal.querySelectorAll(
            '.modal-close, .btn-gris'
        ).forEach(boton => {

            boton.addEventListener('click', () => {
                cerrarModal(modal);
            });
        });

        modal.addEventListener('click', evento => {
            if (evento.target === modal) {
                cerrarModal(modal);
            }
        });
    });

    document.addEventListener('keydown', evento => {
        if (evento.key === 'Escape') {
            modales.forEach(cerrarModal);
        }
    });

    async function iniciarEnsamble() {
        establecerBotones(false);
        bienvenida.textContent = 'Bienvenid@, cargando...';

        try {
            // Verificar sesión y obtener token CSRF
            await cargarHistorial();

            // Consultar órdenes una vez autenticado
            await cargarOrdenes();

            establecerBotones(true);

            console.log('Módulo Ensamble iniciado correctamente.');

        } catch (error) {
            console.error('Error al iniciar Ensamble:', error);

            if (error.message.startsWith('SESIÓN:')) {
                bienvenida.textContent =
                    'Bienvenid@, inicia sesión';
                ponerOpcion('Sesión requerida');
                aviso(
                    'No hay una sesión activa.\n' +
                    'Para probar localmente, abre primero ' +
                    'dev_login_ensamble.php.'
                );
            } else {
                // Un error SQL o del servidor no equivale
                // necesariamente a una sesión inexistente.
                if (!sesionActiva) {
                    bienvenida.textContent =
                        'No fue posible cargar el usuario';
                }

                ponerOpcion('No se pudieron cargar las órdenes');
                aviso(error.message);
            }
        }
    }

    iniciarEnsamble();
});
