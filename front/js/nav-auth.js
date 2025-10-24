(() => {
  const token = localStorage.getItem('authToken');
  const nav = document.querySelector('.navbar-nav') || document.querySelector('nav ul');

  if (!nav) return;

  // Cache “Connexion” si connecté
  const loginLink = nav.querySelector('a[href$="login.html"]');
  if (token) loginLink?.classList.add('d-none');

  // Ajoute “Mon compte”
  if (token && !nav.querySelector('a[href$="account.html"]')) {
    const li = document.createElement('li');
    li.className = 'nav-item';
    li.innerHTML = `<a class="nav-link" href="./account.html">Mon compte</a>`;
    nav.appendChild(li);
  }

  // Ajoute “Déconnexion”
  if (token && !nav.querySelector('#btn-logout')) {
    const li = document.createElement('li');
    li.className = 'nav-item';
    li.innerHTML = `<button id="btn-logout" class="btn btn-outline-danger btn-sm ms-2">Déconnexion</button>`;
    nav.appendChild(li);

    document.getElementById('btn-logout')?.addEventListener('click', async () => {
      try {
        await fetch('http://localhost:8888/vite_gourmand/back/public/index.php?route=/api/auth/logout', {
          method: 'POST',
          headers: { Authorization: 'Bearer ' + localStorage.getItem('authToken') }
        });
      } catch {}
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      location.reload();
    });
  }
})();