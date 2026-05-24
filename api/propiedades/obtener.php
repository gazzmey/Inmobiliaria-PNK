<?php
/**
 * PNK Inmobiliaria — Obtener una Propiedad
 * GET /api/propiedades/obtener.php?id=X  o  ?codigo=C0001
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$db = getDB();

$id     = intval($_GET['id'] ?? 0);
$codigo = trim($_GET['codigo'] ?? '');

if (!$id && !$codigo) {
    jsonResponse(false, null, 'ID o código de propiedad requerido.', 400);
}

if ($id) {
    $stmt = $db->prepare("SELECT p.*, CONCAT(u.nombres, ' ', u.apellido_paterno) as propietario_nombre, u.rut as propietario_rut, u.email as propietario_email, u.telefono as propietario_telefono FROM propiedades p LEFT JOIN usuarios u ON p.propietario_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
} else {
    $stmt = $db->prepare("SELECT p.*, CONCAT(u.nombres, ' ', u.apellido_paterno) as propietario_nombre, u.rut as propietario_rut, u.email as propietario_email, u.telefono as propietario_telefono FROM propiedades p LEFT JOIN usuarios u ON p.propietario_id = u.id WHERE p.codigo = ?");
    $stmt->execute([$codigo]);
}

$prop = $stmt->fetch();
if (!$prop) {
    jsonResponse(false, null, 'Propiedad no encontrada.', 404);
}

// Fotos
$fStmt = $db->prepare("SELECT * FROM propiedades_fotos WHERE propiedad_id = ? ORDER BY es_portada DESC, orden ASC");
$fStmt->execute([$prop['id']]);
$prop['fotos'] = $fStmt->fetchAll();

jsonResponse(true, $prop);
