<?php
/*
 * Generates the OpenAPI (JSON) document from the PHP 8 attributes in doc_setup.php (API info, schemas)
 * and paths/*.php (one class per route file). Swagger UI (index.html) reads it.
 */
// Deprecation notices from libraries would break the JSON output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

// Base URL of the API, e.g. http://localhost:8000/backend/ (works locally and after deployment)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$path = preg_replace('#public/v1/docs/swagger\.php$#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
define('API_BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $path);

// swagger-php reads the attributes with reflection, so the documentation classes must be loaded first
$files = array_merge([__DIR__ . '/doc_setup.php'], glob(__DIR__ . '/paths/*.php'));
foreach ($files as $file) {
    require_once $file;
}

$openapi = (new \OpenApi\Generator())->generate($files);

header('Content-Type: application/json');
echo $openapi->toJson();
