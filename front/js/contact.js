(() => {
  const API_BASE = 'http://localhost:9000/index.php?route=';

  const $form  = document.getElementById('contact-form');
  const $email = document.getElementById('c-email');
  const $subj  = document.getElementById('c-subject');
  const $msg   = document.getElementById('c-message');
  const $btn   = document.getElementById('btn-contact');
  const $alert = document.getElementById('contact-alert');

  const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  // ✅ retire l'erreur quand l'utilisateur tape
  [$email, $subj, $msg].forEach(el => {
    el.addEventListener('input', () => el.classList.remove('is-invalid'));
  });

  $form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // validations simples
    let ok = true;

    if (!emailRx.test($email.value.trim())) {
      $email.classList.add('is-invalid');
      ok = false;
    }

    if ($subj.value.trim().length < 3) {
      $subj.classList.add('is-invalid');
      ok = false;
    }

    if ($msg.value.trim().length < 10) {
      $msg.classList.add('is-invalid');
      ok = false;
    }

    if (!ok) {
      showAlert('warning', 'Merci de corriger les champs du formulaire.');
      return;
    }

    try {
      $btn.disabled = true;
      showAlert('info', 'Envoi en cours…');

      const payload = {
        email: $email.value.trim(),
        subject: $subj.value.trim(),
        message: $msg.value.trim()
      };

      const res = await fetch(API_BASE + '/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        throw new Error(data.error || 'Erreur serveur');
      }

      showAlert('success', '✅ Message envoyé. Nous vous répondrons par e-mail.');
      $form.reset();
      [$email, $subj, $msg].forEach(el => el.classList.remove('is-invalid'));

    } catch (err) {
      showAlert('danger', '❌ Échec : ' + err.message);
    } finally {
      $btn.disabled = false;
    }
  });

  function showAlert(type, text) {
    // ✅ RGAA friendly
    $alert.className = `alert alert-${type}`;
    $alert.setAttribute('role', 'alert');
    $alert.setAttribute('aria-live', 'polite');
    $alert.textContent = text;
    $alert.classList.remove('d-none');
  }
})();