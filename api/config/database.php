<?php
/**
 * PNK Inmobiliaria — Conexión a Base de Datos
 * Configuración PDO para MySQL (XAMPP)
 */

// Credenciales de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');

// Detectar si estamos en local (XAMPP) o en el servidor
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1')) {
    define('DB_NAME', 'pnk_inmobiliaria'); // Nombre DB local
    define('DB_PASS', '');                 // Contraseña XAMPP local
} else {
    define('DB_NAME', 'pnk');              // Nombre DB en servidor
    define('DB_PASS', 'admin12345');       // Contraseña en servidor
}
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtener conexión PDO a la base de datos
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'Error de conexión a la base de datos.'
            ]);
            exit;
        }
    }
    return $pdo;
}

/**
 * Headers CORS y JSON para las respuestas de la API
 */
function setApiHeaders(): void {
    header('Content-Type: application/json; charset=utf-8');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Respuesta JSON estandarizada
 */
function jsonResponse(bool $ok, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    $response = ['ok' => $ok];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validar RUT chileno (formato y dígito verificador — módulo 11)
 */
function validarRUT(string $rut): bool {
    $rut = preg_replace('/[\.\-]/', '', strtoupper(trim($rut)));
    if (strlen($rut) < 2) return false;
    $body = substr($rut, 0, -1);
    $dv = substr($rut, -1);
    if (!ctype_digit($body)) return false;
    if (intval($body) < 1000000) return false;
    $sum = 0;
    $multiplier = 2;
    for ($i = strlen($body) - 1; $i >= 0; $i--) {
        $sum += intval($body[$i]) * $multiplier;
        $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
    }
    $remainder = $sum % 11;
    if ($remainder === 0) $expected = '0';
    elseif ($remainder === 1) $expected = 'K';
    else $expected = strval(11 - $remainder);
    return $dv === $expected;
}

/**
 * Validar formato de email
 */
function validarEmailFormato(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar contraseña robusta
 */
function validarPasswordRobusta(string $pass): array {
    $errors = [];
    if (strlen($pass) < 8) $errors[] = 'Mínimo 8 caracteres';
    if (!preg_match('/[A-Z]/', $pass)) $errors[] = 'Al menos 1 letra mayúscula';
    if (!preg_match('/[a-z]/', $pass)) $errors[] = 'Al menos 1 letra minúscula';
    if (!preg_match('/[0-9]/', $pass)) $errors[] = 'Al menos 1 número';
    return $errors;
}
