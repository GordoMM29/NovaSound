//  Variables globales de la pagina 
let usuarioActual = "Usuario";

// Funciones para la nevagacion
function iniciarSesion() {
    const usuarioInput = document.getElementById('usuario').value.trim();
    const passwordInput = document.getElementById('password').value.trim();

    if (usuarioInput === '' || passwordInput === '') {
        alert('Por favor, ingrese usuario y contraseña.');
        return;
    }

    usuarioActual = usuarioInput;

    document.getElementById('vista-login').classList.remove('activa');
    document.getElementById('nav-bar').classList.remove('oculto');

    document.getElementById('nombre-produccion').textContent = usuarioActual;
    document.getElementById('nombre-ensamble').textContent = usuarioActual;
    document.getElementById('nombre-empaque').textContent = usuarioActual;
    document.getElementById('nombre-embarque').textContent = usuarioActual;
    document.getElementById('nombre-sistemas').textContent = usuarioActual;
    document.getElementById('nombre-gerencia').textContent = usuarioActual;

    cambiarVista('produccion');
}

function cerrarSesion() {
    document.getElementById('nav-bar').classList.add('oculto');
    const vistas = document.querySelectorAll('.vista');
    vistas.forEach(v => v.classList.remove('activa'));

    document.getElementById('vista-login').classList.add('activa');
    document.getElementById('usuario').value = '';
    document.getElementById('password').value = '';
}

function cambiarVista(nombreVista) {
    const vistas = document.querySelectorAll('.vista');
    vistas.forEach(v => v.classList.remove('activa'));

    const navButtons = document.querySelectorAll('.nav-bar button');
    navButtons.forEach(b => b.classList.remove('activo-nav'));

    switch (nombreVista) {
        case 'produccion':
            document.getElementById('vista-produccion').classList.add('activa');
            document.getElementById('nav-produccion').classList.add('activo-nav');
            break;
        case 'ensamble':
            document.getElementById('vista-ensamble').classList.add('activa');
            document.getElementById('nav-ensamble').classList.add('activo-nav');
            break;
        case 'empaque':
            document.getElementById('vista-empaque').classList.add('activa');
            document.getElementById('nav-empaque').classList.add('activo-nav');
            break;
        case 'embarque':
            document.getElementById('vista-embarque').classList.add('activa');
            document.getElementById('nav-embarque').classList.add('activo-nav');
            break;
        case 'sistemas':
            document.getElementById('vista-sistemas').classList.add('activa');
            document.getElementById('nav-sistemas').classList.add('activo-nav');
            break;
        case 'gerencia':
            document.getElementById('vista-gerencia').classList.add('activa');
            document.getElementById('nav-gerencia').classList.add('activo-nav');
            break;
        default:
            document.getElementById('vista-produccion').classList.add('activa');
            document.getElementById('nav-produccion').classList.add('activo-nav');
    }
}

// Funciones especificas de la pagina
function crearCaja() {
    const tabla = document.getElementById('tabla-cajas');
    const nuevaFila = document.createElement('tr');
    const idCaja = 'CJ-' + String(Math.floor(Math.random() * 10000)).padStart(4, '0');
    nuevaFila.innerHTML = `
        <td>${idCaja}</td>
        <td>Cliente Ejemplo</td>
        <td>Modelo X</td>
        <td>1</td>
        <td>Abierta</td>
        <td>${new Date().toISOString().split('T')[0]}</td>
        <td><button class="btn btn-azul btn-opciones-caja">Opciones</button></td>
    `;
    tabla.appendChild(nuevaFila);
    alert('Caja creada exitosamente con ID: ' + idCaja);

    // Actualizar select de embarque
    const select = document.getElementById('select-id-caja');
    const option = document.createElement('option');
    option.value = idCaja;
    option.textContent = idCaja;
    select.appendChild(option);

    // Asignar evento al boton de opciones
    nuevaFila.querySelector('.btn-opciones-caja').addEventListener('click', function() {
        alert('Opciones para caja ' + idCaja);
    });
}

// Asignar algun evento al cargar
document.addEventListener('DOMContentLoaded', function() {
    // Ocultar nav y mostrar login al inicio
    document.getElementById('nav-bar').classList.add('oculto');
    document.querySelectorAll('.vista').forEach(v => v.classList.remove('activa'));
    document.getElementById('vista-login').classList.add('activa');

    // Login 
    document.getElementById('btn-login').addEventListener('click', iniciarSesion);

    //  Navegacion
    document.getElementById('nav-produccion').addEventListener('click', () => cambiarVista('produccion'));
    document.getElementById('nav-ensamble').addEventListener('click', () => cambiarVista('ensamble'));
    document.getElementById('nav-empaque').addEventListener('click', () => cambiarVista('empaque'));
    document.getElementById('nav-embarque').addEventListener('click', () => cambiarVista('embarque'));
    document.getElementById('nav-sistemas').addEventListener('click', () => cambiarVista('sistemas'));
    document.getElementById('nav-gerencia').addEventListener('click', () => cambiarVista('gerencia'));
    document.getElementById('btn-cerrar-sesion').addEventListener('click', cerrarSesion);

    // Produccion
    document.getElementById('btn-reportar-incidente').addEventListener('click', () => {
        alert('Ventana emergente: Reportar Incidente');
    });

    // Ensamble 
    document.getElementById('btn-enviar-pruebas').addEventListener('click', () => {
        alert('Enviando a pruebas...');
    });
    document.getElementById('btn-registrar-unidades').addEventListener('click', () => {
        alert('Registrando unidades...');
    });
    document.getElementById('btn-reportar-incidente-ensamble').addEventListener('click', () => {
        alert('Reportar Incidente en Ensamble');
    });

    // -Empaque 
    document.getElementById('btn-crear-caja').addEventListener('click', crearCaja);
    document.getElementById('btn-buscar-caja').addEventListener('click', () => {
        alert('Buscando caja...');
    });
    document.getElementById('btn-reportar-incidente-empaque').addEventListener('click', () => {
        alert('Reportar Incidente en Empaque');
    });

    // Embarque 
    document.getElementById('btn-finalizar-embarque').addEventListener('click', () => {
        alert('Embarque finalizado. El embarque entrará en tránsito.');
    });

    // --- Sistemas ---
    document.getElementById('btn-buscar-id').addEventListener('click', () => {
        alert('Buscando por ID...');
    });
    document.getElementById('btn-descargar-datos').addEventListener('click', () => {
        alert('Descargando datos...');
    });
    document.getElementById('btn-guardar-cambios').addEventListener('click', () => {
        alert('Cambios guardados y autorizados');
    });

    // --- Gerencia ---
    document.getElementById('btn-descargar-reporte').addEventListener('click', () => {
        alert('Descargando reporte...');
    });
    document.getElementById('btn-info-general').addEventListener('click', () => {
        alert('Informacion General');
    });
});