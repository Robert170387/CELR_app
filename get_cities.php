<?php
/**
 * API Endpoint: get_cities.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Retorna las ciudades/municipios de un departamento en formato JSON.
 *
 * Parámetros GET:
 *   state_id (int) → ID del departamento (loc_states.id)
 *
 * Respuesta exitosa:
 *   [{ "id": 1, "nombre": "Medellín" }, ...]
 *
 * Respuesta vacía o error:
 *   []
 */

// ── 1. Headers ─────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
// Bloquear acceso directo al navegador desde otros dominios (CSRF básico)
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/includes/auth.php';

if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

// ── 2. Validar y sanitizar parámetro de entrada ────────────────────────────
$stateId = isset($_GET['state_id']) ? filter_var($_GET['state_id'], FILTER_VALIDATE_INT) : false;

// Si el parámetro no existe, no es entero positivo, retornar array vacío
if ($stateId === false || $stateId <= 0) {
    echo json_encode([]);
    exit;
}

// ── 3. Conexión a la base de datos ─────────────────────────────────────────
require_once __DIR__ . '/includes/db.php';
// $pdo ya está disponible desde db.php

// ── 4. Consulta con sentencia preparada (evita SQL Injection) ──────────────
try {
    $stmt = $pdo->prepare(
        "SELECT id, name AS nombre
         FROM   loc_cities
         WHERE  state_id = :state_id
         ORDER BY name ASC"
    );
    $stmt->execute([':state_id' => $stateId]);
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Asegurar que los valores sean del tipo correcto
    $result = array_map(function ($city) {
        return [
            'id'     => (int) $city['id'],
            'nombre' => (string) $city['nombre']
        ];
    }, $cities);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Loguear el error real sin exponerlo al cliente
    error_log('[get_cities.php] Error PDO: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
}
exit;
