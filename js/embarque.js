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

    const cargarCajasCerradas = async () => {
        try {
            const res = await fetch('../php/embarque/obtener_cajas_cerradas.php');
            const cajas = await res.json();

            selectCaja.innerHTML = '<option value="">Seleccione caja</option>';
            cajas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.ID_CAJA;
                opt.textContent = `${c.ID_CAJA} (${c.CANTIDAD_UNIDADES} unidades)`;
                selectCaja.appendChild(opt);
            });
        } catch (err) {
            console.error("Error al cargar cajas cerradas:", err);
        }
    };

    cargarCajasCerradas();

    if (btnFinalizar) {
        btnFinalizar.addEventListener('click', async () => {
            const idCaja = selectCaja.value;
            const fechaSalida = inputFechaSalida.value;
            const destino = inputDestino.value.trim();

            if (!idCaja || !fechaSalida || !destino) {
                alert('Por favor, complete todos los campos (Caja, Fecha de Salida y Destino).');
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
                    // Mostrar modal de éxito
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