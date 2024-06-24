document.addEventListener('DOMContentLoaded', function() {
    cargarAlmacenes();

    document.getElementById('btnAgregarAlmacen').addEventListener('click', mostrarFormularioAgregar);
});

function cargarAlmacenes() {
    fetch('almacen.php?action=listar')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tablaAlmacenes tbody');
            tbody.innerHTML = '';
            data.forEach(almacen => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${almacen.id_almacen}</td>
                    <td>${almacen.nombre}</td>
                    <td>${almacen.ubicacion}</td>
                    <td>
                        <button onclick="editarAlmacen(${almacen.id_almacen}, '${almacen.nombre}', '${almacen.ubicacion}')">Editar</button>
                        <button onclick="eliminarAlmacen(${almacen.id_almacen})">Eliminar</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

function mostrarFormularioAgregar() {
    const form = `
        <form id="formAlmacen">
            <input type="text" id="nombre" placeholder="Nombre" required>
            <input type="text" id="ubicacion" placeholder="Ubicación" required>
            <button type="submit">Guardar</button>
            <button type="button" onclick="cancelarEdicion()">Cancelar</button>
        </form>
    `;
    document.querySelector('.content').insertAdjacentHTML('afterbegin', form);
    document.getElementById('formAlmacen').addEventListener('submit', guardarAlmacen);
}

function guardarAlmacen(e) {
    e.preventDefault();
    const nombre = document.getElementById('nombre').value;
    const ubicacion = document.getElementById('ubicacion').value;
    const formData = new FormData();
    formData.append('action', 'agregar');
    formData.append('nombre', nombre);
    formData.append('ubicacion', ubicacion);

    fetch('almacen.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarAlmacenes();
            cancelarEdicion();
        } else {
            alert(data.error);
        }
    });
}

function editarAlmacen(id, nombre, ubicacion) {
    const tr = event.target.closest('tr');
    tr.innerHTML = `
        <td>${id}</td>
        <td><input type="text" id="nombre_${id}" value="${nombre}"></td>
        <td><input type="text" id="ubicacion_${id}" value="${ubicacion}"></td>
        <td>
            <button onclick="actualizarAlmacen(${id})">Guardar</button>
            <button onclick="cancelarEdicion()">Cancelar</button>
        </td>
    `;
}

function actualizarAlmacen(id) {
    const nombre = document.getElementById(`nombre_${id}`).value;
    const ubicacion = document.getElementById(`ubicacion_${id}`).value;
    const formData = new FormData();
    formData.append('action', 'actualizar');
    formData.append('id', id);
    formData.append('nombre', nombre);
    formData.append('ubicacion', ubicacion);

    fetch('almacen.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarAlmacenes();
        } else {
            alert(data.error);
        }
    });
}

function eliminarAlmacen(id) {
    if (confirm('¿Está seguro de que desea eliminar este almacén?')) {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', id);

        fetch('almacen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarAlmacenes();
            } else {
                alert(data.error);
            }
        });
    }
}

function cancelarEdicion() {
    cargarAlmacenes();
    const form = document.getElementById('formAlmacen');
    if (form) form.remove();
}