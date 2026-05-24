<?php
/**
 * PNK Inmobiliaria — Listar Propiedades
 * GET /api/propiedades/listar.php
 * Query: ?tipo=casa|departamento|terreno  &provincia=  &comuna=  &estado=  &buscar=  &page=  &limit=
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$db = getDB();
$where = [];
$params = [];

// Filtros
if (!empty($_GET['tipo'])) {
    $where[] = "p.tipo = ?";
    $params[] = $_GET['tipo'];
}
if (!empty($_GET['provincia'])) {
    $where[] = "p.provincia = ?";
    $params[] = $_GET['provincia'];
}
if (!empty($_GET['comuna'])) {
    $where[] = "p.comuna = ?";
    $params[] = $_GET['comuna'];
}
if (!empty($_GET['sector'])) {
    $where[] = "p.sector LIKE ?";
    $params[] = '%' . $_GET['sector'] . '%';
}
if (!empty($_GET['estado'])) {
    $where[] = "p.estado = ?";
    $params[] = $_GET['estado'];
}
if (!empty($_GET['buscar'])) {
    $buscar = '%' . $_GET['buscar'] . '%';
    $where[] = "(p.codigo LIKE ? OR p.sector LIKE ? OR p.comuna LIKE ? OR p.descripcion LIKE ?)";
    array_push($params, $buscar, $buscar, $buscar, $buscar);
}

$sql = "SELECT p.*,
    CONCAT(u.nombres, ' ', u.apellido_paterno) as propietario_nombre,
    u.rut as propietario_rut
    FROM propiedades p
    LEFT JOIN usuarios u ON p.propietario_id = u.id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.fecha_registro DESC";

// Paginación
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM propiedades p" . ($where ? " WHERE " . implode(" AND ", $where) : "");
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql .= " LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$propiedades = $stmt->fetchAll();

// Agregar fotos a cada propiedad
foreach ($propiedades as &$prop) {
    $fStmt = $db->prepare("SELECT id, ruta, es_portada FROM propiedades_fotos WHERE propiedad_id = ? ORDER BY es_portada DESC, orden ASC");
    $fStmt->execute([$prop['id']]);
    $prop['fotos'] = $fStmt->fetchAll();
}

// Conteos
$conteos = $db->query("SELECT
    COUNT(*) as total,
    SUM(tipo = 'casa') as casas,
    SUM(tipo = 'departamento') as departamentos,
    SUM(tipo = 'terreno') as terrenos,
    SUM(estado = 'activo') as activas,
    SUM(estado = 'vendida') as vendidas,
    SUM(estado = 'inactivo') as inactivas
FROM propiedades")->fetch();

jsonResponse(true, [
    'propiedades' => $propiedades,
    'total'       => $total,
    'page'        => $page,
    'limit'       => $limit,
    'pages'       => ceil($total / $limit),
    'conteos'     => $conteos,
]);
