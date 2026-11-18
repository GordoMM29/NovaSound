
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../php/login/verificar_sesion.php', { method: 'GET', credentials: 'include' });
        const data = await response.json();
        if (!data.autenticado) {
            window.location.href = '../vistas/login.html';
            return;
        }
    } catch (error) { console.error('Error de sesión:', error); }

    const tablaBody = document.getElementById('tabla-sistemas-body');
    const inputBuscar = document.getElementById('input-buscar-sistema');
    let listaUsuariosGlobal = [];

    const renderizarTabla = (usuarios) => {
        if (!tablaBody) return;
        tablaBody.innerHTML = '';
        if (usuarios.length === 0) {
            tablaBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 15px;">No se encontraron usuarios.</td></tr>`;
            return;
        }
        usuarios.forEach(u => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td style="padding: 8px; text-align: center;">${u.ID_USUARIO}</td>
                <td style="padding: 8px; text-align: center;">${u.NOMBRE}</td>
                <td style="padding: 8px; text-align: center;">${u.ROL}</td>
                <td style="padding: 8px; text-align: center;">${u.TURNO || 'N/D'}</td>
                <td style="padding: 8px; text-align: center;">${u.AREA}</td>
            `;
            tablaBody.appendChild(fila);
        });
    };

    const cargarUsuarios = async () => {
        try {
            const res = await fetch('../php/sistemas/obtener_usuarios.php');
            listaUsuariosGlobal = await res.json();
            renderizarTabla(listaUsuariosGlobal);
        } catch (err) { console.error("Error al cargar usuarios:", err); }
    };
    cargarUsuarios();

    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => {
            const txt = e.target.value.trim().toLowerCase();
            if (txt === '') renderizarTabla(listaUsuariosGlobal);
            else renderizarTabla(listaUsuariosGlobal.filter(i => String(i.ID_USUARIO).toLowerCase().includes(txt)));
        });
    }

    const abrirModal = (id) => {
        const m = document.getElementById(id);
        if (m) { m.style.setProperty('display', 'flex', 'important'); m.classList.add('activo'); }
    };
    const cerrarModal = (id) => {
        const m = document.getElementById(id);
        if (m) { m.style.display = 'none'; m.classList.remove('activo'); }
    };

    const btnActualizarAbrir = document.querySelector('.btn-actualizar');
    const btnEliminarAbrir = document.querySelector('.btn-eliminar');
    const btnAgregarAbrir = document.querySelectorAll('.btn-agg')[0];
    const btnDescargarPDF = document.querySelectorAll('.btn-agg')[1];

    if (btnActualizarAbrir) btnActualizarAbrir.addEventListener('click', () => abrirModal('modal-actualizar-usuario'));
    if (btnEliminarAbrir) btnEliminarAbrir.addEventListener('click', () => abrirModal('modal-eliminar-usuario'));
    if (btnAgregarAbrir) btnAgregarAbrir.addEventListener('click', () => abrirModal('modal-agregar-usuario'));
    if (btnDescargarPDF) btnDescargarPDF.addEventListener('click', () => {
        window.open('../php/sistemas/descargar_reporte_usuarios.php', '_blank');
    });

    ['actualizar', 'eliminar', 'agregar'].forEach(tipo => {
        const cerrarX = document.getElementById(`cerrar-${tipo}-usuario`);
        const btnCancelar = document.getElementById(`btn-cancelar-${tipo}-usuario`);
        if (cerrarX) cerrarX.addEventListener('click', () => cerrarModal(`modal-${tipo}-usuario`));
        if (btnCancelar) btnCancelar.addEventListener('click', () => cerrarModal(`modal-${tipo}-usuario`));
    });

    const inputActId = document.querySelector('#modal-actualizar-usuario input[placeholder="Ej. 5"]');
    const btnActBuscar = inputActId ? inputActId.nextElementSibling : null;
    const inputsAct = document.querySelectorAll('#modal-actualizar-usuario input');

    if (btnActBuscar) {
        btnActBuscar.addEventListener('click', async () => {
            const id = inputActId.value.trim();
            if (!id) return alert('Ingrese un ID');
            const res = await fetch(`../php/sistemas/buscar_usuario_por_id.php?id=${id}`);
            const data = await res.json();
            if (data.error) { alert(data.error); return; }
            inputsAct[1].value = data.NOMBRE;
            inputsAct[2].value = data.APELLIDOS;
            inputsAct[3].value = data.AREA;
            inputsAct[4].value = data.ID_linea || '';
            inputsAct[5].value = data.ID_turno || '';
            inputsAct[6].value = data.ID_rol || '';
        });
    }

    const btnGuardarAct = document.getElementById('btn-guardar-actualizar-usuario');
    if (btnGuardarAct) {
        btnGuardarAct.addEventListener('click', async () => {
            const body = {
                id: inputActId.value.trim(),
                nombre: inputsAct[1].value,
                apellidos: inputsAct[2].value,
                area: inputsAct[3].value,
                id_linea: inputsAct[4].value,
                id_turno: inputsAct[5].value,
                id_rol: inputsAct[6].value
            };
            const res = await fetch('../php/sistemas/actualizar_usuario.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)
            });
            const r = await res.json();
            if (r.success) { alert('Usuario actualizado con éxito'); location.reload(); }
            else alert('Error: ' + r.error);
        });
    }

    const inputDelId = document.querySelector('#modal-eliminar-usuario input[placeholder="Ej. 8"]');
    const btnDelBuscar = inputDelId ? inputDelId.nextElementSibling : null;
    const inputsDel = document.querySelectorAll('#modal-eliminar-usuario input');

    if (btnDelBuscar) {
        btnDelBuscar.addEventListener('click', async () => {
            const id = inputDelId.value.trim();
            if (!id) return alert('Ingrese un ID');
            const res = await fetch(`../php/sistemas/buscar_usuario_por_id.php?id=${id}`);
            const data = await res.json();
            if (data.error) { alert(data.error); return; }
            inputsDel[1].value = data.NOMBRE;
            inputsDel[2].value = data.APELLIDOS;
            inputsDel[3].value = data.AREA;
            inputsDel[4].value = data.ID_linea || '';
            inputsDel[5].value = data.FECHA_INGRESO || '';
            inputsDel[6].value = data.ID_rol || '';
        });
    }

    const btnConfDel = document.getElementById('btn-confirmar-eliminar-usuario');
    if (btnConfDel) {
        btnConfDel.addEventListener('click', async () => {
            const id = inputDelId.value.trim();
            if (!id) return alert('ID inválido');
            if (!confirm('¿Estás seguro de eliminar este usuario?')) return;
            const res = await fetch('../php/sistemas/eliminar_usuario.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id })
            });
            const r = await res.json();
            if (r.success) { alert('Usuario eliminado'); location.reload(); }
            else alert('Error: ' + r.error);
        });
    }

    const btnConfAgg = document.getElementById('btn-confirmar-agregar-usuario');
    const inputsAgg = document.querySelectorAll('#modal-agregar-usuario input');
    if (btnConfAgg) {
        btnConfAgg.addEventListener('click', async () => {
            const body = {
                nombre: inputsAgg[0].value,
                apellidos: inputsAgg[1].value,
                area: inputsAgg[2].value,
                id_linea: inputsAgg[3].value,
                id_turno: inputsAgg[4].value,
                id_rol: inputsAgg[5].value,
                fecha_ingreso: inputsAgg[6].value
            };
            const res = await fetch('../php/sistemas/agregar_usuario.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)
            });
            const r = await res.json();
            if (r.success) { alert('Usuario insertado con éxito'); location.reload(); }
            else alert('Error: ' + r.error);
        });
    }
});