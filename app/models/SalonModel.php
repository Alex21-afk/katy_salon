<?php
declare(strict_types=1);

final class SalonModel
{
    public function __construct(private PDO $db, private int $minimumNoticeHours = 4) {}

    public function services(): array
    {
        return $this->db->query('SELECT s.*, c.nombre AS categoria FROM servicios s LEFT JOIN categorias c ON c.id = s.categoria_id WHERE s.activo = 1 ORDER BY s.orden, s.id')->fetchAll();
    }

    public function courses(bool $activeOnly = true): array
    {
        $sql = 'SELECT c.*, c.cupos_totales - (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id AND i.estado = "aprobada") AS cupos_disponibles, COALESCE(c.fecha_fin, c.fecha_inicio) < ? AS terminado FROM cursos c';
        $params = [date('Y-m-d')];
        if ($activeOnly) {
            $sql .= ' WHERE c.estado = "activo" AND COALESCE(c.fecha_fin, c.fecha_inicio) >= ?';
            $params[] = date('Y-m-d');
        }
        $sql .= ' ORDER BY c.fecha_inicio, c.id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function gallery(): array
    {
        return $this->db->query('SELECT * FROM galeria WHERE activo = 1 ORDER BY orden, id LIMIT 12')->fetchAll();
    }

    public function galleryForAdmin(): array
    {
        return $this->db->query('SELECT * FROM galeria ORDER BY orden, id')->fetchAll();
    }

    public function galleryImage(string $filename, bool $includeHidden = false): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM galeria WHERE archivo = ?' . ($includeHidden ? '' : ' AND activo = 1'));
        $stmt->execute([$filename]);
        return $stmt->fetch() ?: null;
    }

    public function addGalleryImage(string $title, string $filename): void
    {
        $stmt = $this->db->prepare('INSERT INTO galeria (titulo, archivo, orden) SELECT ?, ?, COALESCE(MAX(orden), 0) + 1 FROM galeria');
        $stmt->execute([$title, $filename]);
    }

    public function updateGalleryImage(int $id, string $title, int $active): void
    {
        $stmt = $this->db->prepare('UPDATE galeria SET titulo = ?, activo = ? WHERE id = ?');
        $stmt->execute([$title, $active, $id]);
    }

    public function deleteGalleryImage(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT archivo FROM galeria WHERE id = ?');
        $stmt->execute([$id]);
        $filename = $stmt->fetchColumn();
        if ($filename === false) {
            return null;
        }
        $delete = $this->db->prepare('DELETE FROM galeria WHERE id = ?');
        $delete->execute([$id]);
        return (string) $filename;
    }

    public function moveGalleryImage(int $id, int $delta): void
    {
        $this->moveItem('galeria', $id, $delta);
    }

    /** Intercambia un elemento con su vecino y renumera el orden de toda la tabla. */
    private function moveItem(string $table, int $id, int $delta): void
    {
        $table = $table === 'videos' ? 'videos' : 'galeria';
        $ids = array_map('intval', $this->db->query('SELECT id FROM ' . $table . ' ORDER BY orden, id')->fetchAll(PDO::FETCH_COLUMN));
        $index = array_search($id, $ids, true);
        if ($index === false) {
            return;
        }
        $target = max(0, min(count($ids) - 1, $index + ($delta < 0 ? -1 : 1)));
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $stmt = $this->db->prepare('UPDATE ' . $table . ' SET orden = ? WHERE id = ?');
        foreach ($ids as $order => $itemId) {
            $stmt->execute([$order, $itemId]);
        }
    }

    public function videos(): array
    {
        return $this->db->query('SELECT * FROM videos WHERE activo = 1 ORDER BY orden, id LIMIT 12')->fetchAll();
    }

    public function videosForAdmin(): array
    {
        return $this->db->query('SELECT * FROM videos ORDER BY orden, id')->fetchAll();
    }

    public function video(string $filename, bool $includeHidden = false): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE archivo = ?' . ($includeHidden ? '' : ' AND activo = 1'));
        $stmt->execute([$filename]);
        return $stmt->fetch() ?: null;
    }

    public function addVideo(string $title, string $filename): void
    {
        $stmt = $this->db->prepare('INSERT INTO videos (titulo, archivo, orden) SELECT ?, ?, COALESCE(MAX(orden), 0) + 1 FROM videos');
        $stmt->execute([$title, $filename]);
    }

    public function updateVideo(int $id, string $title, int $active): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET titulo = ?, activo = ? WHERE id = ?');
        $stmt->execute([$title, $active, $id]);
    }

    public function moveVideo(int $id, int $delta): void
    {
        $this->moveItem('videos', $id, $delta);
    }

    public function deleteVideo(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT archivo FROM videos WHERE id = ?');
        $stmt->execute([$id]);
        $filename = $stmt->fetchColumn();
        if ($filename === false) {
            return null;
        }
        $delete = $this->db->prepare('DELETE FROM videos WHERE id = ?');
        $delete->execute([$id]);
        return (string) $filename;
    }

    public function incomes(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ingresos WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC, id DESC');
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }

    /** Totales del rango, por método de pago y por origen. */
    public function incomeSummary(string $from, string $to): array
    {
        $summary = ['total' => 0.0, 'registros' => 0, 'metodo' => [], 'origen' => []];
        foreach (['metodo', 'origen'] as $column) {
            $stmt = $this->db->prepare('SELECT ' . $column . ' AS clave, SUM(monto) AS total, COUNT(*) AS registros FROM ingresos WHERE fecha BETWEEN ? AND ? GROUP BY ' . $column . ' ORDER BY total DESC');
            $stmt->execute([$from, $to]);
            foreach ($stmt->fetchAll() as $row) {
                $summary[$column][$row['clave']] = (float) $row['total'];
                if ($column === 'metodo') {
                    $summary['total'] += (float) $row['total'];
                    $summary['registros'] += (int) $row['registros'];
                }
            }
        }
        return $summary;
    }

    public function incomeTotalOn(string $date): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(monto), 0) FROM ingresos WHERE fecha = ?');
        $stmt->execute([$date]);
        return (float) $stmt->fetchColumn();
    }

    public function addIncome(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO ingresos (fecha, concepto, monto, metodo, origen, nota) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$data['date'], $data['concept'], $data['amount'], $data['method'], $data['origin'], $data['note'] ?: null]);
    }

    /**
     * Registra el cobro de una cita atendida. Cada cita se cobra una sola vez:
     * devuelve false si ya tenía un cobro o no está atendida.
     */
    public function registerAppointmentIncome(int $appointmentId, float $amount, string $method): bool
    {
        $stmt = $this->db->prepare('INSERT INTO ingresos (fecha, concepto, monto, metodo, origen, cita_id) SELECT c.fecha, LEFT(CONCAT(s.nombre, " · ", cl.nombre), 160), ?, ?, "cita", c.id FROM citas c JOIN servicios s ON s.id = c.servicio_id JOIN clientas cl ON cl.id = c.clienta_id WHERE c.id = ? AND c.estado = "atendida" AND NOT EXISTS (SELECT 1 FROM ingresos i WHERE i.cita_id = c.id)');
        $stmt->execute([$amount, $method, $appointmentId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteIncome(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM ingresos WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function settings(): array
    {
        return $this->db->query('SELECT clave, valor FROM configuracion')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function availableTimes(int $serviceId, string $date): array
    {
        $service = $this->service($serviceId);
        if (!$service || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return [];
        }
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$day || $day->format('Y-m-d') !== $date || $day < new DateTimeImmutable('today')) {
            return [];
        }
        $stmt = $this->db->prepare('SELECT abierto, hora_inicio, hora_fin FROM horarios_atencion WHERE dia_semana = ?');
        $stmt->execute([(int) $day->format('w')]);
        $hours = $stmt->fetch();
        if (!$hours || !(int) $hours['abierto']) {
            return [];
        }
        $open = new DateTimeImmutable($date . ' ' . $hours['hora_inicio']);
        $close = new DateTimeImmutable($date . ' ' . $hours['hora_fin']);
        $duration = (int) $service['duracion_minutos'];
        $nowPlusNotice = new DateTimeImmutable('now +' . max(0, $this->minimumNoticeHours) . ' hours');
        $query = $this->db->prepare('SELECT hora_inicio, hora_fin FROM citas WHERE fecha = ? AND estado <> "cancelada"');
        $query->execute([$date]);
        $appointments = $query->fetchAll();
        $query = $this->db->prepare('SELECT CASE WHEN DATE(inicio) < ? THEN "00:00:00" ELSE TIME(inicio) END AS hora_inicio, CASE WHEN DATE(fin) > ? THEN "23:59:59" ELSE TIME(fin) END AS hora_fin FROM bloqueos WHERE DATE(inicio) <= ? AND DATE(fin) >= ?');
        $query->execute([$date, $date, $date, $date]);
        $blocks = $query->fetchAll();
        $slots = [];
        for ($start = $open; $start->modify('+' . $duration . ' minutes') <= $close; $start = $start->modify('+30 minutes')) {
            $end = $start->modify('+' . $duration . ' minutes');
            if ($start <= $nowPlusNotice) {
                continue;
            }
            $overlap = false;
            foreach (array_merge($appointments, $blocks) as $busy) {
                if ($start->format('H:i:s') < $busy['hora_fin'] && $end->format('H:i:s') > $busy['hora_inicio']) {
                    $overlap = true;
                    break;
                }
            }
            if (!$overlap) {
                $slots[] = $start->format('H:i');
            }
        }
        return $slots;
    }

    public function service(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM servicios WHERE id = ? AND activo = 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Desde el panel ($byAdmin) se omiten la anticipación mínima y el horario de atención,
     * pero se siguen respetando las otras citas y los bloqueos.
     */
    public function book(array $data, bool $byAdmin = false): int
    {
        $service = $this->service((int) $data['service_id']);
        if (!$service) {
            throw new RuntimeException('El servicio seleccionado ya no está disponible.');
        }
        $date = $data['date'];
        $time = $data['time'];
        $lockName = 'salon_' . md5($date);
        $lock = $this->db->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new RuntimeException('No pudimos verificar el horario. Inténtalo nuevamente.');
        }
        try {
            $this->db->beginTransaction();
            $start = new DateTimeImmutable($date . ' ' . $time);
            $end = $start->modify('+' . (int) $service['duracion_minutos'] . ' minutes');
            if ($byAdmin) {
                if ($end->format('Y-m-d') !== $date) {
                    throw new RuntimeException('La cita debe terminar el mismo día.');
                }
                $this->assertSlotFree($date, $start, $end);
            } elseif (!in_array($time, $this->availableTimes((int) $data['service_id'], $date), true)) {
                throw new RuntimeException('Ese horario acaba de ocuparse. Elige otro, por favor.');
            }
            $client = $this->db->prepare('INSERT INTO clientas (nombre, whatsapp) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nombre = VALUES(nombre)');
            $client->execute([$data['name'], $data['whatsapp']]);
            $clientId = (int) $this->db->lastInsertId();
            if ($clientId === 0) {
                $lookup = $this->db->prepare('SELECT id FROM clientas WHERE whatsapp = ?');
                $lookup->execute([$data['whatsapp']]);
                $clientId = (int) $lookup->fetchColumn();
            }
            $stmt = $this->db->prepare('INSERT INTO citas (servicio_id, clienta_id, fecha, hora_inicio, hora_fin, nota) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$service['id'], $clientId, $date, $start->format('H:i:s'), $end->format('H:i:s'), $data['note'] ?: null]);
            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        } finally {
            $release = $this->db->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        }
    }

    public function enroll(array $data, ?string $voucher): int
    {
        $stmt = $this->db->prepare('SELECT cupos_totales, estado, COALESCE(fecha_fin, fecha_inicio) AS ultimo_dia FROM cursos WHERE id = ? FOR UPDATE');
        $this->db->beginTransaction();
        try {
            $stmt->execute([(int) $data['course_id']]);
            $course = $stmt->fetch();
            if (!$course || $course['estado'] !== 'activo' || $course['ultimo_dia'] < date('Y-m-d')) {
                throw new RuntimeException('Este curso no está disponible.');
            }
            $count = $this->db->prepare('SELECT COUNT(*) FROM inscripciones WHERE curso_id = ? AND estado = "aprobada"');
            $count->execute([(int) $data['course_id']]);
            if ((int) $count->fetchColumn() >= (int) $course['cupos_totales']) {
                throw new RuntimeException('El curso se acaba de llenar. Puedes unirte a la lista de espera.');
            }
            $insert = $this->db->prepare('INSERT INTO inscripciones (curso_id, nombre, dni, whatsapp, correo, voucher) VALUES (?, ?, ?, ?, ?, ?)');
            $insert->execute([(int) $data['course_id'], $data['name'], $data['dni'], $data['whatsapp'], $data['email'], $voucher]);
            $this->db->commit();
            return (int) $this->db->lastInsertId();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function joinWaitlist(array $data): void
    {
        $course = $this->db->prepare('SELECT c.cupos_totales - (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id = c.id AND i.estado = "aprobada") AS libres FROM cursos c WHERE c.id = ? AND c.estado = "activo" AND COALESCE(c.fecha_fin, c.fecha_inicio) >= ?');
        $course->execute([(int) $data['course_id'], date('Y-m-d')]);
        $free = $course->fetchColumn();
        if ($free === false) {
            throw new RuntimeException('No encontramos ese curso.');
        }
        if ((int) $free > 0) {
            throw new RuntimeException('Este curso todavía tiene cupos. Puedes inscribirte directamente con tu voucher.');
        }
        $exists = $this->db->prepare('SELECT COUNT(*) FROM lista_espera WHERE curso_id = ? AND whatsapp = ?');
        $exists->execute([(int) $data['course_id'], $data['whatsapp']]);
        if ((int) $exists->fetchColumn() > 0) {
            throw new RuntimeException('Ya estás en la lista de espera de este curso. Te avisaremos si se libera un cupo.');
        }
        $stmt = $this->db->prepare('INSERT INTO lista_espera (curso_id, nombre, whatsapp, correo) VALUES (?, ?, ?, ?)');
        $stmt->execute([(int) $data['course_id'], $data['name'], $data['whatsapp'], $data['email']]);
    }

    public function waitlist(): array
    {
        return $this->db->query('SELECT e.*, c.nombre AS curso FROM lista_espera e JOIN cursos c ON c.id = e.curso_id ORDER BY c.fecha_inicio, e.creado_en')->fetchAll();
    }

    public function deleteWaitlistEntry(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM lista_espera WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function appointments(string $date): array
    {
        $stmt = $this->db->prepare('SELECT c.*, s.nombre AS servicio, s.precio, cl.nombre AS clienta, cl.whatsapp, i.monto AS cobrado, i.metodo AS metodo_cobro FROM citas c JOIN servicios s ON s.id = c.servicio_id JOIN clientas cl ON cl.id = c.clienta_id LEFT JOIN ingresos i ON i.cita_id = c.id WHERE c.fecha = ? ORDER BY c.hora_inicio');
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    public function appointmentCounts(string $date): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total, SUM(estado = "pendiente") AS pendientes FROM citas WHERE fecha = ? AND estado <> "cancelada"');
        $stmt->execute([$date]);
        $counts = $stmt->fetch();
        $counts['vouchers'] = (int) $this->db->query('SELECT COUNT(*) FROM inscripciones WHERE estado = "pendiente"')->fetchColumn();
        return $counts;
    }

    public function rescheduleAppointment(int $id, string $date, string $time): void
    {
        // Se conserva la duración original de la cita, aunque luego haya cambiado la del servicio.
        $stmt = $this->db->prepare('SELECT id, TIME_TO_SEC(TIMEDIFF(hora_fin, hora_inicio)) DIV 60 AS duracion_minutos FROM citas WHERE id = ? AND estado IN ("pendiente", "confirmada")');
        $stmt->execute([$id]);
        $appointment = $stmt->fetch();
        if (!$appointment) {
            throw new RuntimeException('Solo se pueden reprogramar citas pendientes o confirmadas.');
        }
        $start = new DateTimeImmutable($date . ' ' . $time);
        if ($start <= new DateTimeImmutable()) {
            throw new RuntimeException('Elige una hora que todavía no haya pasado.');
        }
        $end = $start->modify('+' . (int) $appointment['duracion_minutos'] . ' minutes');
        if ($end->format('Y-m-d') !== $date) {
            throw new RuntimeException('La cita debe terminar el mismo día.');
        }
        $lockName = 'salon_' . md5($date);
        $lock = $this->db->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new RuntimeException('No pudimos verificar el horario. Inténtalo nuevamente.');
        }
        try {
            $this->assertSlotFree($date, $start, $end, $id);
            $update = $this->db->prepare('UPDATE citas SET fecha = ?, hora_inicio = ?, hora_fin = ? WHERE id = ?');
            $update->execute([$date, $start->format('H:i:s'), $end->format('H:i:s'), $id]);
        } finally {
            $release = $this->db->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        }
    }

    private function assertSlotFree(string $date, DateTimeImmutable $start, DateTimeImmutable $end, int $ignoreAppointmentId = 0): void
    {
        $overlap = $this->db->prepare('SELECT COUNT(*) FROM citas WHERE fecha = ? AND id <> ? AND estado <> "cancelada" AND hora_inicio < ? AND hora_fin > ?');
        $overlap->execute([$date, $ignoreAppointmentId, $end->format('H:i:s'), $start->format('H:i:s')]);
        if ((int) $overlap->fetchColumn() > 0) {
            throw new RuntimeException('Ese horario se cruza con otra cita.');
        }
        $blocked = $this->db->prepare('SELECT COUNT(*) FROM bloqueos WHERE inicio < ? AND fin > ?');
        $blocked->execute([$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
        if ((int) $blocked->fetchColumn() > 0) {
            throw new RuntimeException('Ese horario está bloqueado.');
        }
    }

    public function monthAppointmentCounts(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT fecha, COUNT(*) AS total, SUM(estado = "pendiente") AS pendientes FROM citas WHERE fecha BETWEEN ? AND ? AND estado <> "cancelada" GROUP BY fecha');
        $stmt->execute([$from, $to]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['fecha']] = ['total' => (int) $row['total'], 'pendientes' => (int) $row['pendientes']];
        }
        return $counts;
    }

    /**
     * Solo se avanza de estado: una cita cancelada o atendida no se reactiva,
     * porque su horario pudo haberlo tomado otra persona.
     */
    public function updateAppointment(int $id, string $status): void
    {
        $allowed = ['pendiente' => ['confirmada', 'cancelada'], 'confirmada' => ['cancelada', 'atendida']];
        $current = $this->db->prepare('SELECT estado FROM citas WHERE id = ?');
        $current->execute([$id]);
        $from = $current->fetchColumn();
        if ($from === false || !in_array($status, $allowed[$from] ?? [], true)) {
            throw new RuntimeException('Esa cita ya no se puede cambiar a ese estado.');
        }
        $stmt = $this->db->prepare('UPDATE citas SET estado = ? WHERE id = ? AND estado = ?');
        $stmt->execute([$status, $id, $from]);
    }

    public function activeAppointmentsBetween(string $start, string $end): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM citas WHERE estado IN ("pendiente", "confirmada") AND TIMESTAMP(fecha, hora_inicio) < ? AND TIMESTAMP(fecha, hora_fin) > ?');
        $stmt->execute([$end, $start]);
        return (int) $stmt->fetchColumn();
    }

    public function enrollments(): array
    {
        return $this->db->query('SELECT i.*, c.nombre AS curso, c.precio AS precio_curso, g.monto AS adelanto, g.metodo AS metodo_adelanto FROM inscripciones i JOIN cursos c ON c.id = i.curso_id LEFT JOIN ingresos g ON g.inscripcion_id = i.id ORDER BY i.creado_en DESC LIMIT 200')->fetchAll();
    }

    /**
     * Al aprobar con $amount se registra el ingreso del voucher en la misma transacción.
     * Al rechazar una inscripción ya aprobada (devolución) se elimina su ingreso.
     *
     * @return array{income: bool, removed: ?float}
     */
    public function updateEnrollment(int $id, string $status, ?float $amount = null, string $method = 'yape'): array
    {
        $result = ['income' => false, 'removed' => null];
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT curso_id, estado FROM inscripciones WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $enrollment = $stmt->fetch();
            if (!$enrollment) {
                throw new RuntimeException('No encontramos la inscripción.');
            }
            if ($status === 'aprobada' && $enrollment['estado'] !== 'aprobada') {
                $course = $this->db->prepare('SELECT cupos_totales FROM cursos WHERE id = ? FOR UPDATE');
                $course->execute([$enrollment['curso_id']]);
                $total = (int) $course->fetchColumn();
                $used = $this->db->prepare('SELECT COUNT(*) FROM inscripciones WHERE curso_id = ? AND estado = "aprobada"');
                $used->execute([$enrollment['curso_id']]);
                if ((int) $used->fetchColumn() >= $total) {
                    throw new RuntimeException('No quedan cupos para aprobar esta inscripción.');
                }
            }
            $update = $this->db->prepare('UPDATE inscripciones SET estado = ? WHERE id = ?');
            $update->execute([$status, $id]);
            if ($status === 'aprobada' && $amount !== null) {
                $income = $this->db->prepare('INSERT INTO ingresos (fecha, concepto, monto, metodo, origen, inscripcion_id) SELECT ?, LEFT(CONCAT("Curso ", c.nombre, " · ", i.nombre), 160), ?, ?, "curso", i.id FROM inscripciones i JOIN cursos c ON c.id = i.curso_id WHERE i.id = ? AND NOT EXISTS (SELECT 1 FROM ingresos g WHERE g.inscripcion_id = i.id)');
                $income->execute([date('Y-m-d'), $amount, $method, $id]);
                $result['income'] = $income->rowCount() > 0;
            }
            if ($status === 'rechazada' && $enrollment['estado'] === 'aprobada') {
                $linked = $this->db->prepare('SELECT monto FROM ingresos WHERE inscripcion_id = ?');
                $linked->execute([$id]);
                $removed = $linked->fetchColumn();
                if ($removed !== false) {
                    $this->db->prepare('DELETE FROM ingresos WHERE inscripcion_id = ?')->execute([$id]);
                    $result['removed'] = (float) $removed;
                }
            }
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function dashboardSearch(string $query): array
    {
        $stmt = $this->db->prepare('SELECT cl.*, COALESCE(t.total_citas, 0) AS total_citas, t.ultima_cita FROM clientas cl LEFT JOIN (SELECT clienta_id, COUNT(*) AS total_citas, MAX(fecha) AS ultima_cita FROM citas GROUP BY clienta_id) t ON t.clienta_id = cl.id WHERE cl.nombre LIKE ? OR cl.whatsapp LIKE ? ORDER BY cl.nombre LIMIT 30');
        $term = '%' . $query . '%';
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll();
    }

    public function clientHistory(int $id): array
    {
        $stmt = $this->db->prepare('SELECT c.*, s.nombre AS servicio, s.precio FROM citas c JOIN servicios s ON s.id = c.servicio_id WHERE c.clienta_id = ? ORDER BY c.fecha DESC, c.hora_inicio DESC');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function loginUser(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = ? AND activo = 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function loginAllowed(string $identifier): bool
    {
        return $this->attemptsAllowed($identifier, 5, 15);
    }

    public function attemptsAllowed(string $identifier, int $max, int $minutes): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM intentos_login WHERE identificador = ? AND ocurrido_en > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $stmt->execute([$identifier, $minutes]);
        return (int) $stmt->fetchColumn() < $max;
    }

    public function recordLoginAttempt(string $identifier): void
    {
        $stmt = $this->db->prepare('INSERT INTO intentos_login (identificador, ocurrido_en) VALUES (?, NOW())');
        $stmt->execute([$identifier]);
        // Limpieza ocasional: los límites solo miran la última hora.
        if (random_int(1, 50) === 1) {
            $this->db->exec('DELETE FROM intentos_login WHERE ocurrido_en < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        }
    }

    public function createAdmin(string $name, string $email, string $password): void
    {
        $stmt = $this->db->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    }

    public function hasAdmin(): bool
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
    }

    public function adminIsActive(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE id = ? AND activo = 1');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function categoryExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM categorias WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function adminName(int $id): string
    {
        $stmt = $this->db->prepare('SELECT nombre FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: 'Isabella');
    }

    public function coursesForAdmin(): array
    {
        return $this->courses(false);
    }

    public function servicesForAdmin(): array
    {
        return $this->db->query('SELECT s.*, c.nombre AS categoria FROM servicios s LEFT JOIN categorias c ON c.id = s.categoria_id ORDER BY s.orden, s.id')->fetchAll();
    }

    public function saveService(array $data): void
    {
        if ((int) $data['id'] > 0) {
            $stmt = $this->db->prepare('UPDATE servicios SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?, duracion_minutos = ?, activo = ?, orden = ? WHERE id = ?');
            $stmt->execute([$data['category_id'] ?: null, $data['name'], $data['description'], $data['price'], $data['duration'], $data['active'], $data['order'], $data['id']]);
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO servicios (categoria_id, nombre, descripcion, precio, duracion_minutos, activo, orden) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$data['category_id'] ?: null, $data['name'], $data['description'], $data['price'], $data['duration'], $data['active'], $data['order']]);
    }

    public function updateCourseSeats(int $id, int $delta): void
    {
        $stmt = $this->db->prepare('UPDATE cursos SET cupos_totales = GREATEST(cupos_totales + ?, (SELECT aprobadas FROM (SELECT COUNT(*) AS aprobadas FROM inscripciones WHERE curso_id = ? AND estado = "aprobada") AS ocupados)) WHERE id = ?');
        $stmt->execute([$delta, $id, $id]);
    }

    public function saveCourse(array $data): void
    {
        if ($data['id'] > 0) {
            $approved = $this->db->prepare('SELECT COUNT(*) FROM inscripciones WHERE curso_id = ? AND estado = "aprobada"');
            $approved->execute([$data['id']]);
            if ((int) $approved->fetchColumn() > (int) $data['seats']) {
                throw new RuntimeException('Los cupos no pueden ser menores a los aprobados.');
            }
            $stmt = $this->db->prepare('UPDATE cursos SET nombre = ?, nivel = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, sesiones = ?, precio = ?, cupos_totales = ?, estado = ? WHERE id = ?');
            $stmt->execute([$data['name'], $data['level'], $data['description'], $data['start_date'], $data['end_date'], $data['sessions'], $data['price'], $data['seats'], $data['status'], $data['id']]);
            return;
        }
        $stmt = $this->db->prepare('INSERT INTO cursos (nombre, nivel, descripcion, fecha_inicio, fecha_fin, sesiones, precio, cupos_totales, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$data['name'], $data['level'], $data['description'], $data['start_date'], $data['end_date'], $data['sessions'], $data['price'], $data['seats'], $data['status']]);
    }

    public function categories(): array
    {
        return $this->db->query('SELECT * FROM categorias ORDER BY orden, nombre')->fetchAll();
    }

    public function openingHours(): array
    {
        return $this->db->query('SELECT * FROM horarios_atencion ORDER BY dia_semana')->fetchAll();
    }

    public function saveOpeningHours(array $rows): void
    {
        $stmt = $this->db->prepare('UPDATE horarios_atencion SET abierto = ?, hora_inicio = ?, hora_fin = ? WHERE dia_semana = ?');
        foreach ($rows as $day => $row) {
            $start = is_array($row) ? ($row['start'] ?? '') : '';
            $end = is_array($row) ? ($row['end'] ?? '') : '';
            if (!is_string($start) || !is_string($end) || !preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
                continue;
            }
            $stmt->execute([!empty($row['open']) ? 1 : 0, $start . ':00', $end . ':00', (int) $day]);
        }
    }

    public function addBlock(string $start, string $end, string $reason): void
    {
        $stmt = $this->db->prepare('INSERT INTO bloqueos (inicio, fin, motivo) VALUES (?, ?, ?)');
        $stmt->execute([$start, $end, $reason]);
    }

    public function upcomingBlocks(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM bloqueos WHERE fin >= ? ORDER BY inicio');
        $stmt->execute([date('Y-m-d H:i:s')]);
        return $stmt->fetchAll();
    }

    public function deleteBlock(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM bloqueos WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function saveSettings(array $settings): void
    {
        $stmt = $this->db->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }
}