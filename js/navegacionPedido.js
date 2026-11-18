'use strict';
// Agrega navegación al botón original sin cambiar el catálogo ni las ventanas de productos.
document.addEventListener('DOMContentLoaded', () => {
    const boton = document.querySelector('#btn-realizar-pedido')
        || [...document.querySelectorAll('.acciones-inicio button')]
            .find(b => b.textContent.trim().toLowerCase() === 'realizar pedido');
    if (!boton) {
        console.warn('No se encontró el botón Realizar pedido de inicio.html');
        return;
    }
    boton.addEventListener('click', () => {
        window.location.href = 'pedido.html';
    });
});
