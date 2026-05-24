<?php
/**
 * PNK Inmobiliaria — Estadísticas Públicas
 * GET /api/propiedades/stats.php
 * Retorna conteos para el homepage (sin autenticación)
 */
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$db = getDB();

$stats = $db->query("SELECT
    (SELECT COUNT(*) FROM propiedades WHERE estado = 'activo') as propiedades_activas,
    (SELECT COUNT(*) FROM propiedades) as propiedades_total,
    (SELECT COUNT(*) FROM usuarios WHERE rol = 'propietario') as propietarios,
    (SELECT COUNT(*) FROM usuarios WHERE rol = 'gestor') as gestores,
    (SELECT COUNT(*) FROM propiedades WHERE estado = 'vendida') as vendidas
")->fetch();

jsonResponse(true, $stats);
