<?php
/**
 * PNK Inmobiliaria — Guardar Propiedad (Crear o Actualizar)
 * POST /api/propiedades/guardar.php
 * Acepta multipart/form-data (por las fotos)
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

$input = $_POST;
$id = intval($input['id'] ?? 0);

// Validaciones
$required = ['tipo', 'provincia', 'comuna', 'sector', 'area_terreno', 'precio_pesos', 'precio_uf'];
foreach ($required as $field) {
    if (empty(trim($input[$field] ?? ''))) {
        jsonResponse(false, null, "El campo '$field' es obligatorio.", 400);
    }
}

$db = getDB();

// Amenidades (checkboxes vienen como 'on' o ausentes)
$amenidades = ['bodega', 'estacionamiento', 'logia', 'cocina_amoblada', 'antejardin', 'patio_trasero', 'piscina'];
$amenValues = [];
foreach ($amenidades as $a) {
    $amenValues[$a] = isset($input[$a]) ? 1 : 0;
}

if ($id > 0) {
    // ===== ACTUALIZAR =====
    $stmt = $db->prepare("UPDATE propiedades SET
        tipo = ?, provincia = ?, comuna = ?, sector = ?,
        dormitorios = ?, banos = ?, area_terreno = ?, area_construida = ?,
        precio_pesos = ?, precio_uf = ?, descripcion = ?, estado = ?,
        bodega = ?, estacionamiento = ?, logia = ?, cocina_amoblada = ?,
        antejardin = ?, patio_trasero = ?, piscina = ?,
        propietario_id = ?, fecha_publicacion = ?
        WHERE id = ?");

    $stmt->execute([
        $input['tipo'], $input['provincia'], $input['comuna'], $input['sector'],
        intval($input['dormitorios'] ?? 0), intval($input['banos'] ?? 0),
        floatval($input['area_terreno']), floatval($input['area_construida'] ?? 0),
        intval($input['precio_pesos']), floatval($input['precio_uf']),
        $input['descripcion'] ?? '', $input['estado'] ?? 'activo',
        $amenValues['bodega'], $amenValues['estacionamiento'], $amenValues['logia'],
        $amenValues['cocina_amoblada'], $amenValues['antejardin'],
        $amenValues['patio_trasero'], $amenValues['piscina'],
        intval($input['propietario_id'] ?? 0) ?: null,
        $input['fecha_publicacion'] ?? null,
        $id,
    ]);

    $message = 'Propiedad actualizada correctamente.';

} else {
    // ===== CREAR =====
    // Generar código único
    $lastCode = $db->query("SELECT codigo FROM propiedades ORDER BY id DESC LIMIT 1")->fetchColumn();
    $num = 1;
    if ($lastCode && preg_match('/C(\d+)/', $lastCode, $m)) {
        $num = intval($m[1]) + 1;
    }
    $codigo = 'C' . str_pad($num, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("INSERT INTO propiedades (codigo, tipo, provincia, comuna, sector, dormitorios, banos, area_terreno, area_construida, precio_pesos, precio_uf, descripcion, estado, bodega, estacionamiento, logia, cocina_amoblada, antejardin, patio_trasero, piscina, propietario_id, fecha_publicacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $codigo,
        $input['tipo'], $input['provincia'], $input['comuna'], $input['sector'],
        intval($input['dormitorios'] ?? 0), intval($input['banos'] ?? 0),
        floatval($input['area_terreno']), floatval($input['area_construida'] ?? 0),
        intval($input['precio_pesos']), floatval($input['precio_uf']),
        $input['descripcion'] ?? '', $input['estado'] ?? 'activo',
        $amenValues['bodega'], $amenValues['estacionamiento'], $amenValues['logia'],
        $amenValues['cocina_amoblada'], $amenValues['antejardin'],
        $amenValues['patio_trasero'], $amenValues['piscina'],
        intval($input['propietario_id'] ?? 0) ?: null,
        $input['fecha_publicacion'] ?? date('Y-m-d'),
    ]);

    $id = $db->lastInsertId();
    $message = "Propiedad creada con código $codigo.";
}

// Procesar fotos subidas
if (!empty($_FILES['fotografias'])) {
    $uploadDir = __DIR__ . '/../../uploads/propiedades/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $files = $_FILES['fotografias'];
    $count = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < min($count, 10); $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $err  = is_array($files['error']) ? $files['error'][$i] : $files['error'];

        if ($err !== UPLOAD_ERR_OK) continue;

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = "prop_{$id}_{$i}_" . time() . ".{$ext}";

        if (move_uploaded_file($tmp, $uploadDir . $filename)) {
            $esPortada = ($i === 0) ? 1 : 0;
            $stmt = $db->prepare("INSERT INTO propiedades_fotos (propiedad_id, ruta, es_portada, orden) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, 'uploads/propiedades/' . $filename, $esPortada, $i]);
        }
    }
}

jsonResponse(true, ['id' => $id], $message, $id > 0 ? 200 : 201);
