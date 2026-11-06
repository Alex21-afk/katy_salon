<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

/**
 * Las peticiones del JS público (fetch) esperan JSON incluso cuando algo falla.
 */
function wants_json(): bool
{
    return isset($_SERVER['HTTP_X_CSRF_TOKEN']) || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

function fail(string $message, int $status): never
{
    if (wants_json()) {
        json_response(['error' => $message], $status);
    }
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    exit('<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Isabella Lashes & Studio</title><body style="font-family:sans-serif;max-width:520px;margin:15vh auto;padding:0 16px;text-align:center"><p>' . e($message) . '</p><p><a href="' . e(app_url()) . '">Volver al inicio</a></p></body></html>');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        fail('Tu sesión venció. Recarga la página e inténtalo otra vez.', 419);
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function start_admin_session(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_last_seen'] = time();
}

function end_admin_session(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_last_seen']);
    session_regenerate_id(true);
}

function require_admin(int $idleMinutes = 120): void
{
    $lastSeen = (int) ($_SESSION['admin_last_seen'] ?? 0);
    if (empty($_SESSION['admin_id']) || time() - $lastSeen > $idleMinutes * 60) {
        end_admin_session();
        redirect('admin/login');
    }
    $_SESSION['admin_last_seen'] = time();
}

/**
 * Deja solo dígitos y agrega el código de Perú a los celulares de 9 dígitos,
 * para que "987 654 321", "+51 987654321" y "51987654321" sean la misma clienta.
 */
function normalize_whatsapp(string $value): string
{
    $digits = preg_replace('/\D+/', '', $value);
    if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
        $digits = '51' . $digits;
    }
    return $digits;
}

function valid_whatsapp(string $digits): bool
{
    return (bool) preg_match('/^\d{8,15}$/', $digits);
}

/** Bytes que PHP acepta en una subida, según upload_max_filesize y post_max_size. */
function php_upload_limit(): int
{
    $toBytes = static function (string $value): int {
        $value = trim($value);
        $number = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    };
    $limits = array_filter([$toBytes((string) ini_get('upload_max_filesize')), $toBytes((string) ini_get('post_max_size'))]);
    return $limits ? min($limits) : PHP_INT_MAX;
}

function format_megabytes(int $bytes): string
{
    return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1, '.', ''), '0'), '.') . ' MB';
}

/**
 * Valida el tipo real del archivo y lo guarda con nombre aleatorio. Devuelve el nombre guardado.
 *
 * @param array<string, string> $allowed MIME => extensión
 */
function store_upload(mixed $upload, array $allowed, int $maxBytes, string $directory): string
{
    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Elige un archivo.');
    }
    if (in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $upload['size'] > $maxBytes) {
        throw new RuntimeException('El archivo pesa más del máximo permitido (' . format_megabytes($maxBytes) . ').');
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo recibir el archivo. Inténtalo nuevamente.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato no permitido.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar la carpeta de archivos.');
    }
    $filename = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }
    return $filename;
}

/**
 * Envía un archivo con ETag (revalidación) y soporte de rangos, necesario para que
 * los videos se reproduzcan y adelanten en Safari/iPhone.
 */
function send_file(string $path, string $mime, string $cacheControl): never
{
    // Liberar la sesión: un video largo no debe bloquear las demás pestañas de la usuaria.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $size = (int) filesize($path);
    $etag = '"' . dechex((int) filemtime($path)) . '-' . dechex($size) . '"';
    header('Cache-Control: ' . $cacheControl);
    header('ETag: ' . $etag);
    header('Accept-Ranges: bytes');
    header('X-Content-Type-Options: nosniff');
    if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
        http_response_code(304);
        exit;
    }
    $start = 0;
    $end = $size - 1;
    if (preg_match('/^bytes=(\d*)-(\d*)$/', (string) ($_SERVER['HTTP_RANGE'] ?? ''), $range) && ($range[1] !== '' || $range[2] !== '')) {
        if ($range[1] === '') {
            $start = max(0, $size - (int) $range[2]);
        } else {
            $start = (int) $range[1];
            if ($range[2] !== '') {
                $end = min($end, (int) $range[2]);
            }
        }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . ($end - $start + 1));
    set_time_limit(0);
    $handle = fopen($path, 'rb');
    fseek($handle, $start);
    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(65536, $remaining));
        echo $chunk;
        $remaining -= strlen($chunk);
        flush();
    }
    fclose($handle);
    exit;
}

/** URL del logo, el icono o las fotos del banner configurados en el panel (o los originales si no hay). */
function brand_url(string $kind): string
{
    return app_url('marca/' . (in_array($kind, ['icono', 'hero-principal', 'hero-secundaria'], true) ? $kind : 'logo'));
}

/** Grupos de textos editables de la web (ver app/site_texts.php). */
function site_text_groups(): array
{
    static $groups = null;
    return $groups ??= require APP_ROOT . '/app/site_texts.php';
}

/** Todos los campos de texto, sin agrupar: clave => [etiqueta, por defecto, tipo, ayuda]. */
function site_text_fields(): array
{
    static $fields = null;
    return $fields ??= array_merge(...array_values(array_column(site_text_groups(), 'fields')));
}

/** Texto guardado en Configuración o, si está vacío, el texto por defecto. */
function site_text(array $settings, string $key): string
{
    $value = trim((string) ($settings[$key] ?? ''));
    return $value !== '' ? $value : (site_text_fields()[$key][1] ?? '');
}

/** Separa un texto en líneas no vacías: cada línea es un punto de una lista. */
function text_lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text) ?: []), static fn(string $line): bool => $line !== ''));
}

/**
 * Módulos de un curso, guardados como JSON.
 *
 * @return list<array{titulo: string, contenido: string}>
 */
function course_modules(?string $json): array
{
    $modules = json_decode((string) $json, true);
    return is_array($modules) ? $modules : [];
}

function money(float|int|string $amount): string
{
    return 'S/ ' . number_format((float) $amount, 2, '.', ',');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
