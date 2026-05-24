<?php
/**
 * PNK Inmobiliaria — Obtener un usuario
 * GET /api/usuarios/obtener.php?id=X
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if (empty($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    jsonResponse(false, null, 'Acceso no autorizado.', 401);
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    jsonResponse(false, null, 'ID de usuario requerido.', 400);
}

$db = getDB();
$stmt = $db->prepare("SELECT id, rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, rol, estado, nro_bienes_raices, penka_id, certificado_path, fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(false, null, 'Usuario no encontrado.', 404);
}

jsonResponse(true, $user);
