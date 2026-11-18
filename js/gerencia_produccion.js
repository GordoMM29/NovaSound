document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('../php/login/verificar_sesion.php', { method: 'GET', credentials: 'include' });
        const data = await res.json();
        if (!data.autenticado) {
            window.location.href = '../vistas/login.html';
            return;
        }
    } catch (err) { console.error('Error de sesión:', err); }

    const selectTurno = document.getElementById('select-turno-reabrir');
    const btnReabrirModal = document.getElementById('btn-reabrir-turno');
    const modalReabrir = document.getElementById('modal-reabrir-turno');
    const btnConfirmarReabrir = document.getElementById('btn-confirmar-reabrir-turno');
    const btnCancelarReabrir = document.getElementById('btn-cancelar-reabrir-turno');
    const cerrarReabrirX = document.getElementById('cerrar-reabrir-turno');

    const cargarTurnos = async () => {
        try {
            const res = await fetch('../php/gerencia_de_produccion/obtener_turnos.php');
            const turnos = await res.json();
            if (selectTurno) {
                selectTurno.innerHTML = '<option value="">Seleccione turno</option>';
                turnos.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.ID_TURNO;
                    opt.textContent = `${t.NOMBRE} (${t.ID_TURNO})`;
                    selectTurno.appendChild(opt);
                });
            }
        } catch (err) { console.error('Error cargando turnos:', err); }
    };
    cargarTurnos();

    if (btnReabrirModal) {
        btnReabrirModal.addEventListener('click', () => {
            if (!selectTurno.value) {
                alert('Por favor, seleccione un turno primero.');
                return;
            }
            if (modalReabrir) {
                modalReabrir.style.setProperty('display', 'flex', 'important');
                modalReabrir.classList.add('activo');
            }
        });
    }

    const cerrarModalReabrir = () => {
        if (modalReabrir) {
            modalReabrir.style.display = 'none';
            modalReabrir.classList.remove('activo');
        }
    };

    if (btnCancelarReabrir) btnCancelarReabrir.addEventListener('click', cerrarModalReabrir);
    if (cerrarReabrirX) cerrarReabrirX.addEventListener('click', cerrarModalReabrir);

    if (btnConfirmarReabrir) {
        btnConfirmarReabrir.addEventListener('click', async () => {
            const idTurno = selectTurno.value;
            try {
                const res = await fetch('../php/gerencia_de_produccion/reabrir_turno.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_turno: idTurno })
                });
                const r = await res.json();
                if (r.success) {
                    alert('¡Turno reabierto con éxito!');
                    cerrarModalReabrir();
                    location.reload();
                } else {
                    alert('Error: ' + (r.error || 'Desconocido'));
                }
            } catch (err) { console.error('Error:', err); }
        });
    }

    const vincularReporte = (idBtn, urlReporte) => {
        const btn = document.getElementById(idBtn);
        if (btn) {
            btn.addEventListener('click', () => {
                window.open(`../php/gerencia_de_produccion/${urlReporte}`, '_blank');
            });
        }
    };

    vincularReporte('btn-consultar-linea', 'reporte_lineas.php');
    vincularReporte('btn-consultar-ordenes', 'reporte_ordenes_incompletas.php');
    vincularReporte('btn-consultar-cierres', 'reporte_cierres.php');
    vincularReporte('btn-historico-produccion', 'reporte_historico_produccion.php');
    vincularReporte('btn-historico-calidad', 'reporte_historico_calidad.php');
    vincularReporte('btn-historico-almacen', 'reporte_historico_almacen.php');
});

    const modalSolicitud = document.getElementById('modal-solicitud');
    const cerrarSolicitudX = document.getElementById('cerrar-solicitud');
    const btnVolverSolicitud = document.getElementById('btn-volver-solicitud');
    const tablaSolicitudBody = document.getElementById('tabla-solicitud-body');
    const btnAprobar = document.getElementById('btn-aprobar-solicitud');
    const btnRechazar = document.getElementById('btn-rechazar-solicitud');

    let solicitudActualId = null;

    document.querySelectorAll('.btn-solicitud').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const idSolicitud = e.target.getAttribute('data-id');
            solicitudActualId = idSolicitud;

            try {
                const res = await fetch(`../php/gerencia_de_produccion/obtener_solicitud.php?id=${idSolicitud}`);
                const data = await res.json();

                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }

                if (tablaSolicitudBody) {
                    tablaSolicitudBody.innerHTML = `
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">${data.OBJETO}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">${data.NOMBRE_USUARIO} (${data.ID_usuario})</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">${data.FECHA_SOLICITUD}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">${data.MOTIVO}</td>
                        </tr>
                    `;
                }

                if (modalSolicitud) {
                    modalSolicitud.style.setProperty('display', 'flex', 'important');
                    modalSolicitud.classList.add('activo');
                }
            } catch (err) {
                console.error('Error al cargar la solicitud:', err);
            }
        });
    });

    const cerrarModalSolicitud = () => {
        if (modalSolicitud) {
            modalSolicitud.style.display = 'none';
            modalSolicitud.classList.remove('activo');
        }
    };

    if (cerrarSolicitudX) cerrarSolicitudX.addEventListener('click', cerrarModalSolicitud);
    if (btnVolverSolicitud) btnVolverSolicitud.addEventListener('click', cerrarModalSolicitud);

    const cambiarEstatusSolicitud = async (estatus) => {
        if (!solicitudActualId) return;

        try {
            const res = await fetch('../php/gerencia_de_produccion/actualizar_estatus_solicitud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: solicitudActualId, estatus: estatus })
            });
            const r = await res.json();

            if (r.success) {
                alert(`¡Solicitud ${estatus.toLowerCase()} exitosamente!`);
                cerrarModalSolicitud();
                location.reload();
            } else {
                alert('Error al actualizar: ' + (r.error || 'Desconocido'));
            }
        } catch (err) {
            console.error('Error en la petición:', err);
        }
    };

    if (btnAprobar) {
        btnAprobar.addEventListener('click', () => cambiarEstatusSolicitud('Aprobado'));
    }

    if (btnRechazar) {
        btnRechazar.addEventListener('click', () => cambiarEstatusSolicitud('Rechazado'));
    }