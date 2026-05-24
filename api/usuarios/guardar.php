<?php
/**
 * PNK Inmobiliaria — Guardar Usuario (Crear o Actualizar)
 * POST /api/usuarios/guardar.php
 * Body: { id?, rut, nombres, apellido_paterno, apellido_materno, email, telefono,
 *         fecha_nacimiento, sexo, rol, estado, nro_bienes, penka_id, password? }
 * Si viene ID → actualizar, si no → crear
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if (empty($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    jsonResponse(false, null, 'Acceso no autorizado.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

// Validaciones
$required = ['rut', 'nombres', 'apellido_paterno', 'email', 'rol'];
foreach ($required as $field) {
    if (empty(trim($input[$field] ?? ''))) {
        jsonResponse(false, null, "El campo '$field' es obligatorio.", 400);
    }
}

// Validar RUT chileno
if (!validarRUT($input['rut'])) {
    jsonResponse(false, null, 'El RUT ingresado no es válido. Verifica el formato y dígito verificador.', 400);
}

// Validar email
if (!validarEmailFormato($input['email'])) {
    jsonResponse(false, null, 'El correo electrónico no tiene un formato válido.', 400);
}

$db = getDB();

if ($id > 0) {
    // ===== ACTUALIZAR =====
    $sql = "UPDATE usuarios SET
        rut = ?, nombres = ?, apellido_paterno = ?, apellido_materno = ?,
        email = ?, telefono = ?, fecha_nacimiento = ?, sexo = ?,
        rol = ?, estado = ?, nro_bienes_raices = ?, penka_id = ?
        WHERE id = ?";
    $params = [
        trim($input['rut']),
        trim($input['nombres']),
        trim($input['apellido_paterno']),
        trim($input['apellido_materno'] ?? ''),
        trim($input['email']),
        trim($input['telefono'] ?? ''),
        $input['fecha_nacimiento'] ?? null,
        $input['sexo'] ?? null,
        $input['rol'],
        $input['estado'] ?? 'activo',
        trim($input['nro_bienes'] ?? ''),
        trim($input['penka_id'] ?? ''),
        $id,
    ];
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Actualizar contraseña si se proporcionó
    if (!empty($input['password'])) {
        $passErrors = validarPasswordRobusta($input['password']);
        if (!empty($passErrors)) {
            jsonResponse(false, null, 'Contraseña débil: ' . implode(', ', $passErrors) . '.', 400);
        }
        $stmt = $db->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($input['password'], PASSWORD_DEFAULT), $id]);
    }

    jsonResponse(true, ['id' => $id], 'Usuario actualizado correctamente.');

} else {
    // ===== CREAR =====
    if (empty($input['password'])) {
        jsonResponse(false, null, 'La contraseña es obligatoria para nuevos usuarios.', 400);
    }
    if (strlen($input['password']) < 8) {
        jsonResponse(false, null, 'La contraseña debe tener al menos 8 caracteres.', 400);
    }

    // Verificar duplicados
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE rut = ? OR email = ?");
    $stmt->execute([trim($input['rut']), trim($input['email'])]);
    if ($stmt->fetch()) {
        jsonResponse(false, null, 'Ya existe un usuario con ese RUT o correo electrónico.', 409);
    }

    $stmt = $db->prepare("INSERT INTO usuarios (rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, password_hash, rol, estado, nro_bienes_raices, penka_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        trim($input['rut']),
        trim($input['nombres']),
        trim($input['apellido_paterno']),
        trim($input['apellido_materno'] ?? ''),
        trim($input['email']),
        trim($input['telefono'] ?? ''),
        $input['fecha_nacimiento'] ?? null,
        $input['sexo'] ?? null,
        password_hash($input['password'], PASSWORD_DEFAULT),
        $input['rol'],
        $input['estado'] ?? 'pendiente',
        trim($input['nro_bienes'] ?? ''),
        trim($input['penka_id'] ?? ''),
    ]);

    jsonResponse(true, ['id' => $db->lastInsertId()], 'Usuario creado correctamente.', 201);
}
