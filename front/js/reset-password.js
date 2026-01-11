
  import { API_BASE } from "./config.js";
  const API = API_BASE;

(() => {

  const params = new URLSearchParams(location.search);
  const token = params.get('token');

  const form = document.getElementById('reset-form');
  const pwd = document.getElementById('password');
  const pwd2 = document.getElementById('password2');
  const alertBox = document.getElementById('alert-box');
  const btn = document.getElementById('btnReset');

  const pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/;

  function showAlert(message, type = 'danger') {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
  }

  if (!token) {
    showAlert('Lien invalide : token manquant.', 'danger');
    form.querySelectorAll('input,button').forEach(el => el.disabled = true);
    return;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!pwdRegex.test(pwd.value)) return showAlert('Mot de passe non conforme.', 'warning');
    if (pwd.value !== pwd2.value) return showAlert('La confirmation ne correspond pas.', 'warning');

    try {
      btn.disabled = true;
      showAlert('Mise à jour…', 'info');

      const res = await fetch(API + '/api/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ token, password: pwd.value })
      });

      const ct = (res.headers.get('content-type') || '').toLowerCase();
      const payload = ct.includes('application/json') ? await res.json() : { error: await res.text() };

      if (!res.ok) throw new Error(payload.error || 'Erreur serveur');

      showAlert('Mot de passe changé ✅ Redirection…', 'success');
      setTimeout(() => location.href = './login.html', 900);

    } catch (err) {
      showAlert(err.message, 'danger');
      btn.disabled = false;
    }
  });
})();