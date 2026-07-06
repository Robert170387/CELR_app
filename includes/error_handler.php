<?php
/**
 * Standardized Error Handling for Controllers
 * Provides consistent error handling across all controllers
 */

if (!function_exists('handleControllerError')) {
    function handleControllerError($error, $controller, $action, $redirectUrl, $errorParams = [], $logError = true)
    {
        // Log the error for debugging
        if ($logError) {
            error_log("Controller Error: $controller::$action - " . $error);
        }

        // Set session error message
        $_SESSION['error'] = $error;

        // Redirect with error parameters
        $query = http_build_query($errorParams);
        if ($query) {
            $redirectUrl .= '?' . $query;
        }

        header("Location: $redirectUrl");
        exit;
    }
}

if (!function_exists('handleApiError')) {
    function handleApiError($error, $code = 500)
    {
        // Log the error for debugging
        error_log("API Error: " . $error);

        // Return JSON error response
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $error,
            'code' => $code
        ]);
        exit;
    }
}

if (!function_exists('validateAndSanitizeInput')) {
    function validateAndSanitizeInput($input, $type = 'string')
    {
        switch ($type) {
            case 'numeric':
                return is_numeric($input) ? (float) $input : null;
            case 'integer':
                return filter_var($input, FILTER_VALIDATE_INT) ? (int) $input : null;
            case 'date':
                $date = DateTime::createFromFormat('Y-m-d', $input);
                return $date && $date->format('Y-m-d') === $input ? $input : null;
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : null;
            case 'string':
                $sanitized = trim($input);
                return $sanitized !== '' ? $sanitized : null;
            default:
                return $input;
        }
    }
}

if (!function_exists('getErrorMessage')) {
    function getErrorMessage($errorCode, $default = 'Error occurred')
    {
        $messages = [
            'validation_error' => 'Datos inválidos proporcionados.',
            'not_found' => 'Registro no encontrado.',
            'fk_constraint' => 'No se puede eliminar: El registro tiene otros datos asociados.',
            'db_error' => 'Error de base de datos. Intente nuevamente.',
            'invalid_request' => 'Solicitud inválida.',
            'unauthorized' => 'No tiene permisos para realizar esta acción.',
            'session_expired' => 'Su sesión ha expirado. Por favor inicie sesión nuevamente.',
        ];

        return $messages[$errorCode] ?? $default;
    }
}
