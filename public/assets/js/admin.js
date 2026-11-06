if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const appRoot = location.pathname.split('/admin')[0];
    navigator.serviceWorker.register(`${appRoot}/sw.js`).catch(() => {});
  });
}

// Reemplazan los onclick/onchange en línea, que la Content-Security-Policy bloquea.
document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  });
});

document.querySelectorAll('[data-autosubmit]').forEach((input) => {
  input.addEventListener('change', () => input.form?.submit());
});

// Avisa antes de subir un archivo más pesado de lo permitido (evita esperar la subida completa).
document.querySelectorAll('input[type="file"][data-max-bytes]').forEach((input) => {
  input.addEventListener('change', () => {
    const file = input.files?.[0];
    const max = Number(input.dataset.maxBytes);
    input.setCustomValidity(file && max && file.size > max ? `El archivo pesa ${(file.size / 1048576).toFixed(1)} MB y el máximo es ${(max / 1048576).toFixed(0)} MB.` : '');
    input.reportValidity();
  });
});

document.querySelectorAll('[data-uploading]').forEach((button) => {
  button.form?.addEventListener('submit', () => {
    button.disabled = true;
    button.textContent = button.dataset.uploading;
  });
});

document.querySelectorAll('.service-edit-form').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const name = form.querySelector('[name="name"]');
    const duration = form.querySelector('[name="duration"]');
    if (!name.value.trim() || Number(duration.value) < 15) {
      event.preventDefault();
      name.reportValidity();
      duration.reportValidity();
    }
  });
});