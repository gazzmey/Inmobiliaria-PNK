<?php
/**
 * PNK Inmobiliaria — Eliminar Propiedad
 * DELETE /api/propiedades/eliminar.php
 * Body: { id }
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if (empty($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    jsonResponse(false, null, 'Acceso no autorizado.', 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? $_GET['id'] ?? 0);

if (!$id) {
    jsonResponse(false, null, 'ID de propiedad requerido.', 400);
}

$db = getDB();

// Eliminar fotos del disco
$fotos = $db->prepare("SELECT ruta FROM propiedades_fotos WHERE propiedad_id = ?");
$fotos->execute([$id]);
foreach ($fotos->fetchAll() as $foto) {
    $path = __DIR__ . '/../../' . $foto['ruta'];
    if (file_exists($path)) unlink($path);
}

// La tabla propiedades_fotos se elimina en cascada
$stmt = $db->prepare("DELETE FROM propiedades WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    jsonResponse(false, null, 'Propiedad no encontrada.', 404);
}

jsonResponse(true, null, 'Propiedad eliminada correctamente.');
