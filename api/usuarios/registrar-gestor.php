<?php
/**
 * PNK Inmobiliaria — Registrar Gestor Inmobiliario Free
 * POST /api/usuarios/registrar-gestor.php
 * Acepta multipart/form-data (por el archivo certificado)
 */
session_start();
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido.', 405);
}

// Los datos pueden venir como form-data o JSON
$input = $_POST;

// Validaciones básicas
$required = ['rut', 'nombres', 'apellido_paterno', 'email', 'telefono', 'fecha_nacimiento', 'sexo', 'password', 'confirm_password'];
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

// Verificar certificado
if (empty($_FILES['certificado']) || $_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, null, 'El certificado de antecedentes es obligatorio.', 400);
}

$file = $_FILES['certificado'];
$allowed = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($file['type'], $allowed)) {
    jsonResponse(false, null, 'Formato de certificado no válido. Usa PDF, JPG o PNG.', 400);
}
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(false, null, 'El certificado no puede superar los 5 MB.', 400);
}

$db = getDB();

// Verificar duplicados
$stmt = $db->prepare("SELECT id FROM usuarios WHERE rut = ? OR email = ?");
$stmt->execute([trim($input['rut']), trim($input['email'])]);
if ($stmt->fetch()) {
    jsonResponse(false, null, 'Ya existe un usuario con ese RUT o correo electrónico.', 409);
}

// Guardar certificado
$uploadDir = __DIR__ . '/../../uploads/certificados/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'cert_' . preg_replace('/[^0-9]/', '', $input['rut']) . '_' . time() . '.' . $ext;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    jsonResponse(false, null, 'Error al guardar el certificado.', 500);
}

// Insertar gestor
$stmt = $db->prepare("INSERT INTO usuarios (rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, password_hash, rol, estado, certificado_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'gestor', 'pendiente', ?)");

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
    'uploads/certificados/' . $filename,
]);

jsonResponse(true, ['id' => $db->lastInsertId()], 'Postulación enviada. Será revisada por el Administrador. Recibirás tu PENKA_ID si eres aceptado.', 201);
