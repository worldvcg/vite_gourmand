(() => {
  const API = 'http://localhost:8888/vite_gourmand/back/public/index.php?route=';

  const form = document.getElementById('login-form');
  const emailInput = document.getElementById('email');
  const pwdInput = document.getElementById('password');
  const rememberInput = document.getElementById('remember');
  const togglePwdBtn = document.getElementById('togglePwd');
  const alertBox = document.getElementById('alert-box');
  const forgotLink = document.getElementById('forgot-link');
  const btnLogin = document.getElementById('btn-login');

  function showAlert(message, type = 'danger') {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
  }
  function clearAlert() {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
  }

  // Afficher / masquer le mot de passe
  togglePwdBtn?.addEventListener('click', () => {
    const isPwd = pwdInput.type === 'password';
    pwdInput.type = isPwd ? 'text' : 'password';
    togglePwdBtn.textContent = isPwd ? 'Masquer' : 'Afficher';
  });

  // Mot de passe oublié (simulation)
  forgotLink?.addEventListener('click', (e) => {
    e.preventDefault();
    const mail = emailInput.value.trim();
    if (!mail) return showAlert('Entrez d’abord votre e-mail dans le champ.', 'warning');
    showAlert(`Si un compte ${mail} existe, un lien de réinitialisation a été envoyé (simulation).`, 'info');
  });

  // Validation simple
  function validate() {
    let ok = true;
    const email = emailInput.value.trim();
    const pwd = pwdInput.value;

    const emailOK = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    emailInput.classList.toggle('is-invalid', !emailOK);
    emailInput.classList.toggle('is-valid', emailOK);
    ok = ok && emailOK;

    const pwdOK = pwd.length > 0;
    pwdInput.classList.toggle('is-invalid', !pwdOK);
    pwdInput.classList.toggle('is-valid', pwdOK);
    ok = ok && pwdOK;

    return ok;
  }

  // Soumission → appel API /api/auth/login
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();
    if (!validate()) return;

    try {
      btnLogin.disabled = true;
      showAlert('Connexion…', 'info');

      const res = await fetch(API + '/api/auth/login', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          email: emailInput.value.trim(),
          password: pwdInput.value
        })
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Erreur de connexion');

      // ✅ Stocker token + user
      localStorage.setItem('authToken', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));

      // Option “se souvenir de moi” : rien à faire pour le token (on le garde en localStorage)
      // (si tu voulais une session courte, tu pourrais à la place utiliser sessionStorage)

      // Redirection (support ?next=...)
      const params = new URLSearchParams(location.search);
      const next = params.get('next') || './index.html';
      showAlert('Connecté ✅ Redirection…', 'success');
      setTimeout(() => location.href = next, 300);
    } catch (err) {
      showAlert(err.message, 'danger');
    } finally {
      btnLogin.disabled = false;
    }
  });
})();