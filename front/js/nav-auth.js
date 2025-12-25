document.addEventListener('DOMContentLoaded', () => {
  // ✅ token
  const token = (localStorage.getItem('authToken') || '').trim();

  // ✅ user (évite crash si null / JSON cassé)
  let user = null;
  try {
    user = JSON.parse(localStorage.getItem('user') || 'null');
  } catch (e) {
    user = null;
  }

  // ✅ récup des éléments
  // 1) si tu as déjà des ids -> OK
  const btnLogin = document.getElementById('btn-login');
  const btnAccount = document.getElementById('btn-account');
  const btnLogout = document.getElementById('btn-logout');

  // 2) fallback: si "Connexion" n’a PAS d’id, on la trouve par son lien login.html
  const loginLink = document.querySelector('a[href$="login.html"], a[href*="login.html"]');
  const loginLi = loginLink?.closest('li');

  const navAdmin = document.getElementById('nav-admin');
  const navEmploye = document.getElementById('nav-employe');

  const isLogged = !!token && !!user;

  if (isLogged) {
    // ✅ utilisateur connecté
    btnLogin?.classList.add('d-none');
    loginLi?.classList.add('d-none'); // 🔥 important si pas d’id

    btnAccount?.classList.remove('d-none');
    btnLogout?.classList.remove('d-none');

    // 🔐 rôles
    if (user.role === 'admin') {
      navAdmin?.classList.remove('d-none');
    } else {
      navAdmin?.classList.add('d-none');
    }

    // employé OU admin (souvent utile)
    if (user.role === 'employe' || user.role === 'admin') {
      navEmploye?.classList.remove('d-none');
    } else {
      navEmploye?.classList.add('d-none');
    }
  } else {
    // ❌ utilisateur non connecté
    btnLogin?.classList.remove('d-none');
    loginLi?.classList.remove('d-none'); // 🔥 important si pas d’id

    btnAccount?.classList.add('d-none');
    btnLogout?.classList.add('d-none');
    navAdmin?.classList.add('d-none');
    navEmploye?.classList.add('d-none');
  }

  // 🚪 Déconnexion
  btnLogout?.addEventListener('click', async (e) => {
    e.preventDefault();

    // 🔁 relire token au moment du clic (plus fiable)
    const t = (localStorage.getItem('authToken') || '').trim();

    try {
      await fetch('http://localhost:9000/index.php?route=/api/auth/logout', {
        method: 'POST',
        headers: { Authorization: 'Bearer ' + t }
      });
    } catch (err) {
      console.warn('Logout API indisponible');
    }

    localStorage.removeItem('authToken');
    localStorage.removeItem('user');

    // ✅ redirection propre
    window.location.href = './index.html';
  });
});