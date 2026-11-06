<?php
$heroTitle = $settings['hero_titulo'] ?? 'Tu mirada, en su mejor versión';
$heroSubtitle = $settings['hero_subtitulo'] ?? 'Pestañas, cejas y formación profesional con atención personalizada en Lima.';
$whatsapp = preg_replace('/\D+/', '', $settings['whatsapp'] ?? '51999999999');
$bookingMessage = $booking ? rawurlencode('Hola Isabella, reservé una cita para ' . $booking['service'] . ' el ' . $booking['date'] . ' a las ' . $booking['time'] . '. Mi nombre es ' . $booking['name'] . '.') : '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Estudio especializado en pestañas, cejas y cursos de belleza en Lima. Reserva tu cita en Isabella Lashes & Studio.">
  <meta property="og:title" content="Isabella Lashes & Studio | Pestañas, cejas y cursos">
  <meta property="og:description" content="Una mirada hecha a tu medida. Agenda tu cita o encuentra tu próximo curso de pestañas.">
  <meta property="og:type" content="website">
  <meta name="csrf-token" content="<?= e($csrf) ?>"><meta name="app-base" content="<?= e(APP_BASE_PATH) ?>">
  <meta name="theme-color" content="#de3580">
  <title>Isabella Lashes & Studio | Pestañas, cejas y cursos en Lima</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="<?= e(brand_url('icono')) ?>"><link rel="apple-touch-icon" href="<?= e(brand_url('icono')) ?>"><link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"BeautySalon","name":"Isabella Lashes & Studio","address":{"@type":"PostalAddress","addressLocality":"Lima","addressCountry":"PE"},"sameAs":["https://www.instagram.com/isabellalashesystudio/","https://www.facebook.com/profile.php?id=100076268310373"]}
  </script>
</head>
<body>
<header class="site-header">
  <nav class="navbar navbar-expand-lg container-xl" aria-label="Navegación principal">
    <a class="brand" href="<?= e(app_url()) ?>" aria-label="Isabella Lashes & Studio, inicio"><img class="brand-logo" src="<?= e(brand_url('logo')) ?>" alt="" width="58" height="58"><span class="brand-text"><span class="brand-name">Isabella</span><small>LASHES & STUDIO</small></span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menú"><i class="bi bi-list"></i></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto gap-lg-4">
        <?php if ($videos): ?><li><a class="nav-link" href="#videos">Videos</a></li><?php endif; ?>
        <li><a class="nav-link" href="#servicios">Servicios</a></li>
        <li><a class="nav-link" href="#cursos">Cursos</a></li>
        <li><a class="nav-link" href="#galeria">Galería</a></li>
        <li><a class="nav-link" href="#contacto">Contacto</a></li>
      </ul>
      <a class="btn btn-rose nav-appointment" href="#reserva">Agendar cita <i class="bi bi-arrow-up-right"></i></a>
    </div>
  </nav>
</header>

<main>
  <section class="hero-section">
    <div class="container-xl hero-grid">
      <div class="hero-copy">
        <p class="eyebrow"><span></span> BELLEZA QUE SE SIENTE TUYA</p>
        <h1><?php $heroParts = explode(",", $heroTitle, 2); ?><?= count($heroParts) === 2 ? e($heroParts[0]) . ", <em>" . e(trim($heroParts[1])) . "</em>" : e($heroTitle) ?></h1>
        <p class="hero-description"><?= e($heroSubtitle) ?></p>
        <div class="d-flex flex-wrap gap-3 hero-actions">
          <a class="btn btn-rose" href="#reserva">Reservar mi cita <i class="bi bi-arrow-right"></i></a>
          <a class="btn btn-outline-rose" href="#cursos">Ver cursos con cupos</a>
        </div>
        <div class="hero-note"><span class="note-mark">✳</span><span>Tu estilo, tu mirada,<br>nuestro cuidado.</span></div>
      </div>
      <div class="hero-art" aria-label="Retratos de belleza">
        <div class="hero-photo hero-photo-main"><img src="<?= e(app_url('assets/img/hero-principal.jpg')) ?>" alt="Retrato de una clienta con pestañas y cejas perfiladas" width="1440" height="2450" fetchpriority="high"></div>
        <div class="hero-photo hero-photo-small"><img src="<?= e(app_url('assets/img/hero-secundaria.jpg')) ?>" alt="Clienta con look elegante sentada en el estudio" width="1439" height="2440" loading="lazy"></div>
        <div class="hero-stamp"><span>BEAUTY</span><strong>hecha<br>para ti</strong><i>✳</i></div>
        <span class="hero-orbit orbit-one"></span><span class="hero-orbit orbit-two"></span>
      </div>
    </div>
    <div class="hero-bottom container-xl"><span>LIMA, PERÚ</span><span>EST. 2021</span><span>PESTAÑAS · CEJAS · EDUCACIÓN</span></div>
  </section>
  <?php if ($videos): ?>
  <section class="videos-section section-pad" id="videos">
    <div class="container-xl">
      <div class="gallery-heading"><div><p class="eyebrow">EN MOVIMIENTO</p><h2>Mira cómo <em>trabajamos.</em></h2></div></div>
      <div class="video-grid">
        <?php foreach ($videos as $video): ?>
          <figure class="video-item"><video src="<?= e(app_url('video?file=' . rawurlencode($video['archivo']))) ?>#t=0.1" controls playsinline preload="metadata"<?= trim($video['titulo']) !== '' ? ' aria-label="' . e($video['titulo']) . '"' : '' ?>></video><?php if (trim($video['titulo']) !== ''): ?><figcaption><?= e($video['titulo']) ?></figcaption><?php endif; ?></figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="section-pad services-section" id="servicios">
    <div class="container-xl">
      <div class="section-heading">
        <div><p class="eyebrow">RITUALES PARA TI</p><h2>Un detalle que<br><em>lo cambia todo.</em></h2></div>
        <p>Servicios pensados para acompañar tus facciones y tu forma de sentirte bien. Sin moldes, con atención a cada detalle.</p>
      </div>
      <div class="row g-3 g-lg-4">
        <?php foreach ($services as $index => $service): ?>
        <div class="col-md-6 col-xl-3">
          <article class="service-card">
            <div class="service-number">0<?= $index + 1 ?> <span><?= e($service['categoria'] ?? 'BEAUTY') ?></span></div>
            <div class="service-icon"><i class="bi <?= $index % 2 === 0 ? 'bi-eye' : 'bi-stars' ?>"></i></div>
            <h3><?= e($service['nombre']) ?></h3>
            <p><?= e($service['descripcion']) ?></p>
            <div class="service-meta"><span><?= e((string) $service['duracion_minutos']) ?> MIN</span><strong><?= e(money($service['precio'])) ?></strong></div>
            <a href="#reserva" class="service-link" data-book-service="<?= (int) $service['id'] ?>">Reservar <i class="bi bi-arrow-up-right"></i></a>
          </article>
        </div>
        <?php endforeach; ?>
        <?php if (!$services): ?><div class="col-12"><p class="empty-note">Estamos preparando nuestros servicios. Escríbenos por WhatsApp para reservar.</p></div><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="courses-section section-pad" id="cursos">
    <div class="container-xl">
      <div class="section-heading courses-heading">
        <div><p class="eyebrow">ISABELLA EDUCATION</p><h2>Aprende el arte<br><em>de una mirada.</em></h2></div>
        <p>Formación cercana, práctica y profesional. Grupos pequeños para que cada técnica tenga su momento.</p>
      </div>
      <div class="row g-3 g-lg-4">
        <?php foreach ($courses as $course): $available = max(0, (int) $course['cupos_disponibles']); $total = (int) $course['cupos_totales']; $full = $available === 0; ?>
        <div class="col-lg-6">
          <article class="course-card">
            <div class="course-card-top"><span class="course-level"><?= e($course['nivel']) ?></span><span class="course-date"><i class="bi bi-calendar3"></i> <?= e(date('d.m.Y', strtotime($course['fecha_inicio']))) ?></span></div>
            <h3><?= e($course['nombre']) ?></h3><p><?= e($course['descripcion']) ?></p>
            <div class="course-details"><span><i class="bi bi-journal-check"></i> <?= (int) $course['sesiones'] ?> sesiones</span><strong><?= e(money($course['precio'])) ?></strong></div>
            <div class="seats-row"><span><?= $full ? 'Grupo completo' : $available . ' de ' . $total . ' cupos disponibles' ?></span><span><?= $full ? '0%' : (int) round(($available / max(1, $total)) * 100) . '%' ?></span></div>
            <div class="seat-track"><span style="width: <?= $full ? 100 : (int) round((($total - $available) / max(1, $total)) * 100) ?>%"></span></div>
            <?php if ($full): ?><button class="btn btn-course-outline js-course-enroll" data-course="<?= (int) $course['id'] ?>" data-waitlist="1">Unirme a la lista de espera <i class="bi bi-arrow-right"></i></button>
            <?php else: ?><button class="btn btn-course js-course-enroll" data-course="<?= (int) $course['id'] ?>" data-waitlist="0">Quiero inscribirme <i class="bi bi-arrow-right"></i></button><?php endif; ?>
          </article>
        </div>
        <?php endforeach; ?>
        <?php if (!$courses): ?><div class="col-12"><p class="empty-note">Pronto anunciaremos nuevas fechas de formación.</p></div><?php endif; ?>
      </div>
      <div class="enroll-wrap" id="inscripcion">
        <form class="enroll-form" id="enrollmentForm" enctype="multipart/form-data"><div class="visually-hidden" aria-hidden="true"><label>No completar<input name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="enroll-title"><p class="eyebrow">DA EL SIGUIENTE PASO</p><h3>Reserva tu lugar.</h3><p>Tu inscripción queda pendiente hasta validar el adelanto.</p></div>
          <input type="hidden" name="_token" value="<?= e($csrf) ?>"><input type="hidden" name="waitlist" id="waitlistFlag" value="0">
          <label class="form-field">Curso<select name="course_id" id="enrollCourse" required><option value="">Elige un curso</option><?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>" data-full="<?= (int) $course['cupos_disponibles'] <= 0 ? '1' : '0' ?>"><?= e($course['nombre']) ?><?= (int) $course['cupos_disponibles'] <= 0 ? ' · Lista de espera' : '' ?></option><?php endforeach; ?></select></label>
          <div class="form-grid"><label class="form-field">Nombre completo<input name="name" required minlength="2" autocomplete="name"></label><label class="form-field">DNI<input name="dni" required inputmode="numeric" pattern="[0-9]{8,12}"></label><label class="form-field">WhatsApp<input name="whatsapp" required inputmode="tel" autocomplete="tel"></label><label class="form-field">Correo electrónico<input type="email" name="email" required autocomplete="email"></label></div>
          <label class="form-field voucher-field" id="voucherField">Voucher de adelanto <span>Yape, Plin o transferencia · JPG, PNG o PDF · máximo 5 MB</span><input type="file" name="voucher" accept="image/jpeg,image/png,application/pdf"></label>
          <div class="form-feedback" id="enrollFeedback" role="status"></div>
          <button class="btn btn-rose" type="submit" id="enrollSubmit">Enviar solicitud <i class="bi bi-arrow-right"></i></button>
        </form>
      </div>
    </div>
  </section>

  <section class="booking-section section-pad" id="reserva">
    <div class="container-xl booking-layout">
      <div class="booking-intro"><p class="eyebrow">UN MOMENTO PARA TI</p><h2>Tu próxima cita<br><em>empieza aquí.</em></h2><p>Elige tu servicio, encuentra el horario que te acomode y nosotras nos encargamos del resto.</p><div class="booking-aside"><i class="bi bi-heart"></i><span>Confirmaremos tu cita por WhatsApp.</span></div></div>
      <div class="booking-panel">
        <?php if ($booking): ?>
          <div class="booking-success"><span class="success-icon"><i class="bi bi-check2"></i></span><p class="eyebrow">SOLICITUD RECIBIDA · #<?= (int) $booking['id'] ?></p><h3>Gracias, <?= e($booking['name']) ?>.</h3><p>Tu cita de <strong><?= e($booking['service']) ?></strong> está pendiente de confirmación para el <?= e(date('d/m/Y', strtotime($booking['date']))) ?> a las <?= e($booking['time']) ?>.</p><a class="btn btn-rose" target="_blank" rel="noopener" href="https://wa.me/<?= e($whatsapp) ?>?text=<?= $bookingMessage ?>">Enviar por WhatsApp <i class="bi bi-whatsapp"></i></a></div>
        <?php else: ?>
        <div class="booking-steps" aria-label="Pasos de reserva"><span class="step-dot active" data-step-dot="1">01</span><i></i><span class="step-dot" data-step-dot="2">02</span><i></i><span class="step-dot" data-step-dot="3">03</span></div>
        <form id="bookingForm"><div class="visually-hidden" aria-hidden="true"><label>No completar<input name="website" tabindex="-1" autocomplete="off"></label></div>
          <input type="hidden" name="_token" value="<?= e($csrf) ?>"><input type="hidden" name="time" id="bookingTime">
          <div class="booking-step active" data-step="1"><p class="step-kicker">PASO 01 / SERVICIO</p><h3>¿Qué te gustaría?</h3><label class="form-field">Selecciona un servicio<select name="service_id" id="bookingService" required><option value="">Elige una opción</option><?php foreach ($services as $service): ?><option value="<?= (int) $service['id'] ?>"><?= e($service['nombre']) ?> · <?= e(money($service['precio'])) ?></option><?php endforeach; ?></select></label><button class="btn btn-rose w-100 mt-4" type="button" data-next-step="2" disabled id="serviceNext">Elegir día <i class="bi bi-arrow-right"></i></button></div>
          <div class="booking-step" data-step="2"><p class="step-kicker">PASO 02 / DÍA Y HORARIO</p><h3>Encuentra tu momento.</h3><label class="form-field">Elige un día<input type="date" name="date" id="bookingDate" min="<?= e(date('Y-m-d')) ?>" required></label><div class="slots-heading">Horarios disponibles <span id="slotDateLabel"></span></div><div class="slot-list" id="slotList"><span class="slot-empty">Elige un día para ver horarios.</span></div><div class="d-flex gap-2 mt-4"><button class="btn btn-back" type="button" data-prev-step="1">Atrás</button><button class="btn btn-rose flex-grow-1" type="button" data-next-step="3" disabled id="bookingNext">Continuar <i class="bi bi-arrow-right"></i></button></div></div>
          <div class="booking-step" data-step="3"><p class="step-kicker">PASO 03 / TUS DATOS</p><h3>¿A quién esperamos?</h3><div class="form-grid"><label class="form-field">Nombre completo<input name="name" autocomplete="name" required minlength="2"></label><label class="form-field">WhatsApp<input name="whatsapp" autocomplete="tel" inputmode="tel" required></label></div><label class="form-field">Nota para nosotras <span>Opcional</span><textarea name="note" rows="3" maxlength="1000" placeholder="Cuéntanos si tienes alguna preferencia"></textarea></label><div class="booking-summary" id="bookingSummary"></div><div class="d-flex gap-2 mt-4"><button class="btn btn-back" type="button" data-prev-step="2">Atrás</button><button class="btn btn-rose flex-grow-1" type="submit" id="bookingSubmit">Solicitar cita <i class="bi bi-arrow-right"></i></button></div></div>
        </form>
        <?php endif; ?>
        <div class="form-feedback" id="bookingFeedback" role="status"></div>
      </div>
    </div>
  </section>

  <section class="gallery-section section-pad" id="galeria">
    <div class="container-xl">
      <div class="gallery-heading"><div><p class="eyebrow">DETALLES QUE HABLAN</p><h2>Miradas con <em>personalidad.</em></h2></div><a href="https://www.instagram.com/isabellalashesystudio/" target="_blank" rel="noopener" class="instagram-link"><i class="bi bi-instagram"></i> Ver Instagram <i class="bi bi-arrow-up-right"></i></a></div>
      <div class="gallery-grid">
        <?php
        $fallback = [
          ['https://images.unsplash.com/photo-1512316609839-ce289d3eba0a?auto=format&fit=crop&w=700&q=80', 'Mirada natural'],
          ['https://images.unsplash.com/photo-1524250502761-1ac6f2e30d43?auto=format&fit=crop&w=700&q=80', 'Belleza auténtica'],
          ['https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=700&q=80', 'Tu estilo'],
          ['https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=700&q=80', 'Una mirada especial'],
        ];
        if ($gallery): foreach ($gallery as $item): ?>
          <figure class="gallery-item"><img src="<?= e(app_url('media?file=' . rawurlencode($item['archivo']))) ?>" alt="<?= e($item['titulo']) ?>" loading="lazy"><?php if (trim($item['titulo']) !== ''): ?><figcaption><?= e($item['titulo']) ?></figcaption><?php endif; ?></figure>
        <?php endforeach; else: foreach ($fallback as $index => [$image, $alt]): ?>
          <figure class="gallery-item gallery-item-<?= $index + 1 ?>"><img src="<?= e($image) ?>" alt="<?= e($alt) ?>" loading="lazy"></figure>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer" id="contacto"><div class="container-xl footer-main"><div><a class="brand footer-brand" href="<?= e(app_url()) ?>"><img class="brand-logo" src="<?= e(brand_url('logo')) ?>" alt="" width="58" height="58"><span class="brand-text"><span class="brand-name">Isabella</span><small>LASHES & STUDIO</small></span></a><p>La belleza de sentirte<br>más tú.</p></div><div><p class="footer-label">VISÍTANOS</p><p><?= e($settings['direccion'] ?? 'Lima, Perú · Atención con cita previa') ?></p><p><?= e($settings['horario_texto'] ?? 'Lun–Vie 9:00–19:00 · Sáb 9:00–17:00') ?></p></div><div><p class="footer-label">SÍGUENOS</p><a href="https://www.instagram.com/isabellalashesystudio/" target="_blank" rel="noopener">Instagram <i class="bi bi-arrow-up-right"></i></a><a href="https://www.facebook.com/profile.php?id=100076268310373" target="_blank" rel="noopener">Facebook <i class="bi bi-arrow-up-right"></i></a></div><a class="footer-cta" href="#reserva">Tu mirada<br>te espera <i class="bi bi-arrow-up-right"></i></a></div><div class="container-xl footer-bottom"><span>© <?= date('Y') ?> Isabella Lashes & Studio</span><a href="<?= e(app_url('admin/login')) ?>">Administración</a><span>LIMA, PERÚ</span></div></footer>
<a class="whatsapp-float" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><i class="bi bi-whatsapp"></i></a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= e(app_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>