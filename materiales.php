<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listarMateriales();
        break;
    case 'agregar':
        agregarMaterial();
        break;
    case 'actualizar':
        actualizarMaterial();
        break;
    case 'eliminar':
        eliminarMaterial();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function listarMateriales() {
    global $conn;
    $query = "SELECT m.*, p.entidad as proveedor FROM materiales m JOIN proveedores p ON m.id_proveedor = p.id_proveedor";
    $result = $conn->query($query);
    $materiales = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($materiales);
}

function agregarMaterial() {
    global $conn;
    $nombre = $_POST['nombre'] ?? '';
    $num_adquiridos = $_POST['num_adquiridos'] ?? 0;
    $costo = $_POST['costo'] ?? null;
    $fecha_adquisicion = $_POST['fecha_adquisicion'] ?? '';
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    
    $query = "INSERT INTO materiales (nombre, num_adquiridos, costo, fecha_adquisicion, id_proveedor) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sidsi", $nombre, $num_adquiridos, $costo, $fecha_adquisicion, $id_proveedor);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Material agregado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al agregar el material']);
    }
}

function actualizarMaterial() {
    global $conn;
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $num_adquiridos = $_POST['num_adquiridos'] ?? 0;
    $costo = $_POST['costo'] ?? null;
    $fecha_adquisicion = $_POST['fecha_adquisicion'] ?? '';
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    
    $query = "UPDATE materiales SET nombre = ?, num_adquiridos = ?, costo = ?, fecha_adquisicion = ?, id_proveedor = ? WHERE id_material = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sidsii", $nombre, $num_adquiridos, $costo, $fecha_adquisicion, $id_proveedor, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Material actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar el material']);
    }
}

function eliminarMaterial() {
    global $conn;
    $id = $_POST['id'] ?? '';
    
    $query = "DELETE FROM materiales WHERE id_material = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Material eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar el material']);
    }
}