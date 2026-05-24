<?php
/**
 * PNK Inmobiliaria — Verificar sesión
 * GET /api/auth/session.php
 * Retorna datos del usuario logueado o error 401
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if (empty($_SESSION['user_id'])) {
    jsonResponse(false, null, 'No hay sesión activa.', 401);
}

$db = getDB();
$stmt = $db->prepare("SELECT id, rut, nombres, apellido_paterno, apellido_materno, email, rol, estado, penka_id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    jsonResponse(false, null, 'Usuario no encontrado.', 401);
}

jsonResponse(true, $user);
