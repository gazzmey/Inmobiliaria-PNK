<?php
/**
 * PNK Inmobiliaria — Cambiar Estado de Usuario
 * PUT /api/usuarios/cambiar-estado.php
 * Body: { id, estado: 'activo'|'pendiente'|'inactivo' }
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if (empty($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    jsonResponse(false, null, 'Acceso no autorizado.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$id     = intval($input['id'] ?? 0);
$estado = $input['estado'] ?? '';

if (!$id || !in_array($estado, ['activo', 'pendiente', 'inactivo'])) {
    jsonResponse(false, null, 'ID y estado válido son requeridos.', 400);
}

// No permitir desactivarse a sí mismo
if ($id === $_SESSION['user_id'] && $estado !== 'activo') {
    jsonResponse(false, null, 'No puedes desactivar tu propia cuenta.', 403);
}

$db = getDB();

// Si se activa un gestor pendiente, asignar PENKA_ID automáticamente
$penkaUpdate = '';
$params = [$estado, $id];

if ($estado === 'activo') {
    $stmt = $db->prepare("SELECT rol, penka_id FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user && $user['rol'] === 'gestor' && empty($user['penka_id'])) {
        // Generar PENKA_ID
        $count = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'gestor' AND penka_id IS NOT NULL")->fetchColumn();
        $penkaId = 'PENKA-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        $penkaUpdate = ", penka_id = ?";
        array_splice($params, 1, 0, [$penkaId]);
    }
}

$stmt = $db->prepare("UPDATE usuarios SET estado = ? $penkaUpdate WHERE id = ?");
$stmt->execute($params);

$labels = ['activo' => 'activado', 'pendiente' => 'puesto en pendiente', 'inactivo' => 'desactivado'];
jsonResponse(true, null, "Usuario {$labels[$estado]} correctamente.");
