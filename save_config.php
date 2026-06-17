<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'app/Core/Cache.php';

use App\Core\Cache;

// Protect Admin Action
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    validateCsrfToken();
    $id = $_POST['config_id'];

    // Localization
    $base_country = $_POST['base_country'] ?? 'Colombia';
    $currency = $_POST['currency'] ?? 'COP';
    $currency_symbol = $_POST['currency_symbol'] ?? '$';
    $decimal_separator = $_POST['decimal_separator'] ?? ',';
    $thousands_separator = $_POST['thousands_separator'] ?? '.';
    $decimal_count = $_POST['decimal_count'] ?? 0;

    // Fiscal
    $rete_fuente = $_POST['default_rete_fuente'] ?? 0;
    $rete_ica = $_POST['default_rete_ica'] ?? 0;
    $iva = $_POST['default_iva_percent'] ?? 0;
    $rete_iva = $_POST['default_rete_iva_percent'] ?? 0;

    // Operational
    $nacional = $_POST['ganancia_nacional_percent'] ?? 0;
    $urbano = $_POST['ganancia_urbano_percent'] ?? 0;
    $weight_unit = $_POST['weight_unit'] ?? 'Toneladas';
    $distance_unit = $_POST['distance_unit'] ?? 'Kilómetros';
    $maint_warning_kms = $_POST['maint_warning_kms'] ?? 500;
    $doc_warning_days = $_POST['doc_warning_days'] ?? 30;
    $unusual_expense_threshold = $_POST['unusual_expense_threshold'] ?? 1000000;

    // Identity
    $business_name = $_POST['business_name'] ?? null;
    $nit = $_POST['nit'] ?? null;
    $billing_resolution = $_POST['billing_resolution'] ?? null;
    $address = $_POST['address'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $email = $_POST['email'] ?? null;

    // Satrack
    $satrack_token = $_POST['satrack_token'] ?? null;
    $satrack_api_key = $_POST['satrack_api_key'] ?? null;
    $satrack_api_url = $_POST['satrack_api_url'] ?? 'https://api.satrack.com/v1/locations';

    // Handle Logo Upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = 'logo_' . time() . '.' . $ext;
            $upload_dir = 'uploads/logo/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $dest_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest_path)) {
                $logo_path = $dest_path;
            }
        }
    }

    // Update Query
    $sql = "UPDATE config SET
                base_country = ?,
                currency = ?,
                currency_symbol = ?,
                decimal_separator = ?,
                thousands_separator = ?,
                decimal_count = ?,
                default_rete_fuente = ?,
                default_rete_ica = ?,
                default_iva_percent = ?,
                default_rete_iva_percent = ?,
                ganancia_nacional_percent = ?,
                ganancia_urbano_percent = ?,
                weight_unit = ?,
                distance_unit = ?,
                maint_warning_kms = ?,
                doc_warning_days = ?,
                business_name = ?,
                nit = ?,
                billing_resolution = ?,
                address = ?,
                phone = ?,
                email = ?,
                satrack_token = ?,
                satrack_api_key = ?,
                satrack_api_url = ?,
                unusual_expense_threshold = ?";

    $params = [
        $base_country,
        $currency,
        $currency_symbol,
        $decimal_separator,
        $thousands_separator,
        $decimal_count,
        $rete_fuente,
        $rete_ica,
        $iva,
        $rete_iva,
        $nacional,
        $urbano,
        $weight_unit,
        $distance_unit,
        $maint_warning_kms,
        $doc_warning_days,
        $business_name,
        $nit,
        $billing_resolution,
        $address,
        $phone,
        $email,
        $satrack_token,
        $satrack_api_key,
        $satrack_api_url,
        $unusual_expense_threshold
    ];

    if ($logo_path) {
        $sql .= ", logo_path = ?";
        $params[] = $logo_path;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute($params);
        
        // Clear config cache after update
        Cache::delete('system_config');
        
        header("Location: config.php?status=success");
    } catch (PDOException $e) {
        echo "Error saving config: " . $e->getMessage();
    }
}
?>