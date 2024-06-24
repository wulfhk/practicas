<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar_todos':
        listarTodosMovimientos();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function listarTodosMovimientos() {
    global $conn;
    $query = "SELECT m.id_movimiento, mat.nombre AS material, a.nombre AS almacen, 
              m.tipo_movimiento, m.docente, m.fecha_movimiento, m.cantidad
              FROM movimientos_almacen m
              JOIN materiales mat ON m.id_material = mat.id_material
              JOIN almacenes a ON m.id_almacen = a.id_almacen
              ORDER BY m.fecha_movimiento DESC";
    $result = $conn->query($query);
    $movimientos = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($movimientos);
}