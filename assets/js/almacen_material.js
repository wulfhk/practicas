document.addEventListener('DOMContentLoaded', function() {
    cargarAlmacenMaterial();
    cargarSelectMateriales();
    cargarSelectAlmacenes();

    document.getElementById('btnAgregarMaterial').addEventListener('click', mostrarFormularioAgregar);
});

function guardarAlmacenMaterial(e) {
    e.preventDefault();
    const id_material = document.getElementById('id_material').value;
    const id_almacen = document.getElementById('id_almacen').value;
    const stock = document.getElementById('stock').value;
    const formData = new FormData();
    formData.append('action', 'agregar');
    formData.append('id_material', id_material);
    formData.append('id_almacen', id_almacen);
    formData.append('stock', stock);

    fetch('almacen_material.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarAlmacenMaterial();
            actualizarStockMaterial(id_material);
            cancelarEdicion();
        } else {
            alert(data.error);
        }
    });
}


function cargarAlmacenMaterial() {
    fetch('almacen_material.php?action=listar')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tablaAlmacenMaterial tbody');
            tbody.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.id_material}</td>
                    <td>${item.nombre_material}</td>
                    <td>${item.nombre_almacen}</td>
                    <td>${item.stock}</td>
                    <td>
                        <button onclick="editarAlmacenMaterial(${item.id_material}, ${item.id_almacen}, ${item.stock})">Editar</button>
                        <button onclick="eliminarAlmacenMaterial(${item.id_material}, ${item.id_almacen})">Eliminar</button>
                    </td>`;
                tbody.appendChild(tr);
            });
        });
}

function cargarSelectMateriales() {
    fetch('almacen_material.php?action=listar_materiales')
        .then(response => response.json())
        .then(data => {
            const selectMaterial = document.getElementById('id_material');
            selectMaterial.innerHTML = '<option value="">Selecciona un material</option>';
            data.forEach(material => {
                const option = document.createElement('option');
                option.value = material.id_material;
                option.textContent = `${material.nombre} (Stock restante: ${material.stock_disponible})`;
                option.dataset.stock = material.stock_disponible;
                selectMaterial.appendChild(option);
            });
            selectMaterial.addEventListener('change', actualizarStockDisponible);
        });
}

function cargarSelectAlmacenes() {
    fetch('almacen_material.php?action=listar_almacenes')
        .then(response => response.json())
        .then(data => {
            const selectAlmacen = document.getElementById('id_almacen');
            selectAlmacen.innerHTML = '<option value="">Selecciona un almacén</option>';
            data.forEach(almacen => {
                const option = document.createElement('option');
                option.value = almacen.id_almacen;
                option.textContent = almacen.nombre;
                selectAlmacen.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

function actualizarStockDisponible() {
    const selectMaterial = document.getElementById('id_material');
    const selectedOption = selectMaterial.options[selectMaterial.selectedIndex];
    const stockElement = document.getElementById('stock_disponible');
    
    if (selectedOption && selectedOption.value) {
        const stockDisponible = selectedOption.dataset.stock;
        stockElement.textContent = `Stock restante: ${stockDisponible}`;
        stockElement.style.color = parseInt(stockDisponible) === 0 ? 'red' : 'black';
    } else {
        stockElement.textContent = '';
    }
}
// ... (el resto del código permanece igual)

function actualizarStockDisponible() {
    const selectMaterial = document.getElementById('id_material');
    const selectedOption = selectMaterial.options[selectMaterial.selectedIndex];
    const stockElement = document.getElementById('stock_disponible');
    
    if (selectedOption && selectedOption.value) {
        const stockDisponible = selectedOption.dataset.stock;
        stockElement.textContent = `Stock restante: ${stockDisponible}`;
        stockElement.style.color = parseInt(stockDisponible) === 0 ? 'red' : 'black';
    } else {
        stockElement.textContent = '';
    }
}

// Asegúrate de llamar a esta función después de cargar los materiales
document.getElementById('id_material').addEventListener('change', actualizarStockDisponible);

function mostrarFormularioAgregar() {
    const form = `
        <form id="formAlmacenMaterial">
            <select id="id_material" required>
                <option value="">Selecciona un material</option>
            </select>
            <div id="stock_disponible"></div>
            <select id="id_almacen" required>
                <option value="">Selecciona un almacén</option>
            </select>
            <input type="number" id="stock" placeholder="Stock" required>
            <button type="submit">Guardar</button>
            <button type="button" onclick="cancelarEdicion()">Cancelar</button>
        </form>`;
    document.querySelector('.content').insertAdjacentHTML('afterbegin', form);
    
    cargarSelectMateriales();
    cargarSelectAlmacenes();
    
    document.getElementById('formAlmacenMaterial').addEventListener('submit', guardarAlmacenMaterial);
}

function guardarAlmacenMaterial(e) {
    e.preventDefault();
    const id_material = document.getElementById('id_material').value;
    const id_almacen = document.getElementById('id_almacen').value;
    const stock = document.getElementById('stock').value;
    const formData = new FormData();
    formData.append('action', 'agregar');
    formData.append('id_material', id_material);
    formData.append('id_almacen', id_almacen);
    formData.append('stock', stock);

    fetch('almacen_material.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarAlmacenMaterial();
            actualizarStockMaterial(id_material, data.stock_restante);
            cancelarEdicion();
        } else {
            alert(data.error);
        }
    });
}

function actualizarStockMaterial(id_material, nuevoStock) {
    const selectMaterial = document.getElementById('id_material');
    const option = selectMaterial.querySelector(`option[value="${id_material}"]`);
    if (option) {
        option.dataset.stock = nuevoStock;
        const nombreMaterial = option.textContent.split('(')[0].trim();
        option.textContent = `${nombreMaterial} (Stock restante: ${nuevoStock})`;
    }
    actualizarStockDisponible();
}

function editarAlmacenMaterial(id_material, id_almacen, stock) {
    const tr = event.target.closest('tr');
    tr.innerHTML = `
        <td>${id_material}</td>
        <td><input type="number" id="edit_id_material" value="${id_material}" disabled></td>
        <td><input type="number" id="edit_id_almacen" value="${id_almacen}" disabled></td>
        <td><input type="number" id="edit_stock" value="${stock}"></td>
        <td>
            <button onclick="actualizarAlmacenMaterial(${id_material}, ${id_almacen})">Guardar</button>
            <button onclick="cancelarEdicion()">Cancelar</button>
        </td>`;
}

function actualizarAlmacenMaterial(id_material, id_almacen) {
    const stock = document.getElementById('edit_stock').value;
    const formData = new FormData();
    formData.append('action', 'actualizar');
    formData.append('id_material', id_material);
    formData.append('id_almacen', id_almacen);
    formData.append('stock', stock);

    fetch('almacen_material.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarAlmacenMaterial();
            actualizarStockMaterial(id_material, data.stock_restante);
        } else {
            alert(data.error);
        }
    });
}

function eliminarAlmacenMaterial(id_material, id_almacen) {
    if (confirm('¿Está seguro de que desea eliminar este material del almacén?')) {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id_material', id_material);
        formData.append('id_almacen', id_almacen);

        fetch('almacen_material.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarAlmacenMaterial();
                actualizarStockMaterial(id_material, data.stock_restante);
            } else {
                alert(data.error);
            }
        });
    }
}

function cancelarEdicion() {
    cargarAlmacenMaterial();
}