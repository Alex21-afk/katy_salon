<?php
declare(strict_types=1);

final class AdminController
{
    // Hash ficticio: hace que un correo inexistente tarde lo mismo que una contraseña incorrecta.
    private const DUMMY_HASH = '$2y$10$7diCsqd9.o8ARN5tNJcPhOYlE1503fqy2UHnI/WN6OdCJvibY9Py6';
    private const MAX_PRICE = 9999999.99;
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    public const INCOME_METHODS = ['efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'];
    public const INCOME_ORIGINS = ['cita' => 'Citas', 'curso' => 'Cursos', 'producto' => 'Productos', 'otro' => 'Otros'];

    private SalonModel $model;
    private PDO $db;

    public function __construct(private array $config)
    {
        $this->db = Database::connection($config);
        $this->model = new SalonModel($this->db, (int) ($config['minimum_booking_notice_hours'] ?? 4));
    }

    private function requireAdmin(): void
    {
        require_admin((int) ($this->config['admin_idle_minutes'] ?? 120));
        if (!$this->model->adminIsActive((int) $_SESSION['admin_id'])) {
            end_admin_session();
            redirect('admin/login');
        }
    }

    private function videoLimit(): int
    {
        return min((int) ($this->config['max_video_mb'] ?? 50) * 1024 * 1024, php_upload_limit());
    }

    /**
     * Si el envío supera post_max_size, PHP descarta todo el formulario (incluido el token)
     * y verify_csrf() mostraría un engañoso "sesión vencida".
     */
    private function rejectOversizedPost(string $tab): void
    {
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('error', 'El archivo pesa más de lo que permite el servidor (' . format_megabytes(php_upload_limit()) . ').');
            redirect('admin?tab=' . $tab);
        }
    }

    private function parseAmount(mixed $value): ?float
    {
        $amount = filter_var(str_replace(',', '.', trim((string) $value)), FILTER_VALIDATE_FLOAT);
        return $amount === false || $amount <= 0 || $amount > self::MAX_PRICE ? null : round($amount, 2);
    }

    public function dispatch(string $path): void
    {
        if ($path === '/admin/setup') {
            $this->setup();
            return;
        }
        if ($path === '/admin/login') {
            if ($this->model->hasAdmin()) {
                require APP_ROOT . '/app/views/admin/login.php';
            } else {
                redirect('admin/setup');
            }
            return;
        }
        $this->requireAdmin();
        $tab = (string) ($_GET['tab'] ?? 'agenda');
        if (!in_array($tab, ['agenda', 'cursos', 'servicios', 'clientas', 'ingresos', 'galeria', 'videos', 'configuracion'], true)) {
            $tab = 'agenda';
        }
        $date = (string) ($_GET['fecha'] ?? '');
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            $date = date('Y-m-d');
        }
        // Cada pestaña consulta solo lo que muestra.
        $monthCounts = $appointments = $counts = $blocks = $courses = $enrollments = $waitlist = [];
        $clients = $clientHistory = $services = $categories = $openingHours = $settings = $gallery = $videos = $incomes = $incomeSummary = [];
        $month = (string) ($_GET['mes'] ?? '');
        $parsedMonth = DateTimeImmutable::createFromFormat('!Y-m', $month);
        if (!$parsedMonth || $parsedMonth->format('Y-m') !== $month) {
            $month = date('Y-m');
            $parsedMonth = new DateTimeImmutable('first day of this month 00:00');
        }
        $incomeToday = 0.0;
        $videoLimit = $this->videoLimit();
        if ($tab === 'agenda') {
            $monthStart = (new DateTimeImmutable($date))->modify('first day of this month');
            $monthCounts = $this->model->monthAppointmentCounts($monthStart->format('Y-m-d'), $monthStart->modify('last day of this month')->format('Y-m-d'));
            $appointments = $this->model->appointments($date);
            $counts = $this->model->appointmentCounts($date);
            $blocks = $this->model->upcomingBlocks();
            $openingHours = $this->model->openingHours();
            $services = $this->model->servicesForAdmin();
        } elseif ($tab === 'cursos') {
            $courses = $this->model->coursesForAdmin();
            $enrollments = $this->model->enrollments();
            $waitlist = $this->model->waitlist();
        } elseif ($tab === 'servicios') {
            $services = $this->model->servicesForAdmin();
            $categories = $this->model->categories();
        } elseif ($tab === 'clientas') {
            if (isset($_GET['client'])) {
                $clientHistory = $this->model->clientHistory((int) $_GET['client']);
            } else {
                $clients = $this->model->dashboardSearch((string) ($_GET['q'] ?? ''));
            }
        } elseif ($tab === 'ingresos') {
            $from = $parsedMonth->format('Y-m-01');
            $to = $parsedMonth->format('Y-m-t');
            $incomes = $this->model->incomes($from, $to);
            $incomeSummary = $this->model->incomeSummary($from, $to);
            $incomeToday = $this->model->incomeTotalOn(date('Y-m-d'));
        } elseif ($tab === 'galeria') {
            $gallery = $this->model->galleryForAdmin();
        } elseif ($tab === 'videos') {
            $videos = $this->model->videosForAdmin();
        } else {
            $openingHours = $this->model->openingHours();
            $settings = array_merge(['whatsapp' => $this->config['whatsapp']], $this->model->settings());
        }
        $adminName = $this->model->adminName((int) $_SESSION['admin_id']);
        $csrf = csrf_token();
        require APP_ROOT . '/app/views/admin/dashboard.php';
    }

    public function setup(): void
    {
        if ($this->model->hasAdmin()) {
            redirect('admin/login');
        }
        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verify_csrf();
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || strlen($password) < 12) {
                $error = 'Ingresa nombre, correo válido y una contraseña de al menos 12 caracteres.';
            } else {
                try {
                    $this->model->createAdmin($name, $email, $password);
                    start_admin_session((int) $this->db->lastInsertId());
                    redirect('admin');
                } catch (Throwable $exception) {
                    $error = 'No se pudo crear la cuenta. Verifica que el correo no esté registrado.';
                }
            }
        }
        $csrf = csrf_token();
        require APP_ROOT . '/app/views/admin/setup.php';
    }

    public function login(): never
    {
        verify_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $identifier = ($_SERVER['REMOTE_ADDR'] ?? 'local') . '|' . mb_substr($email, 0, 150);
        if (!$this->model->loginAllowed($identifier)) {
            redirect('admin/login?error=limite');
        }
        $user = $this->model->loginUser($email);
        $valid = password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'] ?? self::DUMMY_HASH);
        if ($user && $valid) {
            start_admin_session((int) $user['id']);
            redirect('admin');
        }
        $this->model->recordLoginAttempt($identifier);
        redirect('admin/login?error=credenciales');
    }

    public function logout(): never
    {
        verify_csrf();
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?: 'Lax',
        ]);
        session_destroy();
        redirect('admin/login');
    }

    public function updateAppointment(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        try {
            $this->model->updateAppointment($id, $status);
            // Al marcar como atendida se puede registrar el cobro en el mismo paso.
            if ($status === 'atendida' && trim((string) ($_POST['amount'] ?? '')) !== '') {
                $this->recordAppointmentPayment($id);
            }
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage());
        }
        redirect('admin?tab=agenda&fecha=' . urlencode((string) ($_POST['date'] ?? date('Y-m-d'))));
    }

    private function recordAppointmentPayment(int $appointmentId): void
    {
        $amount = $this->parseAmount($_POST['amount'] ?? '');
        $method = (string) ($_POST['method'] ?? 'efectivo');
        if ($amount === null || !isset(self::INCOME_METHODS[$method])) {
            flash('error', 'El monto cobrado no es válido. Regístralo con el botón "Registrar cobro" de la cita.');
            return;
        }
        if ($this->model->registerAppointmentIncome($appointmentId, $amount, $method)) {
            flash('success', 'Cobro de ' . money($amount) . ' (' . self::INCOME_METHODS[$method] . ') registrado en Ingresos.');
        } else {
            flash('error', 'Esta cita ya tiene un cobro registrado o todavía no está atendida.');
        }
    }

    public function registerAppointmentIncome(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $this->recordAppointmentPayment((int) ($_POST['id'] ?? 0));
        redirect('admin?tab=agenda&fecha=' . urlencode((string) ($_POST['date'] ?? date('Y-m-d'))));
    }

    public function saveIncome(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $date = (string) ($_POST['date'] ?? '');
        $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $data = [
            'date' => $date,
            'concept' => mb_substr(trim((string) ($_POST['concept'] ?? '')), 0, 160),
            'amount' => $this->parseAmount($_POST['amount'] ?? ''),
            'method' => (string) ($_POST['method'] ?? ''),
            'origin' => (string) ($_POST['origin'] ?? ''),
            'note' => mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 500),
        ];
        $back = 'admin?tab=ingresos&mes=' . ($validDate ? $validDate->format('Y-m') : date('Y-m'));
        if (!$validDate || $validDate->format('Y-m-d') !== $date || $date > date('Y-m-d')) {
            flash('error', 'Elige una fecha válida (no puede ser futura).');
        } elseif (mb_strlen($data['concept']) < 2) {
            flash('error', 'Escribe un concepto, por ejemplo "Venta de sérum" o "Adelanto curso".');
        } elseif ($data['amount'] === null) {
            flash('error', 'Ingresa un monto mayor a cero.');
        } elseif (!isset(self::INCOME_METHODS[$data['method']]) || !isset(self::INCOME_ORIGINS[$data['origin']])) {
            flash('error', 'Elige el método de pago y el tipo de ingreso.');
        } else {
            $this->model->addIncome($data);
            flash('success', 'Ingreso de ' . money($data['amount']) . ' registrado.');
        }
        redirect($back);
    }

    public function deleteIncome(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $this->model->deleteIncome((int) ($_POST['id'] ?? 0));
        flash('success', 'Ingreso eliminado.');
        $month = (string) ($_POST['mes'] ?? '');
        redirect('admin?tab=ingresos' . (preg_match('/^\d{4}-\d{2}$/', $month) ? '&mes=' . $month : ''));
    }

    public function rescheduleAppointment(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $date = (string) ($_POST['new_date'] ?? '');
        $time = (string) ($_POST['new_time'] ?? '');
        $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $back = (string) ($_POST['date'] ?? date('Y-m-d'));
        if (!$validDate || $validDate->format('Y-m-d') !== $date || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            flash('error', 'Elige una fecha y hora válidas.');
            redirect('admin?tab=agenda&fecha=' . urlencode($back));
        }
        if ($date < date('Y-m-d')) {
            flash('error', 'No se puede mover una cita a una fecha pasada.');
            redirect('admin?tab=agenda&fecha=' . urlencode($back));
        }
        try {
            $this->model->rescheduleAppointment((int) ($_POST['id'] ?? 0), $date, $time);
            flash('success', 'Cita reprogramada para el ' . date('d/m', strtotime($date)) . ' a las ' . $time . '. Avísale a la clienta con el botón Escribir.');
            redirect('admin?tab=agenda&fecha=' . urlencode($date));
        } catch (RuntimeException $error) {
            flash('error', 'No se pudo reprogramar: ' . $error->getMessage());
        }
        redirect('admin?tab=agenda&fecha=' . urlencode($back));
    }

    public function manualAppointment(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $data = [
            'service_id' => (int) ($_POST['service_id'] ?? 0),
            'date' => (string) ($_POST['date'] ?? ''),
            'time' => (string) ($_POST['time'] ?? ''),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'whatsapp' => normalize_whatsapp((string) ($_POST['whatsapp'] ?? '')),
            'note' => mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 1000),
        ];
        $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['date']);
        if (!$validDate || $validDate->format('Y-m-d') !== $data['date'] || $data['date'] < date('Y-m-d') || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $data['time'])) {
            flash('error', 'Elige una fecha (desde hoy) y una hora válidas.');
            redirect('admin?tab=agenda');
        }
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 140 || !valid_whatsapp($data['whatsapp'])) {
            flash('error', 'Revisa el nombre y el WhatsApp (entre 8 y 15 dígitos).');
            redirect('admin?tab=agenda&fecha=' . urlencode($data['date']));
        }
        try {
            $this->model->book($data, true);
            flash('success', 'Cita creada y agregada a la agenda.');
        } catch (RuntimeException $error) {
            flash('error', 'No se pudo crear la cita: ' . $error->getMessage());
        } catch (Throwable $error) {
            error_log('Cita manual: ' . $error);
            flash('error', 'No se pudo crear la cita. Inténtalo nuevamente.');
        }
        redirect('admin?tab=agenda&fecha=' . urlencode($data['date']));
    }

    public function saveService(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $data = [
            'id' => (int) ($_POST['id'] ?? 0),
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT),
            'duration' => filter_var($_POST['duration'] ?? 0, FILTER_VALIDATE_INT),
            'active' => isset($_POST['active']) ? 1 : 0,
            'order' => (int) ($_POST['order'] ?? 0),
        ];
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 140 || mb_strlen($data['description']) > 500) {
            flash('error', 'El nombre debe tener entre 2 y 140 caracteres y la descripción máximo 500.');
        } elseif ($data['price'] === false || $data['price'] < 0 || $data['price'] > self::MAX_PRICE || $data['duration'] === false || $data['duration'] < 15 || $data['duration'] > 600) {
            flash('error', 'Revisa precio y duración (entre 15 y 600 minutos).');
        } elseif ($data['category_id'] > 0 && !$this->model->categoryExists($data['category_id'])) {
            flash('error', 'La categoría elegida ya no existe.');
        } else {
            $this->model->saveService($data);
            flash('success', 'Servicio guardado.');
        }
        redirect('admin?tab=servicios');
    }

    public function updateSeats(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $delta = (int) ($_POST['delta'] ?? 0) < 0 ? -1 : 1;
        $this->model->updateCourseSeats((int) ($_POST['id'] ?? 0), $delta);
        redirect('admin?tab=cursos');
    }

    public function saveCourse(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $data = [
            'id' => isset($_POST['as_new']) ? 0 : (int) ($_POST['id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'level' => mb_substr(trim((string) ($_POST['level'] ?? 'Inicial')), 0, 80),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'start_date' => (string) ($_POST['start_date'] ?? ''),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')) ?: null,
            'sessions' => filter_var($_POST['sessions'] ?? null, FILTER_VALIDATE_INT),
            'price' => filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT),
            'seats' => filter_var($_POST['seats'] ?? null, FILTER_VALIDATE_INT),
            'status' => (string) ($_POST['status'] ?? 'activo'),
        ];
        $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['start_date']);
        $validEnd = $data['end_date'] === null ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $data['end_date']);
        $endOk = $data['end_date'] === null || ($validEnd && $validEnd->format('Y-m-d') === $data['end_date'] && $data['end_date'] >= $data['start_date']);
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 160 || mb_strlen($data['description']) > 5000) {
            flash('error', 'El nombre debe tener entre 2 y 160 caracteres y la descripción máximo 5000.');
        } elseif ($data['sessions'] === false || $data['sessions'] < 1 || $data['sessions'] > 99 || $data['price'] === false || $data['price'] < 0 || $data['price'] > self::MAX_PRICE || $data['seats'] === false || $data['seats'] < 0 || $data['seats'] > 999) {
            flash('error', 'Revisa sesiones (1 a 99), precio y cupos (0 a 999).');
        } elseif (!$validDate || $validDate->format('Y-m-d') !== $data['start_date'] || !$endOk || !in_array($data['status'], ['borrador', 'activo', 'finalizado'], true)) {
            flash('error', 'Revisa las fechas del curso: la de fin no puede ser antes del inicio.');
        } else {
            try {
                $this->model->saveCourse($data);
                flash('success', $data['id'] > 0 ? 'Curso actualizado.' : 'Curso guardado.');
            } catch (RuntimeException $error) {
                flash('error', 'No se pudo guardar el curso. ' . $error->getMessage());
            } catch (Throwable $error) {
                error_log('Guardar curso: ' . $error);
                flash('error', 'No se pudo guardar el curso. Inténtalo nuevamente.');
            }
        }
        redirect('admin?tab=cursos');
    }

    private function parseDateTimeLocal(string $value): ?DateTimeImmutable
    {
        foreach (['!Y-m-d\TH:i', '!Y-m-d\TH:i:s'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value);
            if ($parsed && $parsed->format(str_replace('!', '', $format)) === $value) {
                return $parsed;
            }
        }
        return null;
    }

    public function saveBlocks(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $start = $this->parseDateTimeLocal((string) ($_POST['start'] ?? ''));
        $end = $this->parseDateTimeLocal((string) ($_POST['end'] ?? ''));
        if (!$start || !$end || $end <= $start) {
            flash('error', 'El horario de fin debe ser posterior al de inicio.');
            redirect('admin?tab=agenda');
        }
        $from = $start->format('Y-m-d H:i:s');
        $to = $end->format('Y-m-d H:i:s');
        $reason = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 180) ?: 'No disponible';
        $this->model->addBlock($from, $to, $reason);
        $affected = $this->model->activeAppointmentsBetween($from, $to);
        if ($affected > 0) {
            flash('error', 'Horario bloqueado, pero hay ' . $affected . ($affected === 1 ? ' cita activa' : ' citas activas') . ' en ese rango. Reprográmalas o cancélalas y avisa a las clientas.');
        } else {
            flash('success', 'Horario bloqueado.');
        }
        redirect('admin?tab=agenda&fecha=' . $start->format('Y-m-d'));
    }

    public function deleteBlock(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $this->model->deleteBlock((int) ($_POST['id'] ?? 0));
        flash('success', 'Bloqueo eliminado. Ese horario vuelve a estar disponible.');
        redirect('admin?tab=agenda');
    }

    public function saveSettings(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $whatsapp = normalize_whatsapp((string) ($_POST['whatsapp'] ?? ''));
        if ($whatsapp !== '' && !valid_whatsapp($whatsapp)) {
            flash('error', 'El WhatsApp debe tener entre 8 y 15 dígitos.');
            redirect('admin?tab=configuracion');
        }
        $hours = is_array($_POST['hours'] ?? null) ? $_POST['hours'] : [];
        foreach ($hours as $row) {
            if (is_array($row) && !empty($row['open']) && (string) ($row['start'] ?? '') >= (string) ($row['end'] ?? '')) {
                flash('error', 'En los días abiertos, la hora de cierre debe ser posterior a la de apertura.');
                redirect('admin?tab=configuracion');
            }
        }
        $this->model->saveOpeningHours($hours);
        $settings = [
            'whatsapp' => $whatsapp ?: $this->config['whatsapp'],
            'direccion' => mb_substr(trim((string) ($_POST['direccion'] ?? '')), 0, 250),
            'horario_texto' => mb_substr(trim((string) ($_POST['horario_texto'] ?? '')), 0, 250),
            'hero_titulo' => mb_substr(trim((string) ($_POST['hero_titulo'] ?? '')), 0, 160),
            'hero_subtitulo' => mb_substr(trim((string) ($_POST['hero_subtitulo'] ?? '')), 0, 300),
        ];
        $this->model->saveSettings($settings);
        flash('success', 'Configuración guardada.');
        redirect('admin?tab=configuracion');
    }

    public function saveGalleryImage(): never
    {
        $this->requireAdmin();
        $this->rejectOversizedPost('galeria');
        verify_csrf();
        try {
            $filename = store_upload($_FILES['image'] ?? null, self::IMAGE_TYPES, 5 * 1024 * 1024, $this->config['upload_dir']);
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage() . ' Usa una imagen JPG, PNG o WebP de máximo 5 MB.');
            redirect('admin?tab=galeria');
        }
        $title = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 140);
        $this->model->addGalleryImage($title, $filename);
        flash('success', 'Imagen agregada a la galería.');
        redirect('admin?tab=galeria');
    }

    public function updateGalleryImage(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'up' || $action === 'down') {
            $this->model->moveGalleryImage($id, $action === 'up' ? -1 : 1);
        } elseif ($action === 'delete') {
            $filename = $this->model->deleteGalleryImage($id);
            if ($filename !== null && preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/', $filename)) {
                @unlink($this->config['upload_dir'] . '/' . $filename);
            }
            flash('success', 'Foto eliminada de la galería.');
        } else {
            $this->model->updateGalleryImage($id, mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 140), isset($_POST['active']) ? 1 : 0);
            flash('success', 'Foto actualizada.');
        }
        redirect('admin?tab=galeria');
    }

    public function saveVideo(): never
    {
        $this->requireAdmin();
        $this->rejectOversizedPost('videos');
        verify_csrf();
        try {
            $filename = store_upload($_FILES['video'] ?? null, self::VIDEO_TYPES, $this->videoLimit(), $this->config['upload_dir']);
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage() . ' Usa un video MP4 o WebM de máximo ' . format_megabytes($this->videoLimit()) . '.');
            redirect('admin?tab=videos');
        }
        $this->model->addVideo(mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 140), $filename);
        flash('success', 'Video agregado.');
        redirect('admin?tab=videos');
    }

    public function updateVideo(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'up' || $action === 'down') {
            $this->model->moveVideo($id, $action === 'up' ? -1 : 1);
        } elseif ($action === 'delete') {
            $filename = $this->model->deleteVideo($id);
            if ($filename !== null && preg_match('/^[a-f0-9]{40}\.(mp4|webm)$/', $filename)) {
                @unlink($this->config['upload_dir'] . '/' . $filename);
            }
            flash('success', 'Video eliminado.');
        } else {
            $this->model->updateVideo($id, mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 140), isset($_POST['active']) ? 1 : 0);
            flash('success', 'Video actualizado.');
        }
        redirect('admin?tab=videos');
    }

    /** Sube o restaura el logo y el icono (favicon) del sitio. */
    public function saveBrand(): never
    {
        $this->requireAdmin();
        $this->rejectOversizedPost('configuracion');
        verify_csrf();
        $settings = $this->model->settings();
        $kinds = ['logo' => 'logo_archivo', 'icono' => 'icono_archivo'];
        $action = (string) ($_POST['action'] ?? 'upload');
        if (str_starts_with($action, 'reset_') && isset($kinds[substr($action, 6)])) {
            $key = $kinds[substr($action, 6)];
            $this->deleteBrandFile($settings[$key] ?? '');
            $this->model->saveSettings([$key => '']);
            flash('success', substr($action, 6) === 'logo' ? 'Se restauró el logo original.' : 'Se restauró el icono original.');
            redirect('admin?tab=configuracion');
        }
        $saved = [];
        foreach ($kinds as $field => $key) {
            if ((($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            try {
                $filename = store_upload($_FILES[$field], self::IMAGE_TYPES, 2 * 1024 * 1024, $this->config['upload_dir']);
            } catch (RuntimeException $error) {
                flash('error', ($field === 'logo' ? 'Logo: ' : 'Icono: ') . $error->getMessage() . ' Usa JPG, PNG o WebP de máximo 2 MB.');
                redirect('admin?tab=configuracion');
            }
            $this->deleteBrandFile($settings[$key] ?? '');
            $this->model->saveSettings([$key => $filename]);
            $saved[] = $field;
        }
        flash($saved ? 'success' : 'error', $saved ? 'Listo: se actualizó ' . implode(' y ', $saved) . '. Si no ves el cambio, recarga la página.' : 'Elige una imagen para el logo o el icono.');
        redirect('admin?tab=configuracion');
    }

    private function deleteBrandFile(string $filename): void
    {
        if (preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/', $filename)) {
            @unlink($this->config['upload_dir'] . '/' . $filename);
        }
    }

    public function updateEnrollment(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['aprobada', 'rechazada'], true)) {
            redirect('admin?tab=cursos');
        }
        // Monto del voucher: opcional; si se indica, se registra como ingreso del curso.
        $amount = null;
        $method = (string) ($_POST['method'] ?? 'yape');
        if ($status === 'aprobada' && trim((string) ($_POST['amount'] ?? '')) !== '') {
            $amount = $this->parseAmount($_POST['amount']);
            if ($amount === null || !isset(self::INCOME_METHODS[$method])) {
                flash('error', 'Revisa el monto del voucher y el método de pago. La inscripción no se aprobó.');
                redirect('admin?tab=cursos');
            }
        }
        try {
            $result = $this->model->updateEnrollment((int) ($_POST['id'] ?? 0), $status, $amount, $method);
            if ($status === 'aprobada') {
                flash('success', $result['income'] ? 'Inscripción aprobada y adelanto de ' . money((float) $amount) . ' (' . self::INCOME_METHODS[$method] . ') registrado en Ingresos.' : 'Inscripción aprobada.' . ($amount === null ? ' No se registró ingreso porque no indicaste el monto.' : ''));
            } else {
                flash('success', 'Inscripción rechazada.' . ($result['removed'] !== null ? ' Se quitó su ingreso de ' . money($result['removed']) . ' de Ingresos.' : ''));
            }
        } catch (RuntimeException $error) {
            flash('error', $error->getMessage());
        }
        redirect('admin?tab=cursos');
    }

    public function deleteWaitlistEntry(): never
    {
        $this->requireAdmin();
        verify_csrf();
        $this->model->deleteWaitlistEntry((int) ($_POST['id'] ?? 0));
        flash('success', 'Contacto quitado de la lista de espera.');
        redirect('admin?tab=cursos');
    }

    public function viewVoucher(): never
    {
        $this->requireAdmin();
        $filename = basename((string) ($_GET['file'] ?? ''));
        if (!preg_match('/^[a-f0-9]{40}\.(jpg|png|pdf)$/', $filename)) {
            http_response_code(404);
            exit;
        }
        $path = $this->config['upload_dir'] . '/' . $filename;
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="voucher.' . pathinfo($filename, PATHINFO_EXTENSION) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
