<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
// En Windows, dirname('/index.php') devuelve '\'.
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
if (str_ends_with($scriptDirectory, '/public')) {
    $scriptDirectory = substr($scriptDirectory, 0, -7);
}
define('APP_BASE_PATH', $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : $scriptDirectory);

$configPath = APP_ROOT . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Falta config.php. Copia config.example.php y completa los datos de MySQL.');
}
$config = require $configPath;
date_default_timezone_set($config['timezone'] ?? 'America/Lima');

if (empty($config['debug'])) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https://images.unsplash.com; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/app/models/Database.php';
require APP_ROOT . '/app/models/SalonModel.php';
require APP_ROOT . '/app/controllers/PublicController.php';
require APP_ROOT . '/app/controllers/AdminController.php';

set_exception_handler(static function (Throwable $exception) use ($config): void {
    error_log('Error no controlado: ' . $exception);
    $message = 'Ocurrió un problema al procesar tu solicitud. Inténtalo nuevamente en unos minutos.';
    if (!empty($config['debug'])) {
        $message .= ' [' . get_class($exception) . ': ' . $exception->getMessage() . ']';
    }
    if (headers_sent()) {
        echo e($message);
        return;
    }
    fail($message, 500);
});
