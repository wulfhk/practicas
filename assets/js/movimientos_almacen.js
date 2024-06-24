document.addEventListener('DOMContentLoaded', function() {
    cargarAlmacenes();
    document.getElementById('selectAlmacen').addEventListener('change', cambiarAlmacen);
    document.getElementById('formSalida').addEventListener('submit', registrarSalida);
});

let almacenSeleccionado = null;

function cargarAlmacenes() {
    fetch('movimientos_almacen.php?action=listar_almacenes')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('selectAlmacen');
            data.forEach(almacen => {
                const option = document.createElement('option');
                option.value = almacen.id_almacen;
                option.textContent = almacen.nombre;
                select.appendChild(option);
            });
        });
}

function cambiarAlmacen() {
    almacenSeleccionado = this.value;
    document.getElementById('tituloAlmacen').textContent = `Movimientos de Almacén: ${this.options[this.selectedIndex].text}`;
    cargarMateriales();
    cargarSalidas();
}

function cargarMateriales() {
    fetch(`movimientos_almacen.php?action=listar_materiales&id_almacen=${almacenSeleccionado}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('selectMaterial');
            select.innerHTML = '<option value="">Seleccione un material</option>';
            data.forEach(material => {
                const option = document.createElement('option');
                option.value = material.id_material;
                option.textContent = `${material.nombre} (Stock: ${material.stock})`;
                option.dataset.stock = material.stock;
                select.appendChild(option);
            });
        });
}

function registrarSalida(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'registrar_salida');
    formData.append('id_almacen', almacenSeleccionado);

    fetch('movimientos_almacen.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cargarSalidas();
            cargarMateriales();
            e.target.reset();
        } else {
            alert(data.error);
        }
    });
}

function cargarSalidas() {
    fetch(`movimientos_almacen.php?action=listar_salidas&id_almacen=${almacenSeleccionado}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tablaSalidas tbody');
            tbody.innerHTML = '';
            data.forEach(salida => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${salida.nombre_material}</td>
                    <td>${salida.cantidad}</td>
                    <td>${salida.docente}</td>
                    <td>${salida.sesion_aprendizaje}</td>
                    <td>${salida.grado}</td>
                    <td>${salida.seccion}</td>
                    <td>${salida.num_estudiantes}</td>
                    <td>${salida.nivel}</td>
                    <td>
                        <button onclick="editarSalida(${salida.id_movimiento}, ${salida.cantidad}, ${salida.cantidad_original})">Editar</button>
                        <button onclick="registrarRetorno(${salida.id_movimiento}, ${salida.cantidad})">Retornar</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

function editarSalida(id_movimiento, cantidad_actual, cantidad_original) {
    const nueva_cantidad = prompt(`Ingrese la nueva cantidad (máximo ${cantidad_original}):`, cantidad_actual);
    if (nueva_cantidad !== null && nueva_cantidad !== '') {
        if (parseInt(nueva_cantidad) > cantidad_original) {
            alert('La nueva cantidad no puede ser mayor que la cantidad original');
            return;
        }
        const formData = new FormData();
        formData.append('action', 'actualizar_salida');
        formData.append('id_movimiento', id_movimiento);
        formData.append('nueva_cantidad', nueva_cantidad);

        fetch('movimientos_almacen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarSalidas();
                cargarMateriales();
            } else {
                alert(data.error);
            }
        });
    }
}

function registrarRetorno(id_movimiento, cantidad_actual) {
    const cantidad_retorno = prompt(`Ingrese la cantidad a retornar (máximo ${cantidad_actual}):`, cantidad_actual);
    if (cantidad_retorno !== null && cantidad_retorno !== '') {
        if (parseInt(cantidad_retorno) > cantidad_actual) {
            alert('La cantidad de retorno no puede ser mayor que la cantidad actual');
            return;
        }
        const formData = new FormData();
        formData.append('action', 'registrar_retorno');
        formData.append('id_movimiento', id_movimiento);
        formData.append('cantidad_retorno', cantidad_retorno);

        fetch('movimientos_almacen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarSalidas();
                cargarMateriales();
            } else {
                alert(data.error);
            }
        });
    }
}

// Agregar un event listener para actualizar el stock disponible cuando se selecciona un material
document.getElementById('selectMaterial').addEventListener('change', function() {
    const stockDisponible = this.options[this.selectedIndex].dataset.stock;
    document.getElementById('cantidadSalida').max = stockDisponible;
    document.getElementById('cantidadSalida').placeholder = `Máximo: ${stockDisponible}`;
});