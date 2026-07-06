<?php
/**
 * Shared security helpers for internal tools and file uploads.
 */

if (!function_exists('isLocalRequest')) {
    function isLocalRequest(): bool
    {
        if (php_sapi_name() === 'cli') {
            return true;
        }

        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($remote, ['127.0.0.1', '::1', 'localhost'], true);
    }
}

if (!function_exists('requireInternalToolAccess')) {
    function requireInternalToolAccess(): void
    {
        if (isLocalRequest()) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['role']) && strtolower((string) $_SESSION['role']) === 'admin') {
            return;
        }

        http_response_code(403);
        exit('Acceso restringido.');
    }
}

if (!function_exists('safeUploadFile')) {
    function safeUploadFile(
        array $file,
        string $uploadDir,
        array $allowedExtensions,
        string $prefix,
        int $maxBytes = 5242880
    ): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('No se pudo recibir el archivo.');
        }

        if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {
            throw new RuntimeException('El archivo excede el tamano permitido.');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($extension, $allowedExtensions)) {
            throw new RuntimeException('Tipo de archivo no permitido.');
        }

        $detectedMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }
        }

        $allowedMimes = (array) $allowedExtensions[$extension];
        if ($detectedMime && !in_array($detectedMime, $allowedMimes, true)) {
            throw new RuntimeException('El contenido del archivo no coincide con su extension.');
        }

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('No se pudo preparar el directorio de subida.');
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        return str_replace('\\', '/', $targetPath);
    }
}
?>
