document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('exito') === 'incidente_reportado') {
        alert("¡Incidente reportado exitosamente!");
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (urlParams.has('error')) {
        const tipoError = urlParams.get('error');
        let mensajeError = "Hubo un error al procesar el incidente.";
        
        if (tipoError === 'datos_incidente_invalidos') {
            mensajeError = "Error: Faltan datos requeridos para registrar el incidente.";
        } else if (tipoError === 'bd_incidente') {
            mensajeError = "Error en la base de datos al intentar guardar el incidente.";
        }
        
        alert("Error: " + mensajeError);
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    const btnAbrirReporte = document.getElementById('btn-abrir-reporte');
    const modalReporte = document.getElementById('modal-reporte-produccion');
    const btnCerrarReporte = document.getElementById('cerrar-reporte-produccion');
    const btnCancelarReporte = document.getElementById('btn-cancelar-reporte-produccion');
    const formReporte = document.getElementById('form-reporte-produccion');
    const inputCantidadAgregar = document.getElementById('input-cantidad-agregar');

    const cerrarModalReporte = () => {
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

    if (btnCerrarReporte) btnCerrarReporte.addEventListener('click', cerrarModalReporte);
    if (btnCancelarReporte) btnCancelarReporte.addEventListener('click', cerrarModalReporte);


    const btnAbrirIncidente = document.getElementById('btn-abrir-incidente');
    const modalIncidente = document.getElementById('modal-incidente-produccion');
    const btnCerrarIncidente = document.getElementById('cerrar-incidente-produccion');
    const btnCancelarIncidente = document.getElementById('btn-cancelar-incidente-produccion');

    const cerrarModalIncidente = () => {
        if (modalIncidente) {
            modalIncidente.style.display = 'none';
            modalIncidente.classList.remove('activo');
        }
    };

    if (btnAbrirIncidente && modalIncidente) {
        btnAbrirIncidente.addEventListener('click', (e) => {
            e.preventDefault();
            modalIncidente.style.setProperty('display', 'flex', 'important');
            modalIncidente.classList.add('activo');
        });
    }

    if (btnCerrarIncidente) btnCerrarIncidente.addEventListener('click', cerrarModalIncidente);
    if (btnCancelarIncidente) btnCancelarIncidente.addEventListener('click', cerrarModalIncidente);


    window.addEventListener('click', (e) => {
        if (e.target === modalReporte) cerrarModalReporte();
        if (e.target === modalIncidente) cerrarModalIncidente();
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