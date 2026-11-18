document.addEventListener('DOMContentLoaded', () => {
    const tablaBody = document.getElementById('tabla-calidad-body');
    const modalDetalles = document.getElementById('modal-detalles-orden');
    const btnCerrarX = document.getElementById('cerrar-detalles-orden');
    const btnCerrarModal = document.getElementById('btn-cerrar-detalles');
    
    const lblOrden = document.getElementById('modal-lbl-orden');
    const lblModelo = document.getElementById('modal-lbl-modelo');
    const tablaDetallesBody = document.getElementById('tabla-detalles-body');

    const cerrarModalDetalles = () => {
        if (modalDetalles) {
            modalDetalles.style.display = 'none';
            modalDetalles.classList.remove('activo');
        }
    };

    if (btnCerrarX) btnCerrarX.addEventListener('click', cerrarModalDetalles);
    if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModalDetalles);

    window.addEventListener('click', (e) => {
        if (e.target === modalDetalles) cerrarModalDetalles();
        if (e.target === modalRegistro) cerrarModalRegistro();
    });

    const cargarUnidadesCalidad = async () => {
        try {
            const response = await fetch('../php/calidad/obtener_unidades_calidad.php');
            const unidades = await response.json();

            if (!tablaBody) return;
            tablaBody.innerHTML = '';

            if (unidades.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="3" style="text-align: center; padding: 15px;">No hay unidades aprobadas.</td></tr>`;
                return;
            }

            unidades.forEach(item => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td style="border: 2px solid #333; padding: 10px; text-align: center;">
                        <a href="#" class="link-orden" data-orden="${item.ORDEN}" style="color: #007bff; text-decoration: underline; font-weight: bold;">${item.ORDEN}</a>
                    </td>
                    <td style="border: 2px solid #333; padding: 10px; text-align: center;">${item.MODELO}</td>
                    <td style="border: 2px solid #333; padding: 10px; text-align: center;">${item.ESTATUS}</td>
                `;
                tablaBody.appendChild(fila);
            });

            document.querySelectorAll('.link-orden').forEach(link => {
                link.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const idOrden = e.target.getAttribute('data-orden');
                    
                    lblOrden.textContent = idOrden;
                    modalDetalles.style.setProperty('display', 'flex', 'important');
                    modalDetalles.classList.add('activo');
                    tablaDetallesBody.innerHTML = `<tr><td colspan="6" style="padding: 15px;">Cargando detalles...</td></tr>`;

                    try {
                        const resDetalle = await fetch(`../php/calidad/obtener_detalles_orden.php?id_orden=${idOrden}`);
                        const detalles = await resDetalle.json();

                        tablaDetallesBody.innerHTML = '';

                        if (detalles.length === 0) {
                            tablaDetallesBody.innerHTML = `<tr><td colspan="6" style="padding: 15px;">No hay registros de inspección para esta orden.</td></tr>`;
                            lblModelo.textContent = 'N/D';
                            return;
                        }

                        lblModelo.textContent = detalles[0].MODELO;

                        detalles.forEach(d => {
                            const filaDetalle = document.createElement('tr');
                            filaDetalle.innerHTML = `
                                <td style="border: 1px solid #333; padding: 6px;">${d.NUMERO_SERIE}</td>
                                <td style="border: 1px solid #333; padding: 6px;">${d.ESTATUS_PRUEBAS}</td>
                                <td style="border: 1px solid #333; padding: 6px;">${d.ESTATUS_APARIENCIA}</td>
                                <td style="border: 1px solid #333; padding: 6px;">${d.ESTATUS_ETIQUETA}</td>
                                <td style="border: 1px solid #333; padding: 6px;">${d.ESTATUS_ACCESORIOS}</td>
                                <td style="border: 1px solid #333; padding: 6px; font-weight: bold;">${d.ESTATUS_FINAL}</td>
                            `;
                            tablaDetallesBody.appendChild(filaDetalle);
                        });

                    } catch (err) {
                        console.error("Error al cargar detalles:", err);
                        tablaDetallesBody.innerHTML = `<tr><td colspan="6" style="padding: 15px; color: red;">Error al obtener los datos.</td></tr>`;
                    }
                });
            });

        } catch (error) {
            console.error('Error al obtener los datos de calidad:', error);
        }
    };

    cargarUnidadesCalidad();


    const modalRegistro = document.getElementById('modal-registro-unidades');
    const btnCerrarRegistroX = document.getElementById('cerrar-registro-unidades');
    const btnCancelarRegistro = document.getElementById('btn-cancelar-registro');
    
    const selectOrden = document.getElementById('select-orden-modal');
    const selectSerie = document.getElementById('select-serie-modal');
    const inputNuevoEstatus = document.getElementById('input-nuevo-estatus');
    const modalTitulo = document.getElementById('modal-titulo-accion');
    const btnEjecutar = document.getElementById('btn-ejecutar-accion');

    const cerrarModalRegistro = () => {
        if (modalRegistro) {
            modalRegistro.style.display = 'none';
            modalRegistro.classList.remove('activo');
        }
    };

    if (btnCerrarRegistroX) btnCerrarRegistroX.addEventListener('click', cerrarModalRegistro);
    if (btnCancelarRegistro) btnCancelarRegistro.addEventListener('click', cerrarModalRegistro);

    const accionesConfig = {
        'btn-abrir-liberar': { texto: 'Liberar', color: '#28a745', estatusBD: 'Liberada' },
        'btn-abrir-scrap': { texto: 'Scrap', color: '#343a40', estatusBD: 'Scrap' },
        'btn-abrir-retener': { texto: 'Retener', color: '#ffc107', estatusBD: 'Retenida' },
        'btn-abrir-retrabajar': { texto: 'Retrabajar', color: '#17a2b8', estatusBD: 'Retrabajar' }
    };

    Object.keys(accionesConfig).forEach(idBtn => {
        const boton = document.getElementById(idBtn);
        if (boton) {
            boton.addEventListener('click', async () => {
                const config = accionesConfig[idBtn];
                modalTitulo.textContent = `Registro de unidades - ${config.texto}`;
                inputNuevoEstatus.value = config.estatusBD;
                btnEjecutar.textContent = config.texto;
                btnEjecutar.style.backgroundColor = config.color;

                modalRegistro.style.setProperty('display', 'flex', 'important');
                modalRegistro.classList.add('activo');

                try {
                    const res = await fetch('../php/calidad/obtener_ordenes_aprobadas.php');
                    const ordenes = await res.json();

                    selectOrden.innerHTML = '<option value="">Seleccione una orden...</option>';
                    ordenes.forEach(o => {
                        const opt = document.createElement('option');
                        opt.value = o.ID_orden;
                        opt.textContent = o.ID_orden;
                        selectOrden.appendChild(opt);
                    });
                    selectSerie.innerHTML = '<option value="">Primero seleccione una orden...</option>';
                } catch (err) {
                    console.error("Error al cargar órdenes aprobadas:", err);
                }
            });
        }
    });

    if (selectOrden) {
        selectOrden.addEventListener('change', async () => {
            const idOrden = selectOrden.value;
            selectSerie.innerHTML = '<option value="">Cargando series...</option>';

            if (!idOrden) {
                selectSerie.innerHTML = '<option value="">Primero seleccione una orden...</option>';
                return;
            }

            try {
                const res = await fetch(`../php/calidad/obtener_series_por_orden.php?id_orden=${idOrden}`);
                const series = await res.json();

                selectSerie.innerHTML = '<option value="">Seleccione número de serie...</option>';
                series.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.NUMERO_SERIE;
                    opt.textContent = s.NUMERO_SERIE;
                    selectSerie.appendChild(opt);
                });
            } catch (err) {
                console.error("Error al cargar series:", err);
                selectSerie.innerHTML = '<option value="">Error al cargar series</option>';
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('exito') === 'actualizado') {
        alert("¡Estatus de la unidad actualizado exitosamente!");
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
