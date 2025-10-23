(() => {
  const API_BASE = 'http://localhost:8888/vite_gourmand/back/public/index.php?route=';

  const $form  = document.getElementById('contact-form');
  const $email = document.getElementById('c-email');
  const $subj  = document.getElementById('c-subject');
  const $msg   = document.getElementById('c-message');
  const $btn   = document.getElementById('btn-contact');
  const $alert = document.getElementById('contact-alert');

  const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  $form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // validations simples
    let ok = true;
    if (!emailRx.test($email.value.trim())) { $email.classList.add('is-invalid'); ok = false; } else $email.classList.remove('is-invalid');
    if (!$subj.value.trim()) { $subj.classList.add('is-invalid'); ok = false; } else $subj.classList.remove('is-invalid');
    if (!$msg.value.trim())  { $msg.classList.add('is-invalid');  ok = false; } else $msg.classList.remove('is-invalid');

    if (!ok) return;

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
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const txt = await res.text();
        throw new Error(txt || 'Erreur serveur');
      }

      showAlert('success', 'Message envoyé. Nous vous répondrons par e-mail.');
      $form.reset();
    } catch (err) {
      showAlert('danger', 'Échec : ' + err.message);
    } finally {
      $btn.disabled = false;
    }
  });

  function showAlert(type, text) {
    $alert.innerHTML = `<div class="alert alert-${type}" role="alert">${text}</div>`;
  }
})();