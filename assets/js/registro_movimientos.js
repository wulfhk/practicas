document.addEventListener('DOMContentLoaded', function() {
    cargarTodosMovimientos();
});

function cargarTodosMovimientos() {
    fetch('registro_movimientos.php?action=listar_todos')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#tablaRegistroMovimientos tbody');
            tbody.innerHTML = '';
            data.forEach(movimiento => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${movimiento.id_movimiento}</td>
                    <td>${movimiento.material}</td>
                    <td>${movimiento.almacen}</td>
                    <td>${movimiento.tipo_movimiento}</td>
                    <td>${movimiento.docente}</td>
                    <td>${movimiento.fecha_movimiento}</td>
                    <td>${movimiento.cantidad}</td>
                `;
                tbody.appendChild(tr);
            });
        });
}