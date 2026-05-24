<?php
/**
 * PNK Inmobiliaria — Eliminar Usuario
 * DELETE /api/usuarios/eliminar.php?id=X
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if (empty($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    jsonResponse(false, null, 'Acceso no autorizado.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? $_GET['id'] ?? 0);

if (!$id) {
    jsonResponse(false, null, 'ID de usuario requerido.', 400);
}

if ($id === $_SESSION['user_id']) {
    jsonResponse(false, null, 'No puedes eliminar tu propia cuenta.', 403);
}

$db = getDB();

// Verificar que existe
$stmt = $db->prepare("SELECT id, certificado_path FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(false, null, 'Usuario no encontrado.', 404);
}

// Eliminar certificado si existe
if (!empty($user['certificado_path'])) {
    $fullPath = __DIR__ . '/../../' . $user['certificado_path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

$stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->execute([$id]);

jsonResponse(true, null, 'Usuario eliminado correctamente.');
