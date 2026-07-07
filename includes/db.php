<?php
$db_host   = getenv('DB_HOST') ?: 'localhost';
$db_port   = getenv('DB_PORT') ?: '3306';
$db_name   = getenv('DB_NAME') ?: 'celr_app';
$db_user   = getenv('DB_USER') ?: 'root';
$db_pass   = getenv('DB_PASS') ?: '';
$db_ssl    = getenv('DB_SSL')  ?: 'false';
$db_ssl_ca = getenv('DB_SSL_CA') ?: '';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($db_ssl === 'true') {
        if (!empty($db_ssl_ca)) {
            $ca_file = '/tmp/aiven-ca.pem';
            $ca_content = str_replace(['\n', '\\n'], "\n", $db_ssl_ca);
            file_put_contents($ca_file, $ca_content);
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca_file;
        }
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("Ha ocurrido un error interno. Por favor contacte al administrador.");
}