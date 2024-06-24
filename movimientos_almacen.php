<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar_almacenes':
        listarAlmacenes();
        break;
    case 'listar_materiales':
        listarMateriales();
        break;
    case 'registrar_salida':
        registrarSalida();
        break;
    case 'listar_salidas':
        listarSalidas();
        break;
    case 'actualizar_salida':
        actualizarSalida();
        break;
    case 'registrar_retorno':
        registrarRetorno();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
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

function listarMateriales() {
    global $conn;
    $id_almacen = $_GET['id_almacen'] ?? '';
    if (empty($id_almacen)) {
        echo json_encode(['error' => 'ID de almacén no proporcionado']);
        return;
    }
    $query = "SELECT m.id_material, m.nombre, am.stock
              FROM materiales m
              INNER JOIN almacen_material am ON m.id_material = am.id_material
              WHERE am.id_almacen = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_almacen);
    $stmt->execute();
    $result = $stmt->get_result();
    $materiales = [];
    while ($row = $result->fetch_assoc()) {
        $materiales[] = $row;
    }
    echo json_encode($materiales);
}

function registrarSalida() {
    global $conn;
    $id_almacen = $_POST['id_almacen'] ?? '';
    $id_material = $_POST['id_material'] ?? '';
    $cantidad = $_POST['cantidad'] ?? '';
    $docente = $_POST['docente'] ?? '';
    $sesion_aprendizaje = $_POST['sesion_aprendizaje'] ?? '';
    $grado = $_POST['grado'] ?? '';
    $seccion = $_POST['seccion'] ?? '';
    $num_estudiantes = $_POST['num_estudiantes'] ?? '';
    $nivel = $_POST['nivel'] ?? '';

    if (empty($id_almacen) || empty($id_material) || empty($cantidad) || empty($docente)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Verificar stock disponible
        $query_stock = "SELECT stock FROM almacen_material WHERE id_almacen = ? AND id_material = ?";
        $stmt_stock = $conn->prepare($query_stock);
        $stmt_stock->bind_param("ii", $id_almacen, $id_material);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        $row_stock = $result_stock->fetch_assoc();
        $stock_disponible = $row_stock['stock'];

        if ($stock_disponible < $cantidad) {
            throw new Exception('Stock insuficiente');
        }

        // Actualizar stock en almacen_material
        $query_update = "UPDATE almacen_material SET stock = stock - ? WHERE id_almacen = ? AND id_material = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("iii", $cantidad, $id_almacen, $id_material);
        $stmt_update->execute();

        // Registrar movimiento de salida
        $query_movimiento = "INSERT INTO movimientos_almacen (id_material, id_almacen, tipo_movimiento, docente, sesion_aprendizaje, grado, seccion, num_estudiantes, nivel, cantidad, stock_disponible, cantidad_original) VALUES (?, ?, 'salida', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_movimiento = $conn->prepare($query_movimiento);
        $stock_actualizado = $stock_disponible - $cantidad;
        $stmt_movimiento->bind_param("iisssssisiii", $id_material, $id_almacen, $docente, $sesion_aprendizaje, $grado, $seccion, $num_estudiantes, $nivel, $cantidad, $stock_actualizado, $cantidad);
        $stmt_movimiento->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Salida registrada correctamente', 'stock_actualizado' => $stock_actualizado]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function listarSalidas() {
    global $conn;
    $id_almacen = $_GET['id_almacen'] ?? '';
    if (empty($id_almacen)) {
        echo json_encode(['error' => 'ID de almacén no proporcionado']);
        return;
    }
    $query = "SELECT m.id_movimiento, m.id_material, mat.nombre AS nombre_material, m.cantidad, m.docente, m.sesion_aprendizaje, m.grado, m.seccion, m.num_estudiantes, m.nivel, m.fecha_movimiento, m.cantidad_original
              FROM movimientos_almacen m
              INNER JOIN materiales mat ON m.id_material = mat.id_material
              WHERE m.id_almacen = ? AND m.tipo_movimiento = 'salida' AND m.cantidad > 0
              ORDER BY m.fecha_movimiento DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_almacen);
    $stmt->execute();
    $result = $stmt->get_result();
    $salidas = [];
    while ($row = $result->fetch_assoc()) {
        $salidas[] = $row;
    }
    echo json_encode($salidas);
}

function actualizarSalida() {
    global $conn;
    $id_movimiento = $_POST['id_movimiento'] ?? '';
    $nueva_cantidad = $_POST['nueva_cantidad'] ?? '';

    if (empty($id_movimiento) || empty($nueva_cantidad)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Obtener información del movimiento actual
        $query_movimiento = "SELECT id_material, id_almacen, cantidad, cantidad_original FROM movimientos_almacen WHERE id_movimiento = ?";
        $stmt_movimiento = $conn->prepare($query_movimiento);
        $stmt_movimiento->bind_param("i", $id_movimiento);
        $stmt_movimiento->execute();
        $result_movimiento = $stmt_movimiento->get_result();
        $row_movimiento = $result_movimiento->fetch_assoc();

        $id_material = $row_movimiento['id_material'];
        $id_almacen = $row_movimiento['id_almacen'];
        $cantidad_actual = $row_movimiento['cantidad'];
        $cantidad_original = $row_movimiento['cantidad_original'];

        if ($nueva_cantidad > $cantidad_original) {
            throw new Exception('La nueva cantidad no puede ser mayor que la cantidad original');
        }

        // Calcular la diferencia de cantidad
        $diferencia = $cantidad_actual - $nueva_cantidad;

        // Actualizar stock en almacen_material
        $query_update_stock = "UPDATE almacen_material SET stock = stock + ? WHERE id_almacen = ? AND id_material = ?";
        $stmt_update_stock = $conn->prepare($query_update_stock);
        $stmt_update_stock->bind_param("iii", $diferencia, $id_almacen, $id_material);
        $stmt_update_stock->execute();

        // Actualizar el movimiento
        $query_update_movimiento = "UPDATE movimientos_almacen SET cantidad = ? WHERE id_movimiento = ?";
        $stmt_update_movimiento = $conn->prepare($query_update_movimiento);
        $stmt_update_movimiento->bind_param("ii", $nueva_cantidad, $id_movimiento);
        $stmt_update_movimiento->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Salida actualizada correctamente']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function registrarRetorno() {
    global $conn;
    $id_movimiento = $_POST['id_movimiento'] ?? '';
    $cantidad_retorno = $_POST['cantidad_retorno'] ?? '';

    if (empty($id_movimiento) || empty($cantidad_retorno)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Obtener información del movimiento actual
        $query_movimiento = "SELECT id_material, id_almacen, cantidad, cantidad_original FROM movimientos_almacen WHERE id_movimiento = ?";
        $stmt_movimiento = $conn->prepare($query_movimiento);
        $stmt_movimiento->bind_param("i", $id_movimiento);
        $stmt_movimiento->execute();
        $result_movimiento = $stmt_movimiento->get_result();
        $row_movimiento = $result_movimiento->fetch_assoc();

        $id_material = $row_movimiento['id_material'];
        $id_almacen = $row_movimiento['id_almacen'];
        $cantidad_actual = $row_movimiento['cantidad'];

        if ($cantidad_retorno > $cantidad_actual) {
            throw new Exception('La cantidad de retorno no puede ser mayor que la cantidad actual');
        }

        // Actualizar stock en almacen_material
        $query_update_stock = "UPDATE almacen_material SET stock = stock + ? WHERE id_almacen = ? AND id_material = ?";
        $stmt_update_stock = $conn->prepare($query_update_stock);
        $stmt_update_stock->bind_param("iii", $cantidad_retorno, $id_almacen, $id_material);
        $stmt_update_stock->execute();

        // Actualizar el movimiento
        $nueva_cantidad = $cantidad_actual - $cantidad_retorno;
        $query_update_movimiento = "UPDATE movimientos_almacen SET cantidad = ? WHERE id_movimiento = ?";
        $stmt_update_movimiento = $conn->prepare($query_update_movimiento);
        $stmt_update_movimiento->bind_param("ii", $nueva_cantidad, $id_movimiento);
        $stmt_update_movimiento->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Retorno registrado correctamente']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
}