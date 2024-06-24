document.addEventListener('DOMContentLoaded', function() {
    cargarMateriales();

    document.getElementById('btnAgregarMaterial').addEventListener('click', mostrarFormularioAgregar);
});

function cargarMateriales() {
    fetch('materiales.php?action=listar')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tablaMateriales tbody');
            tbody.innerHTML = '';
            data.forEach(material => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${material.id_material}</td>
                    <td>${material.nombre}</td>
                    <td>${material.num_adquiridos}</td>
                    <td>${material.costo ? '$' + material.costo : 'N/A'}</td>
                    <td>${material.fecha_adquisicion}</td>
                    <td>${material.proveedor}</td>
                    <td>
                        <button onclick="editarMaterial(${material.id_material})">Editar</button>
                        <button onclick="eliminarMaterial(${material.id_material})">Eliminar</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

function mostrarFormularioAgregar() {
    const form = `
        <form id="formMaterial">
            <input type="text" id="nombre" placeholder="Nombre" required>
            <input type="number" id="num_adquiridos" placeholder="Cantidad Adquirida" required>
            <input type="number" step="0.01" id="costo" placeholder="Costo">
            <input type="date" id="fecha_adquisicion" required>
            <select id="id_proveedor" required>
                <option value="">Seleccione un proveedor</option>
                <!-- Opciones de proveedores se cargarán dinámicamente -->
            </select>
            <button type="submit">Guardar</button>
            <button type="button" onclick="cancelarEdicion()">Cancelar</button>
        </form>
    `;
    document.querySelector('.content').insertAdjacentHTML('afterbegin', form);
    document.getElementById('formMaterial').addEventListener('submit', guardarMaterial);
    cargarProveedores();
}

function cargarProveedores() {
    fetch('proveedores.php?action=listar')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('id_proveedor');
            data.forEach(proveedor => {
                const option = document.createElement('option');
                option.value = proveedor.id_proveedor;
                option.textContent = proveedor.entidad;
                select.appendChild(option);
            });
        });
}

function guardarMaterial(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'agregar');

    fetch('materiales.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarMateriales();
            cancelarEdicion();
        } else {
            alert(data.error);
        }
    });
}

function editarMaterial(id) {
    fetch(`materiales.php?action=listar&id=${id}`)
        .then(response => response.json())
        .then(data => {
            const material = data[0];
            const form = `
                <form id="formMaterial">
                    <input type="hidden" id="id_material" value="${material.id_material}">
                    <input type="text" id="nombre" value="${material.nombre}" required>
                    <input type="number" id="num_adquiridos" value="${material.num_adquiridos}" required>
                    <input type="number" step="0.01" id="costo" value="${material.costo || ''}">
                    <input type="date" id="fecha_adquisicion" value="${material.fecha_adquisicion}" required>
                    <select id="id_proveedor" required>
                        <option value="">Seleccione un proveedor</option>
                        <!-- Opciones de proveedores se cargarán dinámicamente -->
                    </select>
                    <button type="submit">Actualizar</button>
                    <button type="button" onclick="cancelarEdicion()">Cancelar</button>
                </form>
            `;
            document.querySelector('.content').insertAdjacentHTML('afterbegin', form);
            document.getElementById('formMaterial').addEventListener('submit', actualizarMaterial);
            cargarProveedores().then(() => {
                document.getElementById('id_proveedor').value = material.id_proveedor;
            });
        });
}

function actualizarMaterial(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'actualizar');

    fetch('materiales.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarMateriales();
            cancelarEdicion();
        } else {
            alert(data.error);
        }
    });
}

function eliminarMaterial(id) {
    if (confirm('¿Está seguro de que desea eliminar este material?')) {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', id);

        fetch('materiales.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarMateriales();
            } else {
                alert(data.error);
            }
        });
    }
}

function cancelarEdicion() {
    cargarMateriales();
    const form = document.getElementById('formMaterial');
    if (form) form.remove();
}