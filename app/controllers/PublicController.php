<?php
declare(strict_types=1);

final class PublicController
{
    private SalonModel $model;

    public function __construct(private array $config)
    {
        $this->model = new SalonModel(Database::connection($config), (int) ($config['minimum_booking_notice_hours'] ?? 4));
    }

    public function home(): void
    {
        $services = $this->model->services();
        $courses = $this->model->courses();
        $gallery = $this->model->gallery();
        try {
            $videos = $this->model->videos();
        } catch (PDOException $error) {
            // Si aún no se importó db/actualizacion_videos_ingresos.sql, la portada sigue funcionando.
            error_log('Videos no disponibles: ' . $error->getMessage());
            $videos = [];
        }
        $settings = array_merge(['whatsapp' => $this->config['whatsapp']], $this->model->settings());
        $csrf = csrf_token();
        $booking = $_SESSION['booking_success'] ?? null;
        unset($_SESSION['booking_success']);
        require APP_ROOT . '/app/views/public/home.php';
    }

    public function availableTimes(): never
    {
        try {
            $slots = $this->model->availableTimes((int) ($_GET['servicio_id'] ?? 0), (string) ($_GET['fecha'] ?? ''));
            json_response(['horarios' => $slots]);
        } catch (Throwable $error) {
            json_response(['error' => 'No se pudo consultar la disponibilidad.'], 500);
        }
    }

    public function galleryImage(): never
    {
        $filename = basename((string) ($_GET['file'] ?? ''));
        $image = preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/', $filename) ? $this->model->galleryImage($filename, !empty($_SESSION['admin_id'])) : null;
        if (!$image) {
            http_response_code(404);
            exit;
        }
        $path = $this->config['upload_dir'] . '/' . $filename;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(415);
            exit;
        }
        // "no-cache" + ETag: el navegador revalida cada vez, así una foto ocultada deja de verse de inmediato.
        send_file($path, $mime, (int) $image['activo'] ? 'public, no-cache' : 'private, no-store');
    }

    public function serviceImage(): never
    {
        $filename = basename((string) ($_GET['file'] ?? ''));
        $service = preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/', $filename) ? $this->model->serviceByPhoto($filename, !empty($_SESSION['admin_id'])) : null;
        $path = $this->config['upload_dir'] . '/' . $filename;
        if (!$service || !is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(415);
            exit;
        }
        send_file($path, $mime, (int) $service['activo'] ? 'public, no-cache' : 'private, no-store');
    }

    public function videoFile(): never
    {
        $filename = basename((string) ($_GET['file'] ?? ''));
        $video = preg_match('/^[a-f0-9]{40}\.(mp4|webm)$/', $filename) ? $this->model->video($filename, !empty($_SESSION['admin_id'])) : null;
        $path = $this->config['upload_dir'] . '/' . $filename;
        if (!$video || !is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = str_ends_with($filename, '.webm') ? 'video/webm' : 'video/mp4';
        send_file($path, $mime, (int) $video['activo'] ? 'public, no-cache' : 'private, no-store');
    }

    /** Logo, icono o foto del banner subidos desde el panel; si no hay, la imagen original del proyecto. */
    public function brandAsset(string $kind): never
    {
        [$setting, $original] = [
            'logo' => ['logo_archivo', 'logo.jpg'],
            'icono' => ['icono_archivo', 'logo.jpg'],
            'hero-principal' => ['hero_principal_archivo', 'hero-principal.jpg'],
            'hero-secundaria' => ['hero_secundaria_archivo', 'hero-secundaria.jpg'],
        ][$kind];
        $filename = (string) ($this->model->settings()[$setting] ?? '');
        $path = $this->config['upload_dir'] . '/' . $filename;
        if (!preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/', $filename) || !is_file($path)) {
            $path = APP_ROOT . '/public/assets/img/' . $original;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(415);
            exit;
        }
        send_file($path, $mime, 'public, no-cache');
    }

    private function guardPublicForm(string $action, int $maxPerHour): void
    {
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            json_response(['error' => 'Revisa tus datos e inténtalo nuevamente.'], 422);
        }
        if (!$this->model->attemptsAllowed($this->formIdentifier($action), $maxPerHour, 60)) {
            json_response(['error' => 'Recibimos varias solicitudes desde tu conexión. Escríbenos por WhatsApp o inténtalo en una hora.'], 429);
        }
    }

    private function formIdentifier(string $action): string
    {
        return $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local');
    }

    public function bookAppointment(): never
    {
        verify_csrf();
        $this->guardPublicForm('reserva', 5);
        $data = [
            'service_id' => filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT),
            'date' => trim((string) ($_POST['date'] ?? '')),
            'time' => trim((string) ($_POST['time'] ?? '')),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'whatsapp' => normalize_whatsapp((string) ($_POST['whatsapp'] ?? '')),
            'note' => mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 1000),
        ];
        if (!$data['service_id'] || mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 140 || !valid_whatsapp($data['whatsapp']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date']) || !preg_match('/^\d{2}:\d{2}$/', $data['time'])) {
            json_response(['error' => 'Revisa tus datos e inténtalo nuevamente.'], 422);
        }
        try {
            $id = $this->model->book($data);
            $this->model->recordLoginAttempt($this->formIdentifier('reserva'));
            $service = $this->model->service((int) $data['service_id']);
            $_SESSION['booking_success'] = [
                'id' => $id,
                'name' => $data['name'],
                'service' => $service['nombre'],
                'date' => $data['date'],
                'time' => $data['time'],
                'whatsapp' => $data['whatsapp'],
            ];
            json_response(['ok' => true, 'redirect' => app_url('') . '#reserva']);
        } catch (RuntimeException $error) {
            json_response(['error' => $error->getMessage()], 409);
        } catch (Throwable $error) {
            json_response(['error' => 'No pudimos registrar tu cita. Inténtalo más tarde.'], 500);
        }
    }

    public function enroll(): never
    {
        verify_csrf();
        $this->guardPublicForm('inscripcion', 4);
        $data = [
            'course_id' => filter_var($_POST['course_id'] ?? null, FILTER_VALIDATE_INT),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'dni' => trim((string) ($_POST['dni'] ?? '')),
            'whatsapp' => normalize_whatsapp((string) ($_POST['whatsapp'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
        ];
        if (!$data['course_id'] || mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 140 || !preg_match('/^\d{8,12}$/', $data['dni']) || !valid_whatsapp($data['whatsapp']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 190) {
            json_response(['error' => 'Revisa los datos ingresados.'], 422);
        }
        if (($_POST['waitlist'] ?? '') === '1') {
            try {
                $this->model->joinWaitlist($data);
                $this->model->recordLoginAttempt($this->formIdentifier('inscripcion'));
                json_response(['ok' => true, 'message' => 'Listo, te avisaremos si se libera un cupo.']);
            } catch (RuntimeException $error) {
                json_response(['error' => $error->getMessage()], 422);
            }
        }
        $upload = $_FILES['voucher'] ?? null;
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) {
            json_response(['error' => 'Adjunta el voucher en JPG, PNG o PDF (máximo 5 MB).'], 422);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        if (!isset($extensions[$mime])) {
            json_response(['error' => 'El archivo debe ser JPG, PNG o PDF.'], 422);
        }
        $directory = $this->config['upload_dir'];
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            json_response(['error' => 'No se pudo guardar el voucher.'], 500);
        }
        $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $filename)) {
            json_response(['error' => 'No se pudo guardar el voucher.'], 500);
        }
        try {
            $this->model->enroll($data, $filename);
            $this->model->recordLoginAttempt($this->formIdentifier('inscripcion'));
            json_response(['ok' => true, 'message' => 'Recibimos tu inscripción. Te contactaremos cuando validemos el voucher.']);
        } catch (RuntimeException $error) {
            @unlink($directory . '/' . $filename);
            json_response(['error' => $error->getMessage()], 409);
        } catch (Throwable $error) {
            @unlink($directory . '/' . $filename);
            json_response(['error' => 'No pudimos registrar la inscripción. Inténtalo nuevamente.'], 500);
        }
    }
}