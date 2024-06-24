document.addEventListener('DOMContentLoaded', function() {
    cargarProveedores();

    document.getElementById('btnAgregarProveedor').addEventListener('click', mostrarFormularioAgregar);
});

function cargarProveedores() {
    fetch('proveedores.php?action=listar')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tablaProveedores tbody');
            tbody.innerHTML = '';
            data.forEach(proveedor => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${proveedor.id_proveedor}</td>
                    <td>${proveedor.entidad}</td>
                    <td>${proveedor.representante}</td>
                    <td>${proveedor.contacto}</td>
                    <td>${proveedor.email}</td>
                    <td>${proveedor.direccion}</td>
                    <td>${proveedor.fecha}</td>
                    <td>
                        <button onclick="editarProveedor(${proveedor.id_proveedor})">Editar</button>
                        <button onclick="eliminarProveedor(${proveedor.id_proveedor})">Eliminar</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

function mostrarFormularioAgregar() {
    const form = `
        <form id="formProveedor">
            <input type="text" id="entidad" placeholder="Entidad" required>
            <input type="text" id="representante" placeholder="Representante" required>
            <input type="text" id="contacto" placeholder="Contacto" required>
            <input type="email" id="email" placeholder="Email">
            <input type="text" id="direccion" placeholder="Dirección">
            <input type="date" id="fecha" required>
            <textarea id="comentario" placeholder="Comentario"></textarea>
            <button type="submit">Guardar</button>
            <button type="button" onclick="cancelarEdicion()">Cancelar</button>
        </form>
    `;
    document.querySelector('.content').insertAdjacentHTML('afterbegin', form);
    document.getElementById('formProveedor').addEventListener('submit', guardarProveedor);
}

function guardarProveedor(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'agregar');

    fetch('proveedores.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarProveedores();
            cancelarEdicion();
        } else {
            alert(data.error);
        }
    });
}

function editarProveedor(id) {
    // Aquí deberías cargar los datos del proveedor y mostrar un formulario de edición
    // Similar a mostrarFormularioAgregar, pero con los datos precargados
}

function actualizarProveedor(id) {
    // Similar a guardarProveedor, pero con la acción 'actualizar'
}

function eliminarProveedor(id) {
    if (confirm('¿Está seguro de que desea eliminar este proveedor?')) {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', id);

        fetch('proveedores.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarProveedores();
            } else {
                alert(data.error);
            }
        });
    }
}

function cancelarEdicion() {
    cargarProveedores();
    const form = document.getElementById('formProveedor');
    if (form) form.remove();
}