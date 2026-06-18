<?php
/**
 * Handler: guardar / actualizar socio
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: socios.php');
    exit;
}

validateCsrfToken();

$s = fn($k) => trim($_POST[$k] ?? '') ?: null;

$socioId = (int)($_POST['socio_id'] ?? 0);
$nombre  = trim($_POST['nombre'] ?? '');

if (!$nombre) {
    $redirect = $socioId > 0 ? "socio_form.php?id=$socioId&msg=error" : "socio_form.php?msg=error";
    header("Location: $redirect");
    exit;
}

$porcentaje = (float)($_POST['porcentaje_utilidad'] ?? 0);

if ($socioId > 0) {
    $active = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE socios SET
        tipo                = ?,
        nombre              = ?,
        documento           = ?,
        tipo_documento      = ?,
        telefono            = ?,
        celular             = ?,
        email               = ?,
        direccion           = ?,
        ciudad              = ?,
        departamento        = ?,
        banco               = ?,
        cuenta_bancaria     = ?,
        tipo_cuenta         = ?,
        porcentaje_utilidad = ?,
        notas               = ?,
        active              = ?
        WHERE id = ?");

    $stmt->execute([
        $s('tipo') ?? 'Persona Natural',
        $nombre,
        $s('documento'),
        $s('tipo_documento') ?? 'CC',
        $s('telefono'),
        $s('celular'),
        $s('email'),
        $s('direccion'),
        $s('ciudad'),
        $s('departamento'),
        $s('banco'),
        $s('cuenta_bancaria'),
        $s('tipo_cuenta'),
        $porcentaje,
        $s('notas'),
        $active,
        $socioId,
    ]);

    header("Location: socio_form.php?id=$socioId&msg=saved");
    exit;
} else {
    $stmt = $pdo->prepare("INSERT INTO socios (
        tipo, nombre, documento, tipo_documento,
        telefono, celular, email, direccion,
        ciudad, departamento, banco, cuenta_bancaria,
        tipo_cuenta, porcentaje_utilidad, notas, active
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)");

    $stmt->execute([
        $s('tipo') ?? 'Persona Natural',
        $nombre,
        $s('documento'),
        $s('tipo_documento') ?? 'CC',
        $s('telefono'),
        $s('celular'),
        $s('email'),
        $s('direccion'),
        $s('ciudad'),
        $s('departamento'),
        $s('banco'),
        $s('cuenta_bancaria'),
        $s('tipo_cuenta'),
        $porcentaje,
        $s('notas'),
    ]);

    header("Location: socios.php?msg=saved");
    exit;
}
