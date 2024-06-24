<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listarAlmacenMaterial();
        break;
    case 'agregar':
        agregarAlmacenMaterial();
        break;
    case 'actualizar':
        actualizarAlmacenMaterial();
        break;
    case 'eliminar':
        eliminarAlmacenMaterial();
        break;
    case 'listar_almacenes':
        listarAlmacenes();
        break;
    case 'listar_materiales':
        listarMateriales();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function obtenerStockRestante($id_material) {
    global $conn;
    $query = "SELECT m.num_adquiridos - COALESCE(SUM(am.stock), 0) AS stock_restante
              FROM materiales m
              LEFT JOIN almacen_material am ON m.id_material = am.id_material
              WHERE m.id_material = ?
              GROUP BY m.id_material";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_material);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['stock_restante'];
}

function listarMateriales() {
    global $conn;
    $query = "SELECT m.id_material, m.nombre, 
              m.num_adquiridos - COALESCE(SUM(am.stock), 0) AS stock_disponible
              FROM materiales m
              LEFT JOIN almacen_material am ON m.id_material = am.id_material
              GROUP BY m.id_material";
    $result = $conn->query($query);
    $materiales = [];
    while ($row = $result->fetch_assoc()) {
        $materiales[] = $row;
    }
    echo json_encode($materiales);
}

function listarAlmacenes() {
    global $conn;
    $query = "SELECT id_almacen, nombre FROM almacenes";
    $result = $conn->query($query);
    $almacenes = [];
    while ($row = $result->fetch_assoc()) {
        $almacenes[] = $row;
    }
    echo json_encode($almacenes);
}

function listarAlmacenMaterial() {
    global $conn;
    $query = "SELECT am.id_material, m.nombre AS nombre_material, 
              am.id_almacen, a.nombre AS nombre_almacen, am.stock
              FROM almacen_material am
              INNER JOIN materiales m ON am.id_material = m.id_material
              INNER JOIN almacenes a ON am.id_almacen = a.id_almacen";
    $result = $conn->query($query);
    $almacen_material = [];
    while ($row = $result->fetch_assoc()) {
        $almacen_material[] = $row;
    }
    echo json_encode($almacen_material);
}

function agregarAlmacenMaterial() {
    global $conn;
    $id_material = $_POST['id_material'] ?? '';
    $id_almacen = $_POST['id_almacen'] ?? '';
    $stock = $_POST['stock'] ?? '';

    if (empty($id_material) || empty($id_almacen) || empty($stock)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    // Verificar el stock general
    $query_total_stock = "SELECT num_adquiridos FROM materiales WHERE id_material = ?";
    $stmt_total_stock = $conn->prepare($query_total_stock);
    $stmt_total_stock->bind_param("i", $id_material);
    $stmt_total_stock->execute();
    $result_total_stock = $stmt_total_stock->get_result();
    $total_stock = $result_total_stock->fetch_assoc()['num_adquiridos'];

    // Verificar el stock ya asignado
    $query_assigned_stock = "SELECT COALESCE(SUM(stock), 0) AS total_stock FROM almacen_material WHERE id_material = ?";
    $stmt_assigned_stock = $conn->prepare($query_assigned_stock);
    $stmt_assigned_stock->bind_param("i", $id_material);
    $stmt_assigned_stock->execute();
    $result_assigned_stock = $stmt_assigned_stock->get_result();
    $assigned_stock = $result_assigned_stock->fetch_assoc()['total_stock'];

    if ($assigned_stock + $stock > $total_stock) {
        echo json_encode(['error' => 'El stock total en almacenes no puede exceder el stock general']);
        return;
    }

    $query = "INSERT INTO almacen_material (id_material, id_almacen, stock) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $id_material, $id_almacen, $stock);

    if ($stmt->execute()) {
        $stock_restante = obtenerStockRestante($id_material);
        echo json_encode([
            'success' => true, 
            'message' => 'Material asignado al almacén correctamente',
            'stock_restante' => $stock_restante
        ]);
    } else {
        echo json_encode(['error' => 'Error al asignar el material al almacén']);
    }
}

function actualizarAlmacenMaterial() {
    global $conn;
    $id_material = $_POST['id_material'] ?? '';
    $id_almacen = $_POST['id_almacen'] ?? '';
    $stock = $_POST['stock'] ?? '';

    if (empty($id_material) || empty($id_almacen) || empty($stock)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    // Verificar el stock general
    $query_total_stock = "SELECT num_adquiridos FROM materiales WHERE id_material = ?";
    $stmt_total_stock = $conn->prepare($query_total_stock);
    $stmt_total_stock->bind_param("i", $id_material);
    $stmt_total_stock->execute();
    $result_total_stock = $stmt_total_stock->get_result();
    $total_stock = $result_total_stock->fetch_assoc()['num_adquiridos'];

    // Verificar el stock ya asignado (excepto el registro actual)
    $query_assigned_stock = "SELECT COALESCE(SUM(stock), 0) AS total_stock FROM almacen_material WHERE id_material = ? AND id_almacen != ?";
    $stmt_assigned_stock = $conn->prepare($query_assigned_stock);
    $stmt_assigned_stock->bind_param("ii", $id_material, $id_almacen);
    $stmt_assigned_stock->execute();
    $result_assigned_stock = $stmt_assigned_stock->get_result();
    $assigned_stock = $result_assigned_stock->fetch_assoc()['total_stock'];

    if ($assigned_stock + $stock > $total_stock) {
        echo json_encode(['error' => 'El stock total en almacenes no puede exceder el stock general']);
        return;
    }

    $query = "UPDATE almacen_material SET stock = ? WHERE id_material = ? AND id_almacen = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $stock, $id_material, $id_almacen);

    if ($stmt->execute()) {
        $stock_restante = obtenerStockRestante($id_material);
        echo json_encode([
            'success' => true, 
            'message' => 'Stock actualizado correctamente',
            'stock_restante' => $stock_restante
        ]);
    } else {
        echo json_encode(['error' => 'Error al actualizar el stock']);
    }
}

function eliminarAlmacenMaterial() {
    global $conn;
    $id_material = $_POST['id_material'] ?? '';
    $id_almacen = $_POST['id_almacen'] ?? '';

    if (empty($id_material) || empty($id_almacen)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    $query = "DELETE FROM almacen_material WHERE id_material = ? AND id_almacen = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id_material, $id_almacen);

    if ($stmt->execute()) {
        $stock_restante = obtenerStockRestante($id_material);
        echo json_encode([
            'success' => true, 
            'message' => 'Material eliminado del almacén correctamente',
            'stock_restante' => $stock_restante
        ]);
    } else {
        echo json_encode(['error' => 'Error al eliminar el material del almacén']);
    }
}
?>