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
            return;
        }
    } catch (error) {
        console.error('Error al verificar la sesión:', error);
    }

    const selectCaja = document.getElementById('select-id-caja');
    const inputFechaSalida = document.getElementById('input-fecha-salida');
    const inputDestino = document.getElementById('input-destino');
    const btnFinalizar = document.getElementById('btn-finalizar-embarque');
    
    const modalLiberado = document.getElementById('modal-embarque-liberado');
    const btnCerrarModal = document.getElementById('cerrar-embarque-liberado');
    const btnVolverModal = document.getElementById('btn-volver-embarque-liberado');

    const tablaEmbarquesBody = document.getElementById('tabla-embarques-body');
    const inputBuscar = document.getElementById('input-buscar-embarque');

    let listaEmbarquesGlobal = [];

    const cargarCajasCerradas = async () => {
        try {
            const res = await fetch('../php/embarque/obtener_cajas_cerradas.php');
            const cajas = await res.json();

            if (selectCaja) {
                selectCaja.innerHTML = '<option value="">Seleccione caja</option>';
                cajas.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.ID_CAJA;
                    opt.textContent = `${c.ID_CAJA} (${c.CANTIDAD_UNIDADES} unidades)`;
                    selectCaja.appendChild(opt);
                });
            }
        } catch (err) {
            console.error("Error al cargar cajas cerradas:", err);
        }
    };

    const renderizarTabla = (embarques) => {
        if (!tablaEmbarquesBody) return;
        tablaEmbarquesBody.innerHTML = '';

        if (embarques.length === 0) {
            tablaEmbarquesBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 15px;">No hay entregas en curso.</td></tr>`;
            return;
        }

        embarques.forEach(e => {
            const fila = document.createElement('tr');
            const fechaSalida = e.FECHA_SALIDA ? e.FECHA_SALIDA.split(' ')[0] : '';
            const fechaPromesa = e.FECHA_PROMESA ? e.FECHA_PROMESA.split(' ')[0] : '';

            fila.innerHTML = `
                <td style="padding: 8px; text-align: center;">${e.ID}</td>
                <td style="padding: 8px; text-align: center;">${e.CLIENTE}</td>
                <td style="padding: 8px; text-align: center;">${fechaSalida}</td>
                <td style="padding: 8px; text-align: center;">${fechaPromesa}</td>
                <td style="padding: 8px; text-align: center; font-weight: bold;">${e.ESTATUS}</td>
            `;
            tablaEmbarquesBody.appendChild(fila);
        });
    };

    const cargarEmbarques = async () => {
        try {
            const res = await fetch('../php/embarque/obtener_embarques.php');
            listaEmbarquesGlobal = await res.json();
            renderizarTabla(listaEmbarquesGlobal);
        } catch (err) {
            console.error("Error al cargar embarques:", err);
        }
    };

    cargarCajasCerradas();
    cargarEmbarques();

    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => {
            const textoBusqueda = e.target.value.trim().toLowerCase();
            if (textoBusqueda === '') {
                renderizarTabla(listaEmbarquesGlobal);
            } else {
                const filtrados = listaEmbarquesGlobal.filter(item => 
                    String(item.ID).toLowerCase().includes(textoBusqueda)
                );
                renderizarTabla(filtrados);
            }
        });
    }

    if (btnFinalizar) {
        btnFinalizar.addEventListener('click', async () => {
            const idCaja = selectCaja ? selectCaja.value : '';
            const fechaSalida = inputFechaSalida ? inputFechaSalida.value : '';
            const destino = inputDestino ? inputDestino.value.trim() : '';

            if (!idCaja || !fechaSalida || !destino) {
                alert('Por favor, seleccione una caja, ingrese la fecha de salida y el destino.');
                return;
            }

            try {
                const res = await fetch('../php/embarque/registrar_embarque.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_caja: idCaja,
                        fecha_salida: fechaSalida,
                        destino: destino
                    })
                });

                const resultado = await res.json();

                if (resultado.success) {
                    if (modalLiberado) {
                        modalLiberado.style.setProperty('display', 'flex', 'important');
                        modalLiberado.classList.add('activo');
                    } else {
                        alert('¡Embarque registrado con éxito!');
                        location.reload();
                    }
                } else {
                    alert('Error al registrar el embarque: ' + (resultado.error || 'Desconocido'));
                }
            } catch (err) {
                console.error("Error en la petición:", err);
                alert('Ocurrió un error al procesar el embarque.');
            }
        });
    }

    const cerrarModal = () => {
        if (modalLiberado) {
            modalLiberado.style.display = 'none';
            modalLiberado.classList.remove('activo');
        }
        location.reload();
    };

    if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModal);
    if (btnVolverModal) btnVolverModal.addEventListener('click', cerrarModal);
});