<?php
/**
 * PNK Inmobiliaria — Búsqueda Pública de Propiedades
 * GET /api/propiedades/buscar.php
 * Query: ?tipo=  &provincia=  &comuna=  &sector=
 * Retorna solo propiedades activas (para la página pública)
 */
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$db = getDB();
$where = ["p.estado = 'activo'"];
$params = [];

if (!empty($_GET['tipo'])) {
    $where[] = "p.tipo = ?";
    $params[] = strtolower($_GET['tipo']);
}
if (!empty($_GET['provincia'])) {
    $where[] = "p.provincia = ?";
    $params[] = strtolower($_GET['provincia']);
}
if (!empty($_GET['comuna'])) {
    $where[] = "p.comuna LIKE ?";
    $params[] = '%' . $_GET['comuna'] . '%';
}
if (!empty($_GET['sector'])) {
    $where[] = "p.sector LIKE ?";
    $params[] = '%' . $_GET['sector'] . '%';
}

$sql = "SELECT p.id, p.codigo, p.tipo, p.provincia, p.comuna, p.sector,
    p.dormitorios, p.banos, p.area_terreno, p.area_construida,
    p.precio_pesos, p.precio_uf, p.descripcion,
    (SELECT ruta FROM propiedades_fotos WHERE propiedad_id = p.id AND es_portada = 1 LIMIT 1) as foto_portada
    FROM propiedades p
    WHERE " . implode(" AND ", $where) . "
    ORDER BY p.fecha_publicacion DESC
    LIMIT 20";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$props = $stmt->fetchAll();

jsonResponse(true, $props);
