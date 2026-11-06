<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (APP_BASE_PATH !== '' && str_starts_with($path, APP_BASE_PATH)) {
    $path = substr($path, strlen(APP_BASE_PATH));
}
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    $public = new PublicController($config);
    $admin = new AdminController($config);
} catch (RuntimeException $exception) {
    fail('El sitio no está disponible en este momento. Inténtalo en unos minutos.', 503);
}

if ($path === '/api/horarios' && $method === 'GET') {
    $public->availableTimes();
} elseif ($path === '/media' && $method === 'GET') {
    $public->galleryImage();
} elseif ($path === '/video' && $method === 'GET') {
    $public->videoFile();
} elseif (($path === '/marca/logo' || $path === '/marca/icono') && $method === 'GET') {
    $public->brandAsset(substr($path, 7));
} elseif ($path === '/api/reservar' && $method === 'POST') {
    $public->bookAppointment();
} elseif ($path === '/api/inscripcion' && $method === 'POST') {
    $public->enroll();
} elseif ($path === '/admin/login' && $method === 'POST') {
    $admin->login();
} elseif ($path === '/admin/logout' && $method === 'POST') {
    $admin->logout();
} elseif ($path === '/admin/estado' && $method === 'POST') {
    $admin->updateAppointment();
} elseif ($path === '/admin/reprogramar' && $method === 'POST') {
    $admin->rescheduleAppointment();
} elseif ($path === '/admin/manual' && $method === 'POST') {
    $admin->manualAppointment();
} elseif ($path === '/admin/inscripcion' && $method === 'POST') {
    $admin->updateEnrollment();
} elseif ($path === '/admin/espera-eliminar' && $method === 'POST') {
    $admin->deleteWaitlistEntry();
} elseif ($path === '/admin/servicio' && $method === 'POST') {
    $admin->saveService();
} elseif ($path === '/admin/cupos' && $method === 'POST') {
    $admin->updateSeats();
} elseif ($path === '/admin/curso' && $method === 'POST') {
    $admin->saveCourse();
} elseif ($path === '/admin/bloqueo' && $method === 'POST') {
    $admin->saveBlocks();
} elseif ($path === '/admin/bloqueo-eliminar' && $method === 'POST') {
    $admin->deleteBlock();
} elseif ($path === '/admin/configuracion' && $method === 'POST') {
    $admin->saveSettings();
} elseif ($path === '/admin/galeria' && $method === 'POST') {
    $admin->saveGalleryImage();
} elseif ($path === '/admin/galeria-orden' && $method === 'POST') {
    $admin->updateGalleryImage();
} elseif ($path === '/admin/video' && $method === 'POST') {
    $admin->saveVideo();
} elseif ($path === '/admin/video-orden' && $method === 'POST') {
    $admin->updateVideo();
} elseif ($path === '/admin/marca' && $method === 'POST') {
    $admin->saveBrand();
} elseif ($path === '/admin/ingreso' && $method === 'POST') {
    $admin->saveIncome();
} elseif ($path === '/admin/ingreso-eliminar' && $method === 'POST') {
    $admin->deleteIncome();
} elseif ($path === '/admin/cobro' && $method === 'POST') {
    $admin->registerAppointmentIncome();
} elseif ($path === '/admin/voucher' && $method === 'GET') {
    $admin->viewVoucher();
} elseif ($path === '/admin/setup' && $method === 'POST') {
    $admin->setup();
} elseif ($path === '/admin' || str_starts_with($path, '/admin/')) {
    $admin->dispatch($path);
} else {
    $public->home();
}