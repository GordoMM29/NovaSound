document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('error')) {
        const tipoError = urlParams.get('error');
        
        if (tipoError === 'credenciales') {
            mostrarErrorLogin('Correo o contraseña incorrectos.');
        } else if (tipoError === 'campos') {
            mostrarErrorLogin('Por favor, completa todos los campos.');
        }
        
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function mostrarErrorLogin(mensaje) {
    alert(mensaje);

    const inputUsuario = document.getElementById('usuario');
    const inputPassword = document.getElementById('password');

    if (inputUsuario) inputUsuario.value = '';
    if (inputPassword) inputPassword.value = '';

    if (inputUsuario) inputUsuario.focus();
}