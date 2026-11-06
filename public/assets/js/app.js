(() => {
  const base = document.querySelector('meta[name="csrf-token"]');
  const csrf = base?.content ?? '';
  const appBase = document.querySelector('meta[name="app-base"]')?.content ?? '';
  const bookingForm = document.querySelector('#bookingForm');
  const bookingFeedback = document.querySelector('#bookingFeedback');
  const jsonHeaders = { Accept: 'application/json', 'X-CSRF-Token': csrf };

  // El servidor puede responder con texto o HTML (sesión vencida, caída); nunca mostrar un error técnico.
  const readJson = async (response) => {
    try {
      return await response.json();
    } catch {
      return { error: response.status === 419 ? 'Tu sesión venció. Recarga la página e inténtalo otra vez.' : 'No pudimos conectar con el estudio. Inténtalo en unos minutos.' };
    }
  };

  const showStep = (step) => {
    document.querySelectorAll('.booking-step').forEach((item) => item.classList.toggle('active', item.dataset.step === String(step)));
    document.querySelectorAll('[data-step-dot]').forEach((dot) => {
      const number = Number(dot.dataset.stepDot);
      dot.classList.toggle('active', number === step);
      dot.classList.toggle('done', number < step);
    });
    if (step === 3) updateSummary();
  };

  document.querySelectorAll('[data-next-step]').forEach((button) => button.addEventListener('click', () => showStep(Number(button.dataset.nextStep))));
  document.querySelectorAll('[data-prev-step]').forEach((button) => button.addEventListener('click', () => showStep(Number(button.dataset.prevStep))));

  const service = document.querySelector('#bookingService');
  const date = document.querySelector('#bookingDate');
  const timeInput = document.querySelector('#bookingTime');
  const bookingNext = document.querySelector('#bookingNext');
  const serviceNext = document.querySelector('#serviceNext');
  const slotList = document.querySelector('#slotList');

  service?.addEventListener('change', () => {
    serviceNext.disabled = !service.value;
    if (date) date.value = '';
    if (timeInput) timeInput.value = '';
    if (bookingNext) bookingNext.disabled = true;
    if (slotList) slotList.innerHTML = '<span class="slot-empty">Elige un día para ver horarios.</span>';
  });

  date?.addEventListener('change', async () => {
    if (!service.value || !date.value) return;
    timeInput.value = '';
    bookingNext.disabled = true;
    slotList.innerHTML = '<span class="slot-empty">Buscando horarios…</span>';
    document.querySelector('#slotDateLabel').textContent = new Date(`${date.value}T12:00:00`).toLocaleDateString('es-PE', { day: 'numeric', month: 'short' });
    try {
      const response = await fetch(`${appBase}/api/horarios?servicio_id=${encodeURIComponent(service.value)}&fecha=${encodeURIComponent(date.value)}`, { headers: { Accept: 'application/json' } });
      const result = await readJson(response);
      if (!response.ok) throw new Error(result.error);
      slotList.replaceChildren();
      if (!result.horarios?.length) {
        slotList.innerHTML = '<span class="slot-empty">No hay horarios para este día. Prueba con otra fecha.</span>';
        return;
      }
      result.horarios.forEach((time) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'slot-option';
        button.textContent = time;
        button.addEventListener('click', () => {
          slotList.querySelectorAll('.slot-option').forEach((option) => option.classList.remove('selected'));
          button.classList.add('selected');
          timeInput.value = time;
          bookingNext.disabled = false;
        });
        slotList.append(button);
      });
    } catch {
      slotList.innerHTML = '<span class="slot-empty">No se pudo consultar. Inténtalo otra vez.</span>';
    }
  });

  function updateSummary() {
    const selected = service?.selectedOptions[0];
    const summary = document.querySelector('#bookingSummary');
    if (summary && date && timeInput) {
      const formattedDate = date.value ? new Date(`${date.value}T12:00:00`).toLocaleDateString('es-PE', { weekday: 'short', day: 'numeric', month: 'long' }) : '';
      const serviceName = document.createElement('span');
      serviceName.textContent = selected?.textContent.split(' · ')[0] ?? '';
      const when = document.createElement('span');
      when.textContent = `${formattedDate} · ${timeInput.value}`;
      summary.replaceChildren(serviceName, when);
    }
  }

  document.querySelectorAll('[data-book-service]').forEach((link) => link.addEventListener('click', () => {
    if (service) {
      service.value = link.dataset.bookService;
      service.dispatchEvent(new Event('change'));
    }
  }));

  bookingForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    bookingFeedback.textContent = '';
    const button = document.querySelector('#bookingSubmit');
    button.disabled = true;
    button.innerHTML = 'Enviando…';
    try {
      const response = await fetch(`${appBase}/api/reservar`, { method: 'POST', headers: jsonHeaders, body: new FormData(bookingForm) });
      const result = await readJson(response);
      if (!response.ok) throw new Error(result.error || 'No se pudo enviar la solicitud.');
      location.reload();
    } catch (error) {
      bookingFeedback.textContent = error.message;
      button.disabled = false;
      button.innerHTML = 'Solicitar cita <i class="bi bi-arrow-right"></i>';
    }
  });

  const enrollForm = document.querySelector('#enrollmentForm');
  const enrollFeedback = document.querySelector('#enrollFeedback');
  const waitlistFlag = document.querySelector('#waitlistFlag');
  const voucherField = document.querySelector('#voucherField');
  const enrollCourse = document.querySelector('#enrollCourse');
  const setEnrollMode = (waitlist) => {
    waitlistFlag.value = waitlist ? '1' : '0';
    voucherField.hidden = waitlist;
    document.querySelector('#enrollSubmit').textContent = waitlist ? 'Unirme a la lista' : 'Enviar solicitud';
  };
  document.querySelectorAll('.js-course-enroll').forEach((button) => button.addEventListener('click', () => {
    enrollCourse.value = button.dataset.course;
    setEnrollMode(button.dataset.waitlist === '1');
    document.querySelector('#inscripcion').scrollIntoView({ behavior: 'smooth' });
  }));
  enrollCourse?.addEventListener('change', () => {
    setEnrollMode(enrollCourse.selectedOptions[0]?.dataset.full === '1');
  });
  enrollForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    enrollFeedback.textContent = '';
    const submit = document.querySelector('#enrollSubmit');
    submit.disabled = true;
    try {
      const response = await fetch(`${appBase}/api/inscripcion`, { method: 'POST', headers: jsonHeaders, body: new FormData(enrollForm) });
      const result = await readJson(response);
      if (!response.ok) throw new Error(result.error || 'No se pudo enviar la solicitud.');
      enrollFeedback.textContent = result.message;
      enrollForm.reset();
      setEnrollMode(false);
    } catch (error) {
      enrollFeedback.textContent = error.message;
    } finally {
      submit.disabled = false;
    }
  });
})();