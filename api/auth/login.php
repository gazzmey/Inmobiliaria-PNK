<?php
/**
 * PNK Inmobiliaria — Login
 * POST /api/auth/login.php
 * Body: { email, password }
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

// Leer datos
$input = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(false, null, 'Email y contraseña son obligatorios.', 400);
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(false, null, 'Credenciales inválidas.', 401);
}

// Verificar contraseña
if (!password_verify($password, $user['password_hash'])) {
    jsonResponse(false, null, 'Credenciales inválidas.', 401);
}

// Verificar estado
if ($user['estado'] === 'pendiente') {
    jsonResponse(false, null, 'Tu cuenta aún está pendiente de verificación por el administrador.', 403);
}
if ($user['estado'] === 'inactivo') {
    jsonResponse(false, null, 'Tu cuenta se encuentra inactiva. Contacta al administrador.', 403);
}

// Crear sesión
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_rol']  = $user['rol'];
$_SESSION['user_name'] = $user['nombres'] . ' ' . $user['apellido_paterno'];

$nombre_completo = $user['nombres'] . ' ' . $user['apellido_paterno'] . ' ' . ($user['apellido_materno'] ?? '');

jsonResponse(true, [
    'id'     => $user['id'],
    'nombre' => trim($nombre_completo),
    'email'  => $user['email'],
    'rol'    => $user['rol'],
    'estado' => $user['estado'],
    'penka_id' => $user['penka_id'],
], 'Inicio de sesión exitoso.');
