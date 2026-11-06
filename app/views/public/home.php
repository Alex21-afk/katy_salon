<?php
// Textos editables en Configuración: $t() los escapa y $tl() además respeta los saltos de línea.
$t = static fn(string $key): string => e(site_text($settings, $key));
$tl = static fn(string $key): string => nl2br(e(site_text($settings, $key)), false);
$heroTitle = site_text($settings, 'hero_titulo');
$whatsapp = preg_replace('/\D+/', '', $settings['whatsapp'] ?? '51999999999');
$instagramUrl = site_text($settings, 'instagram_url');
$facebookUrl = site_text($settings, 'facebook_url');
$bookingMessage = $booking ? rawurlencode(strtr(site_text($settings, 'exito_mensaje'), ['{servicio}' => $booking['service'], '{fecha}' => $booking['date'], '{hora}' => $booking['time'], '{nombre}' => $booking['name']])) : '';
$schema = ['@context' => 'https://schema.org', '@type' => 'BeautySalon', 'name' => site_text($settings, 'marca_completa'), 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Lima', 'addressCountry' => 'PE'], 'sameAs' => [$instagramUrl, $facebookUrl]];
$brandLink = '<span class="brand-text"><span class="brand-name">' . $t('marca_nombre') . '</span><small>' . $t('marca_lema') . '</small></span>';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= $t('meta_description') ?>">
  <meta property="og:title" content="<?= $t('og_title') ?>">
  <meta property="og:description" content="<?= $t('og_description') ?>">
  <meta property="og:type" content="website">
  <meta name="csrf-token" content="<?= e($csrf) ?>"><meta name="app-base" content="<?= e(APP_BASE_PATH) ?>">
  <meta name="theme-color" content="#de3580">
  <title><?= $t('meta_title') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="<?= e(brand_url('icono')) ?>"><link rel="apple-touch-icon" href="<?= e(brand_url('icono')) ?>"><link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</head>
<body>
<header class="site-header">
  <nav class="navbar navbar-expand-lg container-xl" aria-label="Navegación principal">
    <a class="brand" href="<?= e(app_url()) ?>" aria-label="<?= $t('marca_completa') ?>, inicio"><img class="brand-logo" src="<?= e(brand_url('logo')) ?>" alt="" width="58" height="58"><?= $brandLink ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menú"><i class="bi bi-list"></i></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto gap-lg-4">
        <?php if ($videos): ?><li><a class="nav-link" href="#videos"><?= $t('nav_videos') ?></a></li><?php endif; ?>
        <li><a class="nav-link" href="#servicios"><?= $t('nav_servicios') ?></a></li>
        <li><a class="nav-link" href="#cursos"><?= $t('nav_cursos') ?></a></li>
        <li><a class="nav-link" href="#galeria"><?= $t('nav_galeria') ?></a></li>
        <li><a class="nav-link" href="#contacto"><?= $t('nav_contacto') ?></a></li>
      </ul>
      <a class="btn btn-rose nav-appointment" href="#reserva"><?= $t('nav_cta') ?> <i class="bi bi-arrow-up-right"></i></a>
    </div>
  </nav>
</header>

<main>
  <section class="hero-section">
    <div class="container-xl hero-grid">
      <div class="hero-copy">
        <p class="eyebrow"><span></span> <?= $t('hero_eyebrow') ?></p>
        <h1><?php $heroParts = explode(",", $heroTitle, 2); ?><?= count($heroParts) === 2 ? e($heroParts[0]) . ", <em>" . e(trim($heroParts[1])) . "</em>" : e($heroTitle) ?></h1>
        <p class="hero-description"><?= $t('hero_subtitulo') ?></p>
        <div class="d-flex flex-wrap gap-3 hero-actions">
          <a class="btn btn-rose" href="#reserva"><?= $t('hero_btn_reservar') ?> <i class="bi bi-arrow-right"></i></a>
          <a class="btn btn-outline-rose" href="#cursos"><?= $t('hero_btn_cursos') ?></a>
        </div>
        <div class="hero-note"><span class="note-mark">✳</span><span><?= $tl('hero_nota') ?></span></div>
      </div>
      <div class="hero-art" aria-label="Retratos de belleza">
        <div class="hero-photo hero-photo-main"><img src="<?= e(brand_url('hero-principal')) ?>" alt="<?= $t('hero_alt_principal') ?>" width="1440" height="2450" fetchpriority="high"></div>
        <div class="hero-photo hero-photo-small"><img src="<?= e(brand_url('hero-secundaria')) ?>" alt="<?= $t('hero_alt_secundaria') ?>" width="1439" height="2440" loading="lazy"></div>
        <div class="hero-stamp"><span><?= $t('hero_sello_superior') ?></span><strong><?= $tl('hero_sello') ?></strong><i>✳</i></div>
        <span class="hero-orbit orbit-one"></span><span class="hero-orbit orbit-two"></span>
      </div>
    </div>
    <div class="hero-bottom container-xl"><span><?= $t('hero_franja_1') ?></span><span><?= $t('hero_franja_2') ?></span><span><?= $t('hero_franja_3') ?></span></div>
  </section>
  <?php if ($videos): ?>
  <section class="videos-section section-pad" id="videos">
    <div class="container-xl">
      <div class="gallery-heading"><div><p class="eyebrow"><?= $t('videos_eyebrow') ?></p><h2><?= $t('videos_titulo') ?> <em><?= $t('videos_destacado') ?></em></h2></div></div>
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
        <div><p class="eyebrow"><?= $t('servicios_eyebrow') ?></p><h2><?= $t('servicios_titulo') ?><br><em><?= $t('servicios_destacado') ?></em></h2></div>
        <p><?= $t('servicios_texto') ?></p>
      </div>
      <div class="row g-3 g-lg-4">
        <?php foreach ($services as $index => $service): ?>
        <div class="col-md-6 col-xl-3">
          <article class="service-card">
            <div class="service-number">0<?= $index + 1 ?> <span><?= e($service['categoria'] ?? site_text($settings, 'servicios_categoria')) ?></span></div>
            <?php if (!empty($service['foto'])): ?><div class="service-photo"><img src="<?= e(app_url('servicio-foto?file=' . rawurlencode($service['foto']))) ?>" alt="<?= e($service['nombre']) ?>" loading="lazy"></div>
            <?php else: ?><div class="service-icon"><i class="bi <?= $index % 2 === 0 ? 'bi-eye' : 'bi-stars' ?>"></i></div><?php endif; ?>
            <h3><?= e($service['nombre']) ?></h3>
            <p><?= e($service['descripcion']) ?></p>
            <div class="service-meta"><span><?= e((string) $service['duracion_minutos']) ?> <?= $t('servicios_minutos') ?></span><strong><?= e(money($service['precio'])) ?></strong></div>
            <a href="#reserva" class="service-link" data-book-service="<?= (int) $service['id'] ?>"><?= $t('servicios_btn') ?> <i class="bi bi-arrow-up-right"></i></a>
          </article>
        </div>
        <?php endforeach; ?>
        <?php if (!$services): ?><div class="col-12"><p class="empty-note"><?= $t('servicios_vacio') ?></p></div><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="courses-section section-pad" id="cursos">
    <div class="container-xl">
      <div class="section-heading courses-heading">
        <div><p class="eyebrow"><?= $t('cursos_eyebrow') ?></p><h2><?= $t('cursos_titulo') ?><br><em><?= $t('cursos_destacado') ?></em></h2></div>
        <p><?= $t('cursos_texto') ?></p>
      </div>
      <div class="row g-3 g-lg-4">
        <?php foreach ($courses as $course): $available = max(0, (int) $course['cupos_disponibles']); $total = (int) $course['cupos_totales']; $full = $available === 0; ?>
        <div class="col-lg-6">
          <article class="course-card">
            <div class="course-card-top"><span class="course-level"><?= e($course['nivel']) ?></span><span class="course-date"><i class="bi bi-calendar3"></i> <?= e(date('d.m.Y', strtotime($course['fecha_inicio']))) ?></span></div>
            <h3><?= e($course['nombre']) ?></h3><p><?= e($course['descripcion']) ?></p>
            <div class="course-details"><span class="course-facts"><span><i class="bi bi-journal-check"></i> <?= (int) $course['sesiones'] ?> <?= $t('curso_sesiones') ?></span><?php if (trim($course['horas'] ?? '') !== ''): ?><span><i class="bi bi-clock"></i> <?= e($course['horas']) ?></span><?php endif; ?></span><strong><?= e(money($course['precio'])) ?></strong></div>
            <div class="seats-row"><span><?= $full ? $t('curso_completo') : $available . ' de ' . $total . ' ' . $t('curso_cupos') ?></span><span><?= $full ? '0%' : (int) round(($available / max(1, $total)) * 100) . '%' ?></span></div>
            <div class="seat-track"><span style="width: <?= $full ? 100 : (int) round((($total - $available) / max(1, $total)) * 100) ?>%"></span></div>
            <button class="btn btn-course-outline course-info-button" type="button" data-bs-toggle="modal" data-bs-target="#courseInfo<?= (int) $course['id'] ?>"><?= $t('curso_btn_info') ?> <i class="bi bi-list-check"></i></button>
            <?php if ($full): ?><button class="btn btn-course-outline js-course-enroll" data-course="<?= (int) $course['id'] ?>" data-waitlist="1"><?= $t('curso_btn_espera') ?> <i class="bi bi-arrow-right"></i></button>
            <?php else: ?><button class="btn btn-course js-course-enroll" data-course="<?= (int) $course['id'] ?>" data-waitlist="0"><?= $t('curso_btn_inscribir') ?> <i class="bi bi-arrow-right"></i></button><?php endif; ?>
          </article>
        </div>
        <?php endforeach; ?>
        <?php if (!$courses): ?><div class="col-12"><p class="empty-note"><?= $t('cursos_vacio') ?></p></div><?php endif; ?>
      </div>
      <div class="enroll-wrap" id="inscripcion">
        <form class="enroll-form" id="enrollmentForm" enctype="multipart/form-data"><div class="visually-hidden" aria-hidden="true"><label>No completar<input name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="enroll-title"><p class="eyebrow"><?= $t('enroll_eyebrow') ?></p><h3><?= $t('enroll_titulo') ?></h3><p><?= $t('enroll_texto') ?></p></div>
          <input type="hidden" name="_token" value="<?= e($csrf) ?>"><input type="hidden" name="waitlist" id="waitlistFlag" value="0">
          <label class="form-field"><?= $t('enroll_curso') ?><select name="course_id" id="enrollCourse" required><option value=""><?= $t('enroll_elige') ?></option><?php foreach ($courses as $course): ?><option value="<?= (int) $course['id'] ?>" data-full="<?= (int) $course['cupos_disponibles'] <= 0 ? '1' : '0' ?>"><?= e($course['nombre']) ?><?= (int) $course['cupos_disponibles'] <= 0 ? ' · ' . $t('enroll_lista') : '' ?></option><?php endforeach; ?></select></label>
          <div class="form-grid"><label class="form-field"><?= $t('enroll_nombre') ?><input name="name" required minlength="2" autocomplete="name"></label><label class="form-field"><?= $t('enroll_dni') ?><input name="dni" required inputmode="numeric" pattern="[0-9]{8,12}"></label><label class="form-field"><?= $t('enroll_whatsapp') ?><input name="whatsapp" required inputmode="tel" autocomplete="tel"></label><label class="form-field"><?= $t('enroll_correo') ?><input type="email" name="email" required autocomplete="email"></label></div>
          <label class="form-field voucher-field" id="voucherField"><?= $t('enroll_voucher') ?> <span><?= $t('enroll_voucher_ayuda') ?></span><input type="file" name="voucher" accept="image/jpeg,image/png,application/pdf"></label>
          <div class="form-feedback" id="enrollFeedback" role="status"></div>
          <button class="btn btn-rose" type="submit" id="enrollSubmit" data-label="<?= $t('enroll_enviar') ?>" data-waitlist-label="<?= $t('enroll_enviar_espera') ?>"><?= $t('enroll_enviar') ?> <i class="bi bi-arrow-right"></i></button>
        </form>
      </div>
    </div>
  </section>

  <section class="booking-section section-pad" id="reserva">
    <div class="container-xl booking-layout">
      <div class="booking-intro"><p class="eyebrow"><?= $t('reserva_eyebrow') ?></p><h2><?= $t('reserva_titulo') ?><br><em><?= $t('reserva_destacado') ?></em></h2><p><?= $t('reserva_texto') ?></p><div class="booking-aside"><i class="bi bi-heart"></i><span><?= $t('reserva_aviso') ?></span></div></div>
      <div class="booking-panel">
        <?php if ($booking): ?>
          <div class="booking-success"><span class="success-icon"><i class="bi bi-check2"></i></span><p class="eyebrow"><?= $t('exito_eyebrow') ?> · #<?= (int) $booking['id'] ?></p><h3><?= $t('exito_gracias') ?> <?= e($booking['name']) ?>.</h3><p><?= strtr($t('exito_texto'), ['{servicio}' => '<strong>' . e($booking['service']) . '</strong>', '{fecha}' => e(date('d/m/Y', strtotime($booking['date']))), '{hora}' => e($booking['time'])]) ?></p><a class="btn btn-rose" target="_blank" rel="noopener" href="https://wa.me/<?= e($whatsapp) ?>?text=<?= $bookingMessage ?>"><?= $t('exito_btn') ?> <i class="bi bi-whatsapp"></i></a></div>
        <?php else: ?>
        <div class="booking-steps" aria-label="Pasos de reserva"><span class="step-dot active" data-step-dot="1">01</span><i></i><span class="step-dot" data-step-dot="2">02</span><i></i><span class="step-dot" data-step-dot="3">03</span></div>
        <form id="bookingForm"><div class="visually-hidden" aria-hidden="true"><label>No completar<input name="website" tabindex="-1" autocomplete="off"></label></div>
          <input type="hidden" name="_token" value="<?= e($csrf) ?>"><input type="hidden" name="time" id="bookingTime">
          <div class="booking-step active" data-step="1"><p class="step-kicker"><?= $t('paso1_etiqueta') ?></p><h3><?= $t('paso1_titulo') ?></h3><label class="form-field"><?= $t('paso1_campo') ?><select name="service_id" id="bookingService" required><option value=""><?= $t('paso1_elige') ?></option><?php foreach ($services as $service): ?><option value="<?= (int) $service['id'] ?>"><?= e($service['nombre']) ?> · <?= e(money($service['precio'])) ?></option><?php endforeach; ?></select></label><button class="btn btn-rose w-100 mt-4" type="button" data-next-step="2" disabled id="serviceNext"><?= $t('paso1_btn') ?> <i class="bi bi-arrow-right"></i></button></div>
          <div class="booking-step" data-step="2"><p class="step-kicker"><?= $t('paso2_etiqueta') ?></p><h3><?= $t('paso2_titulo') ?></h3><label class="form-field"><?= $t('paso2_campo') ?><input type="date" name="date" id="bookingDate" min="<?= e(date('Y-m-d')) ?>" required></label><div class="slots-heading"><?= $t('paso2_horarios') ?> <span id="slotDateLabel"></span></div><div class="slot-list" id="slotList"><span class="slot-empty">Elige un día para ver horarios.</span></div><div class="d-flex gap-2 mt-4"><button class="btn btn-back" type="button" data-prev-step="1"><?= $t('reserva_atras') ?></button><button class="btn btn-rose flex-grow-1" type="button" data-next-step="3" disabled id="bookingNext"><?= $t('paso2_btn') ?> <i class="bi bi-arrow-right"></i></button></div></div>
          <div class="booking-step" data-step="3"><p class="step-kicker"><?= $t('paso3_etiqueta') ?></p><h3><?= $t('paso3_titulo') ?></h3><div class="form-grid"><label class="form-field"><?= $t('paso3_nombre') ?><input name="name" autocomplete="name" required minlength="2"></label><label class="form-field"><?= $t('paso3_whatsapp') ?><input name="whatsapp" autocomplete="tel" inputmode="tel" required></label></div><label class="form-field"><?= $t('paso3_nota') ?> <span><?= $t('paso3_opcional') ?></span><textarea name="note" rows="3" maxlength="1000" placeholder="<?= $t('paso3_nota_ayuda') ?>"></textarea></label><div class="booking-summary" id="bookingSummary"></div><div class="d-flex gap-2 mt-4"><button class="btn btn-back" type="button" data-prev-step="2"><?= $t('reserva_atras') ?></button><button class="btn btn-rose flex-grow-1" type="submit" id="bookingSubmit" data-label="<?= $t('paso3_btn') ?>"><?= $t('paso3_btn') ?> <i class="bi bi-arrow-right"></i></button></div></div>
        </form>
        <?php endif; ?>
        <div class="form-feedback" id="bookingFeedback" role="status"></div>
      </div>
    </div>
  </section>

  <section class="gallery-section section-pad" id="galeria">
    <div class="container-xl">
      <div class="gallery-heading"><div><p class="eyebrow"><?= $t('galeria_eyebrow') ?></p><h2><?= $t('galeria_titulo') ?> <em><?= $t('galeria_destacado') ?></em></h2></div><a href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener" class="instagram-link"><i class="bi bi-instagram"></i> <?= $t('galeria_instagram') ?> <i class="bi bi-arrow-up-right"></i></a></div>
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

<footer class="site-footer" id="contacto"><div class="container-xl footer-main"><div><a class="brand footer-brand" href="<?= e(app_url()) ?>"><img class="brand-logo" src="<?= e(brand_url('logo')) ?>" alt="" width="58" height="58"><?= $brandLink ?></a><p><?= $tl('pie_lema') ?></p></div><div><p class="footer-label"><?= $t('pie_visitanos') ?></p><p><?= $t('direccion') ?></p><p><?= $t('horario_texto') ?></p></div><div><p class="footer-label"><?= $t('pie_siguenos') ?></p><a href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener">Instagram <i class="bi bi-arrow-up-right"></i></a><a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener">Facebook <i class="bi bi-arrow-up-right"></i></a></div><a class="footer-cta" href="#reserva"><?= $tl('pie_cta') ?> <i class="bi bi-arrow-up-right"></i></a></div><div class="container-xl footer-bottom"><span>© <?= date('Y') ?> <?= $t('marca_completa') ?></span><a href="<?= e(app_url('admin/login')) ?>"><?= $t('pie_admin') ?></a><span><?= $t('pie_ciudad') ?></span></div></footer>
<a class="whatsapp-float" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><i class="bi bi-whatsapp"></i></a>

<?php
$processSteps = text_lines(site_text($settings, 'curso_proceso'));
$paymentLines = text_lines(site_text($settings, 'curso_pagos'));
$importantNote = site_text($settings, 'curso_importante');
foreach ($courses as $course):
  $courseId = (int) $course['id'];
  $modules = course_modules($course['modulos'] ?? null);
  $includes = text_lines($course['incluye'] ?? null);
  $courseFull = (int) $course['cupos_disponibles'] <= 0;
?>
<div class="modal fade course-info-modal" id="courseInfo<?= $courseId ?>" tabindex="-1" aria-labelledby="courseInfoTitle<?= $courseId ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><div><p class="eyebrow"><?= e($course['nivel']) ?></p><h2 class="modal-title" id="courseInfoTitle<?= $courseId ?>"><?= e($course['nombre']) ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <ul class="course-info-facts">
          <li><i class="bi bi-calendar3"></i> <?= e(date('d.m.Y', strtotime($course['fecha_inicio']))) ?></li>
          <li><i class="bi bi-journal-check"></i> <?= (int) $course['sesiones'] ?> <?= $t('curso_sesiones') ?></li>
          <?php if (trim($course['horas'] ?? '') !== ''): ?><li><i class="bi bi-clock"></i> <?= e($course['horas']) ?></li><?php endif; ?>
          <li><i class="bi bi-tag"></i> <?= e(money($course['precio'])) ?></li>
        </ul>
        <p><?= e($course['descripcion']) ?></p>
        <?php if ($modules): ?>
        <h3><?= $t('curso_modulos_titulo') ?></h3>
        <?php foreach ($modules as $moduleIndex => $module): ?>
        <section class="course-module"><h4><span><?= $moduleIndex + 1 ?></span> <?= e($module['titulo'] !== '' ? $module['titulo'] : 'Módulo ' . ($moduleIndex + 1)) ?></h4><?php if ($lines = text_lines($module['contenido'])): ?><ul><?php foreach ($lines as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul><?php endif; ?></section>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($includes): ?>
        <h3><?= $t('curso_incluye_titulo') ?></h3>
        <ul class="course-includes"><?php foreach ($includes as $line): ?><li><i class="bi bi-check2"></i> <?= e($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($processSteps): ?>
        <h3><?= $t('curso_proceso_titulo') ?></h3>
        <ol class="course-steps"><?php foreach ($processSteps as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ol>
        <?php endif; ?>
        <?php if ($paymentLines): ?>
        <h3><?= $t('curso_pagos_titulo') ?></h3>
        <ul class="course-payments"><?php foreach ($paymentLines as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($importantNote !== ''): ?>
        <p class="course-important"><strong><?= $t('curso_importante_titulo') ?>:</strong> <?= nl2br(e($importantNote), false) ?></p>
        <?php endif; ?>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-back" data-bs-dismiss="modal">Cerrar</button><button type="button" class="btn btn-rose js-course-enroll" data-bs-dismiss="modal" data-course="<?= $courseId ?>" data-waitlist="<?= $courseFull ? '1' : '0' ?>"><?= $courseFull ? $t('curso_btn_espera') : $t('curso_btn_inscribir') ?> <i class="bi bi-arrow-right"></i></button></div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= e(app_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
