<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listarProveedores();
        break;
    case 'agregar':
        agregarProveedor();
        break;
    case 'actualizar':
        actualizarProveedor();
        break;
    case 'eliminar':
        eliminarProveedor();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function listarProveedores() {
    global $conn;
    $query = "SELECT * FROM proveedores";
    $result = $conn->query($query);
    $proveedores = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($proveedores);
}

function agregarProveedor() {
    global $conn;
    $entidad = $_POST['entidad'] ?? '';
    $representante = $_POST['representante'] ?? '';
    $contacto = $_POST['contacto'] ?? '';
    $email = $_POST['email'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    
    $query = "INSERT INTO proveedores (entidad, representante, contacto, email, direccion, fecha, comentario) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $entidad, $representante, $contacto, $email, $direccion, $fecha, $comentario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proveedor agregado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al agregar el proveedor']);
    }
}

function actualizarProveedor() {
    global $conn;
    $id = $_POST['id'] ?? '';
    $entidad = $_POST['entidad'] ?? '';
    $representante = $_POST['representante'] ?? '';
    $contacto = $_POST['contacto'] ?? '';
    $email = $_POST['email'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $comentario = $_POST['comentario'] ?? '';
    
    $query = "UPDATE proveedores SET entidad = ?, representante = ?, contacto = ?, email = ?, direccion = ?, fecha = ?, comentario = ? WHERE id_proveedor = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssi", $entidad, $representante, $contacto, $email, $direccion, $fecha, $comentario, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proveedor actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar el proveedor']);
    }
}

function eliminarProveedor() {
    global $conn;
    $id = $_POST['id'] ?? '';
    
    $query = "DELETE FROM proveedores WHERE id_proveedor = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proveedor eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar el proveedor']);
    }
}