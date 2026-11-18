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