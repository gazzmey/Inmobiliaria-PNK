<?php
/**
 * PNK Inmobiliaria — Listar Usuarios
 * GET /api/usuarios/listar.php
 * Query params: ?rol=admin|propietario|gestor  &estado=activo|pendiente|inactivo  &buscar=texto
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

// Verificar sesión admin
if (empty($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    jsonResponse(false, null, 'Acceso no autorizado.', 401);
}

$db = getDB();
$where = [];
$params = [];

// Filtro por rol
if (!empty($_GET['rol'])) {
    $where[] = "rol = ?";
    $params[] = $_GET['rol'];
}

// Filtro por estado
if (!empty($_GET['estado'])) {
    $where[] = "estado = ?";
    $params[] = $_GET['estado'];
}

// Búsqueda
if (!empty($_GET['buscar'])) {
    $buscar = '%' . $_GET['buscar'] . '%';
    $where[] = "(nombres LIKE ? OR apellido_paterno LIKE ? OR apellido_materno LIKE ? OR rut LIKE ? OR email LIKE ?)";
    array_push($params, $buscar, $buscar, $buscar, $buscar, $buscar);
}

$sql = "SELECT id, rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, rol, estado, nro_bienes_raices, penka_id, fecha_registro FROM usuarios";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY fecha_registro DESC";

// Paginación
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

// Total de registros
$countSql = "SELECT COUNT(*) FROM usuarios" . ($where ? " WHERE " . implode(" AND ", $where) : "");
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql .= " LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Conteos por rol
$conteos = $db->query("SELECT
    COUNT(*) as total,
    SUM(rol = 'admin') as admins,
    SUM(rol = 'propietario') as propietarios,
    SUM(rol = 'gestor') as gestores,
    SUM(estado = 'pendiente') as pendientes
FROM usuarios")->fetch();

jsonResponse(true, [
    'usuarios'  => $usuarios,
    'total'     => $total,
    'page'      => $page,
    'limit'     => $limit,
    'pages'     => ceil($total / $limit),
    'conteos'   => $conteos,
]);
