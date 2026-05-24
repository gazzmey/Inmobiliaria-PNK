<?php
/**
 * PNK Inmobiliaria — Registrar Propietario
 * POST /api/usuarios/registrar-propietario.php
 * Body: { rut, nombres, apellido_paterno, apellido_materno, email, telefono,
 *         fecha_nacimiento, sexo, nro_bienes_raices, password, confirm_password }
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

// Validaciones básicas
$required = ['rut', 'nombres', 'apellido_paterno', 'email', 'telefono', 'fecha_nacimiento', 'sexo', 'nro_bienes_raices', 'password', 'confirm_password'];
foreach ($required as $field) {
    if (empty(trim($input[$field] ?? ''))) {
        jsonResponse(false, null, "El campo '$field' es obligatorio.", 400);
    }
}

if ($input['password'] !== $input['confirm_password']) {
    jsonResponse(false, null, 'Las contraseñas no coinciden.', 400);
}

// Validar RUT chileno
if (!validarRUT($input['rut'])) {
    jsonResponse(false, null, 'El RUT ingresado no es válido. Verifica el formato y dígito verificador.', 400);
}

// Validar email
if (!validarEmailFormato($input['email'])) {
    jsonResponse(false, null, 'El correo electrónico no tiene un formato válido.', 400);
}

// Validar contraseña robusta
$passErrors = validarPasswordRobusta($input['password']);
if (!empty($passErrors)) {
    jsonResponse(false, null, 'Contraseña débil: ' . implode(', ', $passErrors) . '.', 400);
}

$db = getDB();

// Verificar duplicados
$stmt = $db->prepare("SELECT id FROM usuarios WHERE rut = ? OR email = ?");
$stmt->execute([trim($input['rut']), trim($input['email'])]);
if ($stmt->fetch()) {
    jsonResponse(false, null, 'Ya existe un usuario con ese RUT o correo electrónico.', 409);
}

// Insertar
$stmt = $db->prepare("INSERT INTO usuarios (rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, password_hash, rol, estado, nro_bienes_raices)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'propietario', 'pendiente', ?)");

$stmt->execute([
    trim($input['rut']),
    trim($input['nombres']),
    trim($input['apellido_paterno']),
    trim($input['apellido_materno'] ?? ''),
    trim($input['email']),
    trim($input['telefono']),
    $input['fecha_nacimiento'],
    $input['sexo'],
    password_hash($input['password'], PASSWORD_DEFAULT),
    trim($input['nro_bienes_raices']),
]);

jsonResponse(true, ['id' => $db->lastInsertId()], 'Registro exitoso. Tu cuenta quedará en estado Pendiente hasta ser verificada por el Administrador.', 201);
