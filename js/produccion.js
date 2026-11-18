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
    console.log("DOM cargado correctamente."); 

    const btnAbrirReporte = document.getElementById('btn-abrir-reporte');
    const modalReporte = document.getElementById('modal-reporte-produccion');
    const btnCerrarReporte = document.getElementById('cerrar-reporte-produccion');
    const btnCancelarReporte = document.getElementById('btn-cancelar-reporte-produccion');
    const inputFechaHora = document.getElementById('fecha-hora-produccion');

    function obtenerFechaHoraActual() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const dia = String(ahora.getDate()).padStart(2, '0');
        const mes = String(ahora.getMonth() + 1).padStart(2, '0');
        const anio = ahora.getFullYear();

        return `${horas}:${minutos} - ${dia}/${mes}/${anio}`;
    }

    if (btnAbrirReporte && modalReporte) {
        btnAbrirReporte.addEventListener('click', (e) => {
            e.preventDefault();
            console.log("Botón de reporte presionado."); 
            
            modalReporte.style.display = 'flex';
            modalReporte.classList.add('activo');

            if (inputFechaHora) {
                inputFechaHora.value = obtenerFechaHoraActual();
            }
        });
    } else {
        console.warn("No se encontró el botón 'btn-abrir-reporte' o el modal.");
    }

    if (btnCerrarReporte && modalReporte) {
        btnCerrarReporte.addEventListener('click', () => {
            modalReporte.style.display = 'none';
            modalReporte.classList.remove('activo');
        });
    }

    if (btnCancelarReporte && modalReporte) {
        btnCancelarReporte.addEventListener('click', () => {
            modalReporte.style.display = 'none';
            modalReporte.classList.remove('activo');
        });
    }
});