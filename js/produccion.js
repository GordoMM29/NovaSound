document.addEventListener('DOMContentLoaded', () => {
    const btnAbrirReporte = document.getElementById('btn-abrir-reporte');
    const modalReporte = document.getElementById('modal-reporte-produccion');
    const btnCerrarReporte = document.getElementById('cerrar-reporte-produccion');
    const btnCancelarReporte = document.getElementById('btn-cancelar-reporte-produccion');
    const formReporte = document.getElementById('form-reporte-produccion');
    const inputCantidadAgregar = document.getElementById('input-cantidad-agregar');

    const cerrarModal = () => {
        if (modalReporte) {
            modalReporte.style.display = 'none';
            modalReporte.classList.remove('activo');
        }
    };

    if (btnAbrirReporte && modalReporte) {
        btnAbrirReporte.addEventListener('click', (e) => {
            e.preventDefault();
            modalReporte.style.setProperty('display', 'flex', 'important');
            modalReporte.classList.add('activo');
        });
    }

    if (btnCerrarReporte) btnCerrarReporte.addEventListener('click', cerrarModal);
    if (btnCancelarReporte) btnCancelarReporte.addEventListener('click', cerrarModal);

    window.addEventListener('click', (e) => {
        if (e.target === modalReporte) cerrarModal();
    });

    if (formReporte && inputCantidadAgregar) {
        formReporte.addEventListener('submit', (e) => {
            const requerida = parseInt(inputCantidadAgregar.getAttribute('data-requerida')) || 0;
            const producidaActual = parseInt(inputCantidadAgregar.getAttribute('data-producida-actual')) || 0;
            const cantidadNueva = parseInt(inputCantidadAgregar.value) || 0;

            const pendiente = requerida - producidaActual;

            if (cantidadNueva <= 0) {
                e.preventDefault();
                alert("Por favor, ingresa una cantidad mayor a 0.");
                inputCantidadAgregar.focus();
                return;
            }

            if (cantidadNueva > pendiente) {
                e.preventDefault(); 
                alert('Error: No puedes reportar ' + cantidadNueva + ' unidades. Lo pendiente por producir es de solo ' + pendiente + ' unidades.');
                inputCantidadAgregar.focus();
            }
        });
    }
});