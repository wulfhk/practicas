<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listarAlmacenes();
        break;
    case 'agregar':
        agregarAlmacen();
        break;
    case 'actualizar':
        actualizarAlmacen();
        break;
    case 'eliminar':
        eliminarAlmacen();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function listarAlmacenes() {
    global $conn;
    $query = "SELECT * FROM almacenes";
    $result = $conn->query($query);
    $almacenes = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($almacenes);
}

function agregarAlmacen() {
    global $conn;
    $nombre = $_POST['nombre'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    
    $query = "INSERT INTO almacenes (nombre, ubicacion) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $nombre, $ubicacion);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Almacén agregado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al agregar el almacén']);
    }
}

function actualizarAlmacen() {
    global $conn;
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $ubicacion = $_POST['ubicacion'] ?? '';
    
    $query = "UPDATE almacenes SET nombre = ?, ubicacion = ? WHERE id_almacen = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $nombre, $ubicacion, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Almacén actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar el almacén']);
    }
}

function eliminarAlmacen() {
    global $conn;
    $id = $_POST['id'] ?? '';
    
    $query = "DELETE FROM almacenes WHERE id_almacen = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Almacén eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar el almacén']);
    }
}